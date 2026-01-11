<?php
/**
 * =============================================================================
 * SUPPLIER MODEL - QUẢN LÝ NHÀ CUNG CẤP
 * =============================================================================
 * 
 * Bảng: nha_cung_cap
 * 
 * Chức năng:
 * - CRUD nhà cung cấp
 * - Thống kê sản phẩm theo nhà cung cấp
 */

class Supplier {
    private $db;
    private $table = 'nha_cung_cap';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lấy tất cả nhà cung cấp
     * 
     * @param array $filters Bộ lọc (keyword, status)
     * @param int $page Trang hiện tại
     * @param int $perPage Số item/trang
     * @return array
     */
    public function getAll($filters = [], $page = 1, $perPage = 12) {
        $where = "1=1";
        $params = [];
        
        // Filter by keyword
        if (!empty($filters['keyword'])) {
            $where .= " AND (Ten_ncc LIKE ? OR Ma_hien_thi LIKE ? OR Email LIKE ?)";
            $keyword = "%" . $filters['keyword'] . "%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where .= " AND Trang_thai = ?";
            $params[] = $filters['status'];
        }
        
        // Count total
        $countResult = $this->db->query("SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}", $params)->fetch();
        $total = $countResult['total'] ?? 0;
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        // Get data
        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY Ngay_tao DESC LIMIT {$perPage} OFFSET {$offset}";
        $suppliers = $this->db->query($sql, $params)->fetchAll();
        
        return [
            'data' => $suppliers,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ]
        ];
    }
    
    /**
     * Lấy một nhà cung cấp theo ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE ID_ncc = ?", [$id])->fetch();
    }
    
    /**
     * Tạo nhà cung cấp mới
     * 
     * @param array $data
     * @return int|false ID mới hoặc false nếu lỗi
     */
    public function create($data) {
        // Generate display code
        $lastId = $this->db->query("SELECT MAX(ID_ncc) as max_id FROM {$this->table}")->fetch();
        $nextNum = ($lastId['max_id'] ?? 0) + 1;
        $maHienThi = 'NCC-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO {$this->table} (Ma_hien_thi, Ten_ncc, Dia_chi, Sdt, Email, Nguoi_lien_he, Mo_ta, Trang_thai, Ngay_tao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $maHienThi,
            $data['Ten_ncc'],
            $data['Dia_chi'] ?? null,
            $data['Sdt'] ?? null,
            $data['Email'] ?? null,
            $data['Nguoi_lien_he'] ?? null,
            $data['Mo_ta'] ?? null,
            $data['Trang_thai'] ?? 'active'
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Cập nhật nhà cung cấp
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                Ten_ncc = ?, 
                Dia_chi = ?, 
                Sdt = ?, 
                Email = ?, 
                Nguoi_lien_he = ?, 
                Mo_ta = ?, 
                Trang_thai = ?
                WHERE ID_ncc = ?";
        
        $params = [
            $data['Ten_ncc'],
            $data['Dia_chi'] ?? null,
            $data['Sdt'] ?? null,
            $data['Email'] ?? null,
            $data['Nguoi_lien_he'] ?? null,
            $data['Mo_ta'] ?? null,
            $data['Trang_thai'] ?? 'active',
            $id
        ];
        
        $this->db->query($sql, $params);
        return true;
    }
    
    /**
     * Xóa nhà cung cấp
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE ID_ncc = ?", [$id]);
        return true;
    }
    
    /**
     * Cập nhật trạng thái
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status) {
        $this->db->query("UPDATE {$this->table} SET Trang_thai = ? WHERE ID_ncc = ?", [$status, $id]);
        return true;
    }
    
    /**
     * Đếm tổng số nhà cung cấp
     * 
     * @return array ['active' => x, 'inactive' => y, 'all' => z]
     */
    public function countByStatus() {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Trang_thai = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Trang_thai = 'inactive' THEN 1 ELSE 0 END) as inactive
            FROM {$this->table}
        ")->fetch();
        
        return [
            'all' => $result['total'] ?? 0,
            'active' => $result['active'] ?? 0,
            'inactive' => $result['inactive'] ?? 0
        ];
    }
    
    /**
     * Lấy danh sách cho dropdown
     * 
     * @return array
     */
    public function getForDropdown() {
        return $this->db->query("SELECT ID_ncc, Ten_ncc FROM {$this->table} WHERE Trang_thai = 'active' ORDER BY Ten_ncc")->fetchAll();
    }
}
