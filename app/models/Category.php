<?php
/**
 * =============================================================================
 * CATEGORY MODEL - QUẢN LÝ DANH MỤC
 * =============================================================================
 * 
 * Bảng: danh_muc
 * 
 * Chức năng:
 * - CRUD danh mục
 * - Lấy cây danh mục (cha - con)
 * - Đếm số sản phẩm theo danh mục
 */

class Category extends Model {
    
    protected $table = 'danh_muc';
    protected $primaryKey = 'ID_danh_muc';
    
    /**
     * Override delete để kiểm tra ràng buộc (Safety Logic)
     */
    public function delete($id) {
        // 1. Kiểm tra có danh mục con không
        if ($this->hasChildren($id)) {
            return false;
        }
        
        // 2. Kiểm tra có sản phẩm không
        if ($this->hasProducts($id)) {
            return false; 
        }
        
        return parent::delete($id);
    }
    
    /**
     * ==========================================================================
     * CÂY DANH MỤC (HIERARCHICAL)
     * ==========================================================================
     */
    
    /**
     * Lấy cây danh mục (dùng VIEW: v_danh_muc_cay)
     * 
     * @return array
     * 
     * Format:
     * [
     *   [
     *     'ID_cha' => 1,
     *     'Ten_cha' => 'Sữa các loại',
     *     'children' => [
     *       ['ID_con' => 2, 'Ten_con' => 'Sữa tươi', 'So_san_pham' => 15],
     *       ['ID_con' => 3, 'Ten_con' => 'Sữa bột', 'So_san_pham' => 8]
     *     ]
     *   ]
     * ]
     */
    public function getCategoriesTree() {
        $sql = "SELECT * FROM v_danh_muc_cay ORDER BY Thu_tu_hien_thi";
        $rows = $this->db->query($sql)->fetchAll();
        
        // Nhóm theo danh mục cha
        $tree = [];
        foreach ($rows as $row) {
            $parentId = $row['ID_cha'];
            
            if (!isset($tree[$parentId])) {
                $tree[$parentId] = [
                    'ID_danh_muc' => $row['ID_cha'],
                    'Ten_danh_muc' => $row['Ten_cha'],
                    'children' => []
                ];
            }
            
            // Chỉ thêm con nếu có
            if ($row['ID_con']) {
                $tree[$parentId]['children'][] = [
                    'ID_danh_muc' => $row['ID_con'],
                    'Ten_danh_muc' => $row['Ten_con'],
                    'Thu_tu_hien_thi' => $row['Thu_tu_hien_thi'],
                    'So_san_pham' => $row['So_san_pham']
                ];
            }
        }
        
        return array_values($tree);
    }
    
    /**
     * Lấy tất cả danh mục cha (không có cha)
     * 
     * @return array
     */
    public function getParentCategories() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Danh_muc_cha IS NULL 
                AND Trang_thai = 'active'
                ORDER BY Thu_tu_hien_thi ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Lấy danh mục con theo cha
     * 
     * @param int $parentId
     * @return array
     */
    public function getChildCategories($parentId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Danh_muc_cha = ? 
                AND Trang_thai = 'active'
                ORDER BY Thu_tu_hien_thi ASC";
        
        return $this->db->query($sql, [$parentId])->fetchAll();
    }
    
    /**
     * Lấy danh sách ID danh mục con
     * 
     * @param int $parentId
     * @return array
     */
    public function getChildIds($parentId) {
        $sql = "SELECT ID_danh_muc FROM {$this->table} 
                WHERE Danh_muc_cha = ? 
                AND Trang_thai = 'active'";
        $rows = $this->db->query($sql, [$parentId])->fetchAll();
        return array_column($rows, 'ID_danh_muc');
    }
    
    /**
     * Lấy breadcrumb cho danh mục
     * 
     * @param int $categoryId
     * @return array
     * 
     * VD: ['Sữa các loại', 'Sữa tươi']
     */
    public function getBreadcrumb($categoryId) {
        $breadcrumb = [];
        $category = $this->findById($categoryId);
        
        if (!$category) {
            return $breadcrumb;
        }
        
        // Thêm danh mục hiện tại
        $breadcrumb[] = [
            'id' => $category['ID_danh_muc'],
            'name' => $category['Ten_danh_muc']
        ];
        
        // Nếu có cha, lấy tiếp
        if ($category['Danh_muc_cha']) {
            $parent = $this->findById($category['Danh_muc_cha']);
            if ($parent) {
                array_unshift($breadcrumb, [
                    'id' => $parent['ID_danh_muc'],
                    'name' => $parent['Ten_danh_muc']
                ]);
            }
        }
        
        return $breadcrumb;
    }
    
    /**
     * ==========================================================================
     * ADMIN - QUẢN LÝ DANH MỤC
     * ==========================================================================
     */
    
    /**
     * Lấy tất cả danh mục (cho admin, bao gồm inactive)
     * 
     * @return array
     */
    public function getCategoriesTreeAdmin() {
        $sql = "SELECT 
                    cha.ID_danh_muc as ID_cha,
                    cha.Ten_danh_muc as Ten_cha,
                    cha.Thu_tu_hien_thi as Thu_tu_cha,
                    cha.Trang_thai as Trang_thai_cha,
                    con.ID_danh_muc as ID_con,
                    con.Ten_danh_muc as Ten_con,
                    con.Thu_tu_hien_thi as Thu_tu_con,
                    con.Trang_thai as Trang_thai_con,
                    COUNT(sp.ID_sp) as So_san_pham
                FROM {$this->table} cha
                LEFT JOIN {$this->table} con ON cha.ID_danh_muc = con.Danh_muc_cha
                LEFT JOIN san_pham sp ON con.ID_danh_muc = sp.ID_danh_muc
                WHERE cha.Danh_muc_cha IS NULL
                GROUP BY cha.ID_danh_muc, con.ID_danh_muc
                ORDER BY cha.Thu_tu_hien_thi, con.Thu_tu_hien_thi";
        
        $rows = $this->db->query($sql)->fetchAll();
        
        // Nhóm theo cha
        $tree = [];
        foreach ($rows as $row) {
            $parentId = $row['ID_cha'];
            
            if (!isset($tree[$parentId])) {
                $tree[$parentId] = [
                    'ID_danh_muc' => $row['ID_cha'],
                    'Ten_danh_muc' => $row['Ten_cha'],
                    'Thu_tu_hien_thi' => $row['Thu_tu_cha'],
                    'Trang_thai' => $row['Trang_thai_cha'],
                    'children' => []
                ];
            }
            
            if ($row['ID_con']) {
                $tree[$parentId]['children'][] = [
                    'ID_danh_muc' => $row['ID_con'],
                    'Ten_danh_muc' => $row['Ten_con'],
                    'Thu_tu_hien_thi' => $row['Thu_tu_con'],
                    'Trang_thai' => $row['Trang_thai_con'],
                    'So_san_pham' => $row['So_san_pham']
                ];
            }
        }
        
        return array_values($tree);
    }
     /**
     * Lấy toàn bộ cây danh mục (đa cấp) từ bảng `danh_muc`
     * Trả về mảng phân cấp bất kỳ chiều sâu, dùng cho dropdown
     *
     * @param bool $onlyActive - chỉ lấy danh mục active
     * @return array
     */
    public function getCategoriesNested($onlyActive = true) {
        $sql = "SELECT 
                dm.*,
                COUNT(sp.ID_sp) as So_san_pham
            FROM {$this->table} dm
            LEFT JOIN san_pham sp ON dm.ID_danh_muc = sp.ID_danh_muc 
                AND sp.Trang_thai = 'active'";
    
    if ($onlyActive) {
        $sql .= " WHERE dm.Trang_thai = 'active'";
    }
    
    $sql .= " GROUP BY dm.ID_danh_muc
              ORDER BY dm.Thu_tu_hien_thi ASC, dm.Ten_danh_muc ASC";

    $rows = $this->db->query($sql)->fetchAll();

    // Build map
    $map = [];
    foreach ($rows as $row) {
        $row['children'] = [];
        $map[$row['ID_danh_muc']] = $row;
    }

    // Build tree
    $tree = [];
    foreach ($map as $id => $node) {
        if ($node['Danh_muc_cha']) {
            $parentId = $node['Danh_muc_cha'];
            if (isset($map[$parentId])) {
                $map[$parentId]['children'][] = &$map[$id];
            } else {
                // Parent not found (or inactive), treat as root
                $tree[] = &$map[$id];
            }
        } else {
            $tree[] = &$map[$id];
        }
    }

    return $tree;
}
    /**
     * Lấy danh sách flat (không phân cấp)
     * 
     * @return array
     */
    public function getAllFlat() {
        $sql = "SELECT 
                    dm.*,
                    cha.Ten_danh_muc as Ten_cha,
                    COUNT(sp.ID_sp) as So_san_pham
                FROM {$this->table} dm
                LEFT JOIN {$this->table} cha ON dm.Danh_muc_cha = cha.ID_danh_muc
                LEFT JOIN san_pham sp ON dm.ID_danh_muc = sp.ID_danh_muc
                GROUP BY dm.ID_danh_muc
                ORDER BY dm.Thu_tu_hien_thi ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Kiểm tra danh mục có sản phẩm không
     * 
     * @param int $categoryId
     * @return bool
     */
    public function hasProducts($categoryId) {
        $sql = "SELECT COUNT(*) as total FROM san_pham WHERE ID_danh_muc = ?";
        $result = $this->db->query($sql, [$categoryId])->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Kiểm tra danh mục có danh mục con không
     * 
     * @param int $categoryId
     * @return bool
     */
    public function hasChildren($categoryId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE Danh_muc_cha = ?";
        $result = $this->db->query($sql, [$categoryId])->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Đếm số sản phẩm theo danh mục
     * 
     * @param int $categoryId
     * @return int
     */
    public function countProducts($categoryId) {
        $sql = "SELECT COUNT(*) as total 
                FROM san_pham 
                WHERE ID_danh_muc = ? AND Trang_thai = 'active'";
        
        $result = $this->db->query($sql, [$categoryId])->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Cập nhật thứ tự hiển thị
     * 
     * @param int $categoryId
     * @param int $order
     * @return bool
     */
    public function updateOrder($categoryId, $order) {
        return $this->update($categoryId, ['Thu_tu_hien_thi' => $order]);
    }
    
    /**
     * Cập nhật trạng thái
     * 
     * @param int $categoryId
     * @param string $status
     * @return bool
     */
    public function updateStatus($categoryId, $status) {
        return $this->update($categoryId, ['Trang_thai' => $status]);
    }
    
    /**
     * Lấy danh mục cho dropdown (chỉ danh mục cha)
     * 
     * @return array
     */
    public function getForDropdown() {
        return $this->getParentCategories();
    }
    
    /**
     * ==========================================================================
     * VALIDATION
     * ==========================================================================
     */
    
    /**
     * Kiểm tra tên danh mục đã tồn tại chưa
     * 
     * @param string $name
     * @param int $excludeId ID danh mục cần loại trừ (khi update)
     * @return bool
     */
    public function nameExists($name, $excludeId = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE Ten_danh_muc = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND ID_danh_muc != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Kiểm tra không được chọn chính nó làm cha
     * 
     * @param int $categoryId
     * @param int $parentId
     * @return bool
     */
    public function isValidParent($categoryId, $parentId) {
        // Không được chọn chính nó
        if ($categoryId == $parentId) {
            return false;
        }
        
        // Không được chọn danh mục con của nó
        $children = $this->getChildCategories($categoryId);
        foreach ($children as $child) {
            if ($child['ID_danh_muc'] == $parentId) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * ==========================================================================
     * POS - DANH MỤC CHO POS
     * ==========================================================================
     */
    
    /**
     * Lấy tất cả danh mục active (cho POS filter tabs)
     * 
     * @return array
     */
    public function getAllActive() {
        $sql = "SELECT 
                    ID_danh_muc as ID_dm,
                    Ten_danh_muc as Ten,
                    Thu_tu_hien_thi
                FROM {$this->table}
                WHERE Trang_thai = 'active'
                ORDER BY Thu_tu_hien_thi ASC, Ten_danh_muc ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * ==========================================================================
     * STATISTICS
     * ==========================================================================
     */
    
    /**
     * Thống kê số sản phẩm theo danh mục
     * 
     * @return array
     */
    public function getProductCountByCategory() {
        $sql = "SELECT 
                    dm.ID_danh_muc,
                    dm.Ten_danh_muc,
                    COUNT(sp.ID_sp) as So_san_pham
                FROM {$this->table} dm
                LEFT JOIN san_pham sp ON dm.ID_danh_muc = sp.ID_danh_muc 
                    AND sp.Trang_thai = 'active'
                WHERE dm.Trang_thai = 'active'
                GROUP BY dm.ID_danh_muc
                ORDER BY So_san_pham DESC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Top danh mục bán chạy
     * 
     * @param int $limit
     * @return array
     */
    public function getTopSellingCategories($limit = 5) {
        $sql = "SELECT 
                    dm.ID_danh_muc,
                    dm.Ten_danh_muc,
                    COUNT(DISTINCT ct.ID_dh) as So_don_hang,
                    SUM(ct.So_luong) as Tong_ban,
                    SUM(ct.Thanh_tien) as Doanh_thu
                FROM {$this->table} dm
                INNER JOIN san_pham sp ON dm.ID_danh_muc = sp.ID_danh_muc
                INNER JOIN chi_tiet_don_hang ct ON sp.ID_sp = ct.ID_sp
                WHERE dh.Trang_thai = 'da_giao'
                GROUP BY dm.ID_danh_muc
                ORDER BY Doanh_thu DESC
                LIMIT {$limit}";
        
        return $this->db->query($sql)->fetchAll();
    }
}