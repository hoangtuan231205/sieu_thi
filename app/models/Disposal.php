<?php
/**
 * =============================================================================
 * DISPOSAL MODEL - QUẢN LÝ PHIẾU HỦY
 * =============================================================================
 * 
 * Bảng: phieu_huy, chi_tiet_phieu_huy
 * 
 * Chức năng:
 * - CRUD phiếu hủy
 * - Approval workflow (Admin duyệt)
 * - Thống kê hàng hủy
 */

class Disposal extends Model {
    
    protected $table = 'phieu_huy';
    protected $primaryKey = 'ID_phieu_huy';
    
    /**
     * Vietnamese diacritics matching (rau ≠ râu)
     */
    private function vietnameseMatch($text, $keyword) {
        $text = mb_strtolower($text, 'UTF-8');
        $keyword = mb_strtolower($keyword, 'UTF-8');
        return mb_strpos($text, $keyword, 0, 'UTF-8') !== false;
    }
    
    // ==========================================================================
    // CRUD PHIẾU HỦY
    // ==========================================================================
    
    /**
     * Tạo phiếu hủy mới
     * 
     * @param int $userId ID người tạo
     * @param string $date Ngày hủy
     * @param string $type Loại phiếu (huy, hong, het_han, dieu_chinh)
     * @param string $reason Lý do
     * @param array $items Chi tiết sản phẩm hủy
     * @return int|false ID phiếu hủy
     */
    public function createDisposal($userId, $date, $type, $reason, $items) {
        try {
            $this->beginTransaction();
            
            // Tạo phiếu hủy (trigger sẽ tự generate mã)
            $disposalId = $this->create([
                'Nguoi_tao' => $userId,
                'Ngay_huy' => $date,
                'Loai_phieu' => $type,
                'Ly_do' => $reason,
                'Trang_thai' => 'cho_duyet'
            ]);
            
            if (!$disposalId) {
                throw new Exception('Không thể tạo phiếu hủy');
            }
            
            // Thêm chi tiết
            foreach ($items as $item) {
                $this->addDetail($disposalId, $item);
            }
            
            $this->commit();
            return $disposalId;
            
        } catch (Exception $e) {
            $this->rollBack();
            error_log("Disposal::createDisposal Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Thêm chi tiết phiếu hủy
     */
    private function addDetail($disposalId, $item) {
        $sql = "INSERT INTO chi_tiet_phieu_huy 
                (ID_phieu_huy, ID_sp, ID_lo_nhap, Ten_sp, So_luong, Gia_nhap, Thanh_tien, Ghi_chu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $disposalId,
            $item['ID_sp'],
            !empty($item['ID_lo_nhap']) ? $item['ID_lo_nhap'] : null,
            $item['Ten_sp'],
            $item['So_luong'],
            $item['Gia_nhap'],
            $item['So_luong'] * $item['Gia_nhap'],
            $item['Ghi_chu'] ?? null
        ]);
    }
    
    /**
     * Lấy danh sách phiếu hủy với filter
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getDisposals($filters = [], $limit = 20, $offset = 0) {
        $hasKeyword = !empty($filters['keyword']);
        $keyword = $hasKeyword ? trim($filters['keyword']) : '';
        
        // Also fetch product names for Vietnamese filtering
        $sql = "SELECT ph.*, tk.Ho_ten AS Ten_nguoi_tao,
                       tk2.Ho_ten AS Ten_nguoi_duyet,
                       (SELECT COUNT(*) FROM chi_tiet_phieu_huy WHERE ID_phieu_huy = ph.ID_phieu_huy) AS So_san_pham,
                       (SELECT GROUP_CONCAT(sp.Ten SEPARATOR ', ') 
                        FROM chi_tiet_phieu_huy ct 
                        JOIN san_pham sp ON ct.ID_sp = sp.ID_sp 
                        WHERE ct.ID_phieu_huy = ph.ID_phieu_huy) AS Ten_san_pham_list
                FROM phieu_huy ph
                INNER JOIN tai_khoan tk ON ph.Nguoi_tao = tk.ID
                LEFT JOIN tai_khoan tk2 ON ph.Nguoi_duyet = tk2.ID
                WHERE 1=1";
        
        $params = [];
        
        // SQL LIKE for initial filtering
        if ($hasKeyword) {
            $sql .= " AND (ph.Ma_hien_thi LIKE ? OR ph.Ly_do LIKE ? OR EXISTS (
                        SELECT 1 FROM chi_tiet_phieu_huy ct 
                        JOIN san_pham sp ON ct.ID_sp = sp.ID_sp 
                        WHERE ct.ID_phieu_huy = ph.ID_phieu_huy 
                        AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ?)
                      ))";
            $kwLike = '%' . $keyword . '%';
            $params[] = $kwLike;
            $params[] = $kwLike;
            $params[] = $kwLike;
            $params[] = $kwLike;
        }
        
        // Filter by status
        if (!empty($filters['trang_thai'])) {
            $sql .= " AND ph.Trang_thai = ?";
            $params[] = $filters['trang_thai'];
        }
        
        // Filter by type
        if (!empty($filters['loai_phieu'])) {
            $sql .= " AND ph.Loai_phieu = ?";
            $params[] = $filters['loai_phieu'];
        }
        
        // Filter by date range
        if (!empty($filters['tu_ngay'])) {
            $sql .= " AND ph.Ngay_huy >= ?";
            $params[] = $filters['tu_ngay'];
        }
        
        if (!empty($filters['den_ngay'])) {
            $sql .= " AND ph.Ngay_huy <= ?";
            $params[] = $filters['den_ngay'];
        }
        
        $sql .= " ORDER BY ph.Ngay_tao DESC";
        
        // Fetch more if keyword for PHP filtering
        $fetchLimit = $hasKeyword ? max($limit * 3, 60) : $limit;
        $fetchOffset = $hasKeyword ? 0 : $offset;
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $fetchLimit;
        $params[] = $fetchOffset;
        
        $results = $this->query($sql, $params);
        
        // Vietnamese diacritics filter
        if ($hasKeyword) {
            $filtered = [];
            foreach ($results as $row) {
                $matchMa = $this->vietnameseMatch($row['Ma_hien_thi'] ?? '', $keyword);
                $matchLyDo = $this->vietnameseMatch($row['Ly_do'] ?? '', $keyword);
                $matchSP = $this->vietnameseMatch($row['Ten_san_pham_list'] ?? '', $keyword);
                
                if ($matchMa || $matchLyDo || $matchSP) {
                    $filtered[] = $row;
                }
            }
            return array_slice($filtered, $offset, $limit);
        }
        
        return $results;
    }
    
    /**
     * Đếm phiếu hủy với filter (có hỗ trợ Vietnamese)
     */
    public function countDisposals($filters = []) {
        $hasKeyword = !empty($filters['keyword']);
        $keyword = $hasKeyword ? trim($filters['keyword']) : '';
        
        if ($hasKeyword) {
            // Need to count with Vietnamese match - fetch and count in PHP
            $sql = "SELECT ph.Ma_hien_thi, ph.Ly_do,
                           (SELECT GROUP_CONCAT(sp.Ten SEPARATOR ', ') 
                            FROM chi_tiet_phieu_huy ct 
                            JOIN san_pham sp ON ct.ID_sp = sp.ID_sp 
                            WHERE ct.ID_phieu_huy = ph.ID_phieu_huy) AS Ten_san_pham_list
                    FROM phieu_huy ph WHERE 1=1";
            $params = [];
            
            // SQL LIKE for initial filter
            $sql .= " AND (ph.Ma_hien_thi LIKE ? OR ph.Ly_do LIKE ? OR EXISTS (
                        SELECT 1 FROM chi_tiet_phieu_huy ct 
                        JOIN san_pham sp ON ct.ID_sp = sp.ID_sp 
                        WHERE ct.ID_phieu_huy = ph.ID_phieu_huy 
                        AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ?)
                      ))";
            $kwLike = '%' . $keyword . '%';
            $params[] = $kwLike;
            $params[] = $kwLike;
            $params[] = $kwLike;
            $params[] = $kwLike;
            
            if (!empty($filters['trang_thai'])) {
                $sql .= " AND ph.Trang_thai = ?";
                $params[] = $filters['trang_thai'];
            }
            
            if (!empty($filters['loai_phieu'])) {
                $sql .= " AND ph.Loai_phieu = ?";
                $params[] = $filters['loai_phieu'];
            }
            
            if (!empty($filters['tu_ngay'])) {
                $sql .= " AND ph.Ngay_huy >= ?";
                $params[] = $filters['tu_ngay'];
            }
            
            if (!empty($filters['den_ngay'])) {
                $sql .= " AND ph.Ngay_huy <= ?";
                $params[] = $filters['den_ngay'];
            }
            
            $results = $this->query($sql, $params);
            
            // Vietnamese count
            $count = 0;
            foreach ($results as $row) {
                $matchMa = $this->vietnameseMatch($row['Ma_hien_thi'] ?? '', $keyword);
                $matchLyDo = $this->vietnameseMatch($row['Ly_do'] ?? '', $keyword);
                $matchSP = $this->vietnameseMatch($row['Ten_san_pham_list'] ?? '', $keyword);
                
                if ($matchMa || $matchLyDo || $matchSP) {
                    $count++;
                }
            }
            return $count;
        }
        
        // No keyword - simple count
        $sql = "SELECT COUNT(*) AS total FROM phieu_huy ph WHERE 1=1";
        $params = [];
        
        if (!empty($filters['trang_thai'])) {
            $sql .= " AND Trang_thai = ?";
            $params[] = $filters['trang_thai'];
        }
        
        if (!empty($filters['loai_phieu'])) {
            $sql .= " AND Loai_phieu = ?";
            $params[] = $filters['loai_phieu'];
        }
        
        if (!empty($filters['tu_ngay'])) {
            $sql .= " AND Ngay_huy >= ?";
            $params[] = $filters['tu_ngay'];
        }
        
        if (!empty($filters['den_ngay'])) {
            $sql .= " AND Ngay_huy <= ?";
            $params[] = $filters['den_ngay'];
        }
        
        $result = $this->queryOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Lấy chi tiết phiếu hủy
     */
    public function getDisposalById($id) {
        $sql = "SELECT ph.*, tk.Ho_ten AS Ten_nguoi_tao,
                       tk2.Ho_ten AS Ten_nguoi_duyet
                FROM phieu_huy ph
                INNER JOIN tai_khoan tk ON ph.Nguoi_tao = tk.ID
                LEFT JOIN tai_khoan tk2 ON ph.Nguoi_duyet = tk2.ID
                WHERE ph.ID_phieu_huy = ?";
        
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Lấy chi tiết sản phẩm trong phiếu hủy
     */
    public function getDisposalDetails($disposalId) {
        $sql = "SELECT ct.*, sp.Ma_hien_thi AS Ma_SP, sp.Hinh_anh,
                       pn.Ma_hien_thi AS Ma_phieu_nhap, pn.Ngay_nhap,
                       ct_pn.Ngay_het_han
                FROM chi_tiet_phieu_huy ct
                INNER JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
                LEFT JOIN chi_tiet_phieu_nhap ct_pn ON ct.ID_lo_nhap = ct_pn.ID_chi_tiet_nhap
                LEFT JOIN phieu_nhap_kho pn ON ct_pn.ID_phieu_nhap = pn.ID_phieu_nhap
                WHERE ct.ID_phieu_huy = ?
                ORDER BY ct.ID_chi_tiet";
        
        return $this->query($sql, [$disposalId]);
    }
    
    // ==========================================================================
    // APPROVAL WORKFLOW
    // ==========================================================================
    
    /**
     * Duyệt phiếu hủy (chỉ Admin)
     * UPDATE: Cập nhật tồn kho sản phẩm (Trừ kho)
     */
    public function approve($disposalId, $approverId) {
        $this->beginTransaction();
        try {
            // 1. Cập nhật trạng thái phiếu
            $sql = "UPDATE phieu_huy 
                    SET Trang_thai = 'da_duyet', 
                        Nguoi_duyet = ?, 
                        Ngay_duyet = NOW()
                    WHERE ID_phieu_huy = ? AND Trang_thai = 'cho_duyet'";
            
            $this->db->query($sql, [$approverId, $disposalId]);
            
            if ($this->db->rowCount() === 0) {
                // Không tìm thấy phiếu hoặc phiếu không ở trạng thái 'cho_duyet'
                $this->rollBack();
                return false;
            }
            
            // 2. Lấy chi tiết phiếu hủy
            $details = $this->getDisposalDetails($disposalId);
            
            // 3. Trừ tồn kho - ĐÃ XỬ LÝ BỞI TRIGGER `trg_duyet_phieu_huy_tru_kho`
            // Không thực hiện update ở đây để tránh trừ 2 lần
            /*
            $sqlUpdateStock = "UPDATE san_pham SET So_luong_ton = GREATEST(0, So_luong_ton - ?) WHERE ID_sp = ?";
            
            foreach ($details as $item) {
                $this->db->query($sqlUpdateStock, [$item['So_luong'], $item['ID_sp']]);
            }
            */
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollBack();
            error_log("Disposal::approve Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Từ chối phiếu hủy
     */
    public function reject($disposalId, $approverId, $reason) {
        $sql = "UPDATE phieu_huy 
                SET Trang_thai = 'tu_choi', 
                    Nguoi_duyet = ?, 
                    Ngay_duyet = NOW(),
                    Ly_do_tu_choi = ?
                WHERE ID_phieu_huy = ? AND Trang_thai = 'cho_duyet'";
        
        $this->db->query($sql, [$approverId, $reason, $disposalId]);
        return $this->db->rowCount() > 0;
    }
    
    /**
     * Đếm phiếu hủy chờ duyệt
     */
    public function countPending() {
        $result = $this->queryOne("SELECT COUNT(*) AS total FROM phieu_huy WHERE Trang_thai = 'cho_duyet'");
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Đếm phiếu hủy theo từng trạng thái
     * @return array ['all' => x, 'cho_duyet' => y, 'da_duyet' => z, 'tu_choi' => w]
     */
    public function countByStatus() {
        $all = $this->queryOne("SELECT COUNT(*) AS total FROM phieu_huy");
        $pending = $this->queryOne("SELECT COUNT(*) AS total FROM phieu_huy WHERE Trang_thai = 'cho_duyet'");
        $approved = $this->queryOne("SELECT COUNT(*) AS total FROM phieu_huy WHERE Trang_thai = 'da_duyet'");
        $rejected = $this->queryOne("SELECT COUNT(*) AS total FROM phieu_huy WHERE Trang_thai = 'tu_choi'");
        
        return [
            'all' => (int)($all['total'] ?? 0),
            'cho_duyet' => (int)($pending['total'] ?? 0),
            'da_duyet' => (int)($approved['total'] ?? 0),
            'tu_choi' => (int)($rejected['total'] ?? 0)
        ];
    }
    
    /**
     * Lấy phiếu hủy chờ duyệt (cho dashboard)
     */
    public function getPendingDisposals($limit = 5) {
        return $this->query("SELECT * FROM v_phieu_huy_cho_duyet LIMIT ?", [$limit]);
    }
    
    // ==========================================================================
    // THỐNG KÊ
    // ==========================================================================
    
    /**
     * Thống kê hàng hủy theo loại
     */
    public function getDisposalStats() {
        return $this->query("SELECT * FROM v_thong_ke_hang_huy");
    }
    
    /**
     * Tổng giá trị hàng hủy trong khoảng thời gian
     */
    public function getTotalDisposalValue($dateFrom, $dateTo) {
        $sql = "SELECT COALESCE(SUM(Tong_tien_huy), 0) AS total
                FROM phieu_huy 
                WHERE Trang_thai = 'da_duyet'
                  AND Ngay_huy BETWEEN ? AND ?";
        
        $result = $this->queryOne($sql, [$dateFrom, $dateTo]);
        return (float)($result['total'] ?? 0);
    }
    
    // ==========================================================================
    // EXPORT EXCEL
    // ==========================================================================
    
    /**
     * Lấy dữ liệu để xuất Excel
     */
    public function getDisposalsForExport($filters = []) {
        $sql = "SELECT ph.Ma_hien_thi, ph.Loai_phieu, ph.Ngay_huy, ph.Ly_do,
                       ph.Tong_tien_huy, ph.Trang_thai, ph.Ngay_tao,
                       tk.Ho_ten AS Nguoi_tao, tk2.Ho_ten AS Nguoi_duyet, ph.Ngay_duyet
                FROM phieu_huy ph
                INNER JOIN tai_khoan tk ON ph.Nguoi_tao = tk.ID
                LEFT JOIN tai_khoan tk2 ON ph.Nguoi_duyet = tk2.ID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['trang_thai'])) {
            $sql .= " AND ph.Trang_thai = ?";
            $params[] = $filters['trang_thai'];
        }
        
        if (!empty($filters['tu_ngay'])) {
            $sql .= " AND ph.Ngay_huy >= ?";
            $params[] = $filters['tu_ngay'];
        }
        
        if (!empty($filters['den_ngay'])) {
            $sql .= " AND ph.Ngay_huy <= ?";
            $params[] = $filters['den_ngay'];
        }
        
        $sql .= " ORDER BY ph.Ngay_huy DESC";
        
        return $this->query($sql, $params);
    }
}
