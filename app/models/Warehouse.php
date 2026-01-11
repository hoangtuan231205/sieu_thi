<?php

class Warehouse extends Model
{
    /**
     * Count imports with 3-field search
     */
    public function countImports3Fields($filters)
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM phieu_nhap_kho pn
            LEFT JOIN tai_khoan tk ON tk.ID = pn.Nguoi_tao
            WHERE 1=1
        ";
        $conds  = [];
        $params = [];

        if ($filters['ma_phieu'] !== '') {
            $conds[]  = "pn.Ma_hien_thi LIKE ?";
            $params[] = '%' . $filters['ma_phieu'] . '%';
        }

        if ($filters['nguoi_tao'] !== '') {
            $conds[]  = "tk.Ho_ten LIKE ?";
            $params[] = '%' . $filters['nguoi_tao'] . '%';
        }

        if ($filters['ngay_nhap'] !== '') {
            $conds[]  = "DATE(pn.Ngay_nhap) = ?";
            $params[] = $filters['ngay_nhap'];
        }

        if (!empty($conds)) {
            $sql .= " AND (" . implode(' OR ', $conds) . ") ";
        }


        $row = $this->db->query($sql, $params)->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get imports list with 3-field search + pagination
     */
    public function getImports3Fields($filters, $limit, $offset)
    {
        $sql = "
            SELECT
                pn.ID_phieu_nhap,
                pn.Ma_hien_thi,
                pn.Ngay_nhap,
                pn.Tong_tien,
                pn.Ghi_chu,
                pn.Ngay_cap_nhat,
                pn.Nguoi_tao,
                tk.Ho_ten AS Nguoi_tao_ten
            FROM phieu_nhap_kho pn
            LEFT JOIN tai_khoan tk ON tk.ID = pn.Nguoi_tao
            WHERE 1=1
        ";
        $conds  = [];
        $params = [];

        if ($filters['ma_phieu'] !== '') {
            $conds[]  = "pn.Ma_hien_thi LIKE ?";
            $params[] = '%' . $filters['ma_phieu'] . '%';
        }

        if ($filters['nguoi_tao'] !== '') {
            $conds[]  = "tk.Ho_ten LIKE ?";
            $params[] = '%' . $filters['nguoi_tao'] . '%';
        }

        if ($filters['ngay_nhap'] !== '') {
            $conds[]  = "DATE(pn.Ngay_nhap) = ?";
            $params[] = $filters['ngay_nhap'];
        }

        if (!empty($conds)) {
            $sql .= " AND (" . implode(' AND ', $conds) . ") ";
        }


        $sql .= " ORDER BY pn.ID_phieu_nhap DESC LIMIT ? OFFSET ? ";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->db->query($sql, $params)->fetchAll();

    }
    public function getImportsForExport($filters = [])
    {
        $sql = "
            SELECT 
                pn.ID_phieu_nhap,
                pn.Ma_hien_thi,
                pn.Ngay_nhap,
                tk.Ho_ten AS Nguoi_tao_ten,
                pn.Tong_tien,
                pn.Ghi_chu
            FROM phieu_nhap_kho pn
            JOIN tai_khoan tk ON tk.ID = pn.Nguoi_tao
            WHERE 1 = 1
        ";

        $params = [];

        if (!empty($filters['ma_phieu'])) {
            $sql .= " AND pn.Ma_hien_thi LIKE ?";
            $params[] = '%' . $filters['ma_phieu'] . '%';
        }

        if (!empty($filters['nguoi_tao'])) {
            $sql .= " AND tk.Ho_ten LIKE ?";
            $params[] = '%' . $filters['nguoi_tao'] . '%';
        }

        if (!empty($filters['ngay_nhap'])) {
            $sql .= " AND pn.Ngay_nhap = ?";
            $params[] = $filters['ngay_nhap'];
        }

        $sql .= " ORDER BY pn.ID_phieu_nhap DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }



    public function getImportById($id)
    {
        $sql = "
            SELECT
                pn.ID_phieu_nhap,
                pn.Ma_hien_thi,
                pn.Ngay_nhap,
                pn.Tong_tien,
                pn.Ghi_chu,
                pn.Ngay_cap_nhat,
                pn.Nguoi_tao,
                tk.Ho_ten AS Nguoi_tao_ten
            FROM phieu_nhap_kho pn
            LEFT JOIN tai_khoan tk ON tk.ID = pn.Nguoi_tao
            WHERE pn.ID_phieu_nhap = ?
            LIMIT 1
        ";
        return $this->db->query($sql, [$id])->fetch();

    }

    public function getImportDetails($idPhieuNhap)
    {
        $sql = "
            SELECT
                ct.ID_chi_tiet_nhap,
                ct.ID_phieu_nhap,
                ct.ID_sp,
                ct.Ten_sp,
                ct.Don_vi_tinh,
                sp.Gia_tien AS Gia_hien_tai,
                ct.So_luong,
                ct.Don_gia_nhap,
                ct.Thanh_tien
            FROM chi_tiet_phieu_nhap ct
            LEFT JOIN san_pham sp ON sp.ID_sp = ct.ID_sp
            WHERE ct.ID_phieu_nhap = ?
            ORDER BY ct.ID_chi_tiet_nhap ASC
        ";
        return $this->db->query($sql, [$idPhieuNhap])->fetchAll();
    }


    /**
     * CREATE import + details + UPDATE STOCK
     * items[]:
     *  - ID_sp
     *  - Ten_sp
     *  - Don_vi_tinh
     *  - So_luong
     *  - Don_gia_nhap
     *  (Xuat_xu, Nha_cung_cap optional)
     */
    public function createImport($userId, $ngayNhap, $ghiChu, $items)
    {
        $this->db->beginTransaction();
        try {
            $tongTien = $this->calcTotal($items);

            // Ma_hien_thi sẽ do trigger sinh (NEW.Ma_hien_thi null)
            $sql1 = "
                INSERT INTO phieu_nhap_kho (Ma_hien_thi, Nguoi_tao, Ngay_nhap, Tong_tien, Ghi_chu, Ngay_cap_nhat)
                VALUES (NULL, ?, ?, ?, ?, NOW())
            ";
            $this->db->query($sql1, [$userId, $ngayNhap, $tongTien, $ghiChu]);

            $id = (int)$this->db->lastInsertId();

            $sql2 = "
                INSERT INTO chi_tiet_phieu_nhap
                (ID_phieu_nhap, ID_sp, Ten_sp, Don_vi_tinh, Xuat_xu, Nha_cung_cap, So_luong, Don_gia_nhap, Thanh_tien)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            // Query update tồn kho
            $sqlUpdateStock = "UPDATE san_pham SET So_luong_ton = So_luong_ton + ? WHERE ID_sp = ?";

            foreach ($items as $it) {
                $idSp   = (int)($it['ID_sp'] ?? 0);
                $tenSp  = trim($it['Ten_sp'] ?? '');
                $dvt    = trim($it['Don_vi_tinh'] ?? 'SP');
                $xx     = trim($it['Xuat_xu'] ?? '');
                $ncc    = trim($it['Nha_cung_cap'] ?? '');
                $sl     = (int)($it['So_luong'] ?? 0);
                $dg     = (float)($it['Don_gia_nhap'] ?? 0);
                $tt     = $sl * $dg;

                if ($idSp <= 0 || $tenSp === '' || $sl <= 0) {
                    throw new Exception('Dòng sản phẩm không hợp lệ');
                }

                $this->db->query($sql2, [$id, $idSp, $tenSp, $dvt, $xx, $ncc, $sl, $dg, $tt]);
                
                // Cập nhật tồn kho - ĐÃ XỬ LÝ BỞI TRIGGER `trg_nhap_kho_tang_ton`
                // Không update PHP để tránh cộng dồn 2 lần
                // $this->db->query($sqlUpdateStock, [$sl, $idSp]);
            }

            $this->db->commit();
            return $id;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateImport($id, $ngayNhap, $ghiChu, $items)
    {
        $this->db->beginTransaction();
        try {
            // 1. Revert stock cũ (Trừ đi số lượng của phiếu cũ)
            $oldDetails = $this->getImportDetails($id);
            $sqlRevertStock = "UPDATE san_pham SET So_luong_ton = GREATEST(0, So_luong_ton - ?) WHERE ID_sp = ?";
            
            foreach ($oldDetails as $oldItem) {
                $this->db->query($sqlRevertStock, [$oldItem['So_luong'], $oldItem['ID_sp']]);
            }

            // 2. Update phiếu nhập
            $tongTien = $this->calcTotal($items);

            $sql1 = "
                UPDATE phieu_nhap_kho
                SET Ngay_nhap = ?, Tong_tien = ?, Ghi_chu = ?, Ngay_cap_nhat = NOW()
                WHERE ID_phieu_nhap = ?
            ";
            $this->db->query($sql1, [$ngayNhap, $tongTien, $ghiChu, $id]);

            // 3. Xóa chi tiết cũ
            $this->db->query("DELETE FROM chi_tiet_phieu_nhap WHERE ID_phieu_nhap = ?", [$id]);

            // 4. Insert chi tiết mới & Cộng tồn kho mới
            $sql2 = "
                INSERT INTO chi_tiet_phieu_nhap
                (ID_phieu_nhap, ID_sp, Ten_sp, Don_vi_tinh, Xuat_xu, Nha_cung_cap, So_luong, Don_gia_nhap, Thanh_tien)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $sqlAddStock = "UPDATE san_pham SET So_luong_ton = So_luong_ton + ? WHERE ID_sp = ?";

            foreach ($items as $it) {
                $idSp   = (int)($it['ID_sp'] ?? 0);
                $tenSp  = trim($it['Ten_sp'] ?? '');
                $dvt    = trim($it['Don_vi_tinh'] ?? 'SP');
                $xx     = trim($it['Xuat_xu'] ?? '');
                $ncc    = trim($it['Nha_cung_cap'] ?? '');
                $sl     = (int)($it['So_luong'] ?? 0);
                $dg     = (float)($it['Don_gia_nhap'] ?? 0);
                $tt     = $sl * $dg;

                if ($idSp <= 0 || $tenSp === '' || $sl <= 0) {
                    throw new Exception('Dòng sản phẩm không hợp lệ');
                }

                $this->db->query($sql2, [$id, $idSp, $tenSp, $dvt, $xx, $ncc, $sl, $dg, $tt]);
                
                // Cộng tồn kho mới - ĐÃ XỬ LÝ BỞI TRIGGER `trg_nhap_kho_tang_ton`
                // Không update PHP để tránh cộng dồn 2 lần
                // $this->db->query($sqlAddStock, [$sl, $idSp]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tìm kiếm sản phẩm cho phiếu nhập/hủy
     * Có hỗ trợ tiếng Việt: cá ≠ cải, rau ≠ râu
     * Tìm theo: Tên sản phẩm, Mã sản phẩm, Tên danh mục
     */
    public function searchProductsSimple($q, $limit = 20)
    {
        $q = trim($q);
        if (mb_strlen($q) < 1) return [];
        
        // Fetch more results for PHP filtering
        $fetchLimit = max((int)$limit * 5, 100);
        
        // Include category name in search
        $sql = "
            SELECT
                sp.ID_sp AS id,
                sp.Ma_hien_thi AS ma,
                sp.Ten AS ten,
                sp.Don_vi_tinh AS dvt,
                sp.Gia_tien AS gia,
                dm.Ten_danh_muc AS danh_muc
            FROM san_pham sp
            LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
            WHERE sp.Trang_thai = 'active' 
              AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ? OR dm.Ten_danh_muc LIKE ?)
            ORDER BY sp.ID_sp DESC
            LIMIT ?
        ";
        
        $searchTerm = '%' . $q . '%';
        $results = $this->db->query($sql, [$searchTerm, $searchTerm, $searchTerm, $fetchLimit])->fetchAll();
        
        // Vietnamese diacritics filter - ACCURATE matching
        $qLower = mb_strtolower($q, 'UTF-8');
        $filtered = [];
        
        foreach ($results as $row) {
            $tenLower = mb_strtolower($row['ten'] ?? '', 'UTF-8');
            $maLower = mb_strtolower($row['ma'] ?? '', 'UTF-8');
            $dmLower = mb_strtolower($row['danh_muc'] ?? '', 'UTF-8');
            
            // Check if keyword EXACTLY matches (accent-sensitive)
            $matchTen = (mb_strpos($tenLower, $qLower, 0, 'UTF-8') !== false);
            $matchMa = (mb_strpos($maLower, $qLower, 0, 'UTF-8') !== false);
            $matchDm = (mb_strpos($dmLower, $qLower, 0, 'UTF-8') !== false);
            
            if ($matchTen || $matchMa || $matchDm) {
                $filtered[] = $row;
                if (count($filtered) >= $limit) break;
            }
        }
        
        return $filtered;
    }

    private function calcTotal($items)
    {
        $sum = 0.0;
        foreach ($items as $it) {
            $sl = (int)($it['So_luong'] ?? 0);
            $dg = (float)($it['Don_gia_nhap'] ?? 0);
            $sum += ($sl * $dg);
        }
        return $sum;
    }

    public function deleteImport($id)
    {
        $this->db->beginTransaction();
        try {
            // 1. Trừ ngược tồn kho trước khi xóa
            $oldDetails = $this->getImportDetails($id);
            $sqlRevertStock = "UPDATE san_pham SET So_luong_ton = GREATEST(0, So_luong_ton - ?) WHERE ID_sp = ?";
            
            foreach ($oldDetails as $oldItem) {
                $this->db->query($sqlRevertStock, [$oldItem['So_luong'], $oldItem['ID_sp']]);
            }

            // 2. Xóa chi tiết
            $this->db->query(
                "DELETE FROM chi_tiet_phieu_nhap WHERE ID_phieu_nhap = ?",
                [$id]
            );

            // 3. Xóa phiếu
            $this->db->query(
                "DELETE FROM phieu_nhap_kho WHERE ID_phieu_nhap = ?",
                [$id]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    public function getAllImportDetailsForExport($filters = [])
    {
        $sql = "
            SELECT 
                pn.Ma_hien_thi       AS Ma_phieu,
                sp.Ma_hien_thi       AS Ma_sp,
                ctpn.Ten_sp,
                ctpn.So_luong,
                ctpn.Don_gia_nhap,
                ctpn.Thanh_tien
            FROM chi_tiet_phieu_nhap ctpn
            JOIN phieu_nhap_kho pn ON pn.ID_phieu_nhap = ctpn.ID_phieu_nhap
            JOIN san_pham sp ON sp.ID_sp = ctpn.ID_sp
            WHERE 1 = 1
        ";

        $params = [];

        if (!empty($filters['ma_phieu'])) {
            $sql .= " AND pn.Ma_hien_thi LIKE ?";
            $params[] = '%' . $filters['ma_phieu'] . '%';
        }

        if (!empty($filters['ngay_nhap'])) {
            $sql .= " AND pn.Ngay_nhap = ?";
            $params[] = $filters['ngay_nhap'];
        }

        $sql .= " ORDER BY pn.ID_phieu_nhap DESC";

        return $this->db->query($sql, $params)->fetchAll();

    }


}
