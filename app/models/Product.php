<?php
/**
 * =============================================================================
 * PRODUCT MODEL - QUẢN LÝ SẢN PHẨM
 * =============================================================================
 * 
 * Bảng: san_pham
 * 
 * Chức năng:
 * - CRUD sản phẩm
 * - Filter, search, sort
 * - Quản lý tồn kho
 * - Thống kê bán chạy
 */

class Product extends Model {
    
    protected $table = 'san_pham';
    protected $primaryKey = 'ID_sp';
    
    /**
     * Override delete để kiểm tra ràng buộc (Safety Logic)
     */
    public function delete($id) {
        // 1. Kiểm tra có trong đơn hàng không
        if ($this->hasOrders($id)) {
            return false; // Không thể xóa vì có đơn hàng
        }
        
        // 2. Kiểm tra có trong kho không (tồn > 0)
        // Nếu tồn > 0 thì không cho xóa (phải hủy trước để đảm bảo audit trail)
        $product = $this->findById($id);
        if ($product && $product['So_luong_ton'] > 0) {
            return false; 
        }
        
        return parent::delete($id);
    }
    
    /**
     * User ID hiện tại (cho trigger ghi log)
     */
    private $currentUserId = null;
    
    /**
     * Set current user ID (cho trigger)
     */
    public function setCurrentUserId($userId) {
        $this->currentUserId = $userId;
        
        // Set biến session MySQL để trigger sử dụng
        if ($userId) {
            $this->db->query("SET @current_user_id = ?", [$userId]);
        }
    }
    private $allowedOrderBy = [
        'newest' => 'sp.Ngay_tao DESC',
        'oldest' => 'sp.Ngay_tao ASC',
        'price_asc' => 'sp.Gia_tien ASC',
        'price_desc' => 'sp.Gia_tien DESC',
        'name_asc' => 'sp.Ten ASC',
        'name_desc' => 'sp.Ten DESC',
        'bestseller' => '(SELECT IFNULL(SUM(So_luong), 0) FROM chi_tiet_don_hang WHERE ID_sp = sp.ID_sp) DESC',
        'stock_asc' => 'sp.So_luong_ton ASC',
        'stock_desc' => 'sp.So_luong_ton DESC'
    ];
    
    /**
     * Kiểm tra keyword có thực sự match trong text hay không
     * Phân biệt dấu tiếng Việt chính xác (cá != cải != cà)
     * 
     * @param string $text
     * @param string $keyword
     * @return bool
     */
    private function vietnameseMatch($text, $keyword) {
        $text = mb_strtolower($text, 'UTF-8');
        $keyword = mb_strtolower($keyword, 'UTF-8');
        return mb_strpos($text, $keyword, 0, 'UTF-8') !== false;
    }
    
    /**
     * ==========================================================================
     * DANH SÁCH SẢN PHẨM (CUSTOMER)
     * ==========================================================================
     */
    
    /**
     * Lấy danh sách sản phẩm (có filter)
     * 
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProducts($filters = [], $orderBy = 'newest', $limit = 12, $offset = 0) {
        // ===== BƯỚC 1: VALIDATE ORDER BY ✅ FIX =====
        // Nếu orderBy không hợp lệ → dùng default
        if (!isset($this->allowedOrderBy[$orderBy])) {
            $orderBy = 'newest';
        }
        
        // Lấy SQL ORDER BY an toàn từ whitelist
        $orderBySql = $this->allowedOrderBy[$orderBy];
        
        // ===== BƯỚC 2: XÂY DỰNG QUERY =====
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE sp.Trang_thai = 'active'";
        
        $params = [];
        
        // ===== BƯỚC 3: THÊM FILTERS ✅ IMPROVED =====
        
        // Filter theo danh mục (bao gồm cả danh mục con)
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $catId = (int)$filters['category_id'];
            $sql .= " AND (sp.ID_danh_muc = ? OR sp.ID_danh_muc IN (SELECT ID_danh_muc FROM danh_muc WHERE Danh_muc_cha = ?))";
            $params[] = $catId;
            $params[] = $catId;
        }
        
        // Filter theo khoảng giá
        if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND sp.Gia_tien >= ?";
            $params[] = (float)$filters['min_price'];
        }
        
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND sp.Gia_tien <= ?";
            $params[] = (float)$filters['max_price'];
        }
        
        // Search theo tên SẢN PHẨM HOẶC tên DANH MỤC
        if (!empty($filters['keyword'])) {
            // Sanitize keyword
            $keyword = trim($filters['keyword']);
            if (strlen($keyword) > 0) {
                // Search both product name AND category name
                // This allows "mì" to find all products in "Mì" category
                $sql .= " AND (sp.Ten LIKE ? OR dm.Ten_danh_muc LIKE ?)";
                $params[] = '%' . $keyword . '%';
                $params[] = '%' . $keyword . '%';
            }
        }
        
        // ===== BƯỚC 4: THÊM ORDER BY (AN TOÀN) ✅ FIX =====
        $sql .= " ORDER BY {$orderBySql}";
        
        // ===== BƯỚC 5: THÊM LIMIT & OFFSET ✅ FIX =====
        // Validate limit & offset
        // Lấy nhiều hơn để bù cho việc filter bỏ false positives
        $fetchLimit = !empty($filters['keyword']) ? max(1, min((int)$limit * 3, 300)) : max(1, min((int)$limit, 100));
        $offset = max(0, (int)$offset);
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $fetchLimit;
        $params[] = $offset;
        
        // ===== BƯỚC 6: EXECUTE QUERY =====
        $results = $this->db->query($sql, $params)->fetchAll();
        
        // ===== BƯỚC 7: FILTER VIETNAMESE DIACRITICS ✅ NEW =====
        // Lọc lại kết quả bằng PHP để phân biệt dấu tiếng Việt chính xác
        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
            $filteredResults = [];
            
            foreach ($results as $product) {
                $matchInName = $this->vietnameseMatch($product['Ten'], $keyword);
                $matchInCategory = $this->vietnameseMatch($product['Ten_danh_muc'] ?? '', $keyword);
                
                if ($matchInName || $matchInCategory) {
                    $filteredResults[] = $product;
                }
            }
            
            // Giới hạn lại theo limit gốc
            return array_slice($filteredResults, 0, (int)$limit);
        }
        
        return $results;
    }
    
    /**
     * Đếm số lượng sản phẩm
     * 
     * @param array $filters
     * @return int
     */
    public function countProducts($filters = []) {
        // Nếu có keyword, cần đếm chính xác với Vietnamese filter
        if (!empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
            if (strlen($keyword) > 0) {
                // Query tất cả sản phẩm matching và đếm bằng PHP
                $sql = "SELECT sp.Ten, dm.Ten_danh_muc
                        FROM {$this->table} sp
                        LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                        WHERE sp.Trang_thai = 'active'
                        AND (sp.Ten LIKE ? OR dm.Ten_danh_muc LIKE ?)";
                
                $params = ['%' . $keyword . '%', '%' . $keyword . '%'];
                
                // Add other filters
                if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
                    $catId = (int)$filters['category_id'];
                    $sql .= " AND (sp.ID_danh_muc = ? OR sp.ID_danh_muc IN (SELECT ID_danh_muc FROM danh_muc WHERE Danh_muc_cha = ?))";
                    $params[] = $catId;
                    $params[] = $catId;
                }
                
                if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
                    $sql .= " AND sp.Gia_tien >= ?";
                    $params[] = (float)$filters['min_price'];
                }
                
                if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
                    $sql .= " AND sp.Gia_tien <= ?";
                    $params[] = (float)$filters['max_price'];
                }
                
                $results = $this->db->query($sql, $params)->fetchAll();
                
                // Filter với Vietnamese match
                $count = 0;
                foreach ($results as $product) {
                    $matchInName = $this->vietnameseMatch($product['Ten'], $keyword);
                    $matchInCategory = $this->vietnameseMatch($product['Ten_danh_muc'] ?? '', $keyword);
                    
                    if ($matchInName || $matchInCategory) {
                        $count++;
                    }
                }
                
                return $count;
            }
        }
        
        // Không có keyword - đếm bình thường
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} sp
                WHERE sp.Trang_thai = 'active'";
        
        $params = [];
        
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $catId = (int)$filters['category_id'];
            $sql .= " AND (sp.ID_danh_muc = ? OR sp.ID_danh_muc IN (SELECT ID_danh_muc FROM danh_muc WHERE Danh_muc_cha = ?))";
            $params[] = $catId;
            $params[] = $catId;
        }
        
        if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
            $sql .= " AND sp.Gia_tien >= ?";
            $params[] = (float)$filters['min_price'];
        }
        
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $sql .= " AND sp.Gia_tien <= ?";
            $params[] = (float)$filters['max_price'];
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Lấy khoảng giá (min-max)
     * 
     * @return array
     */
    public function getPriceRange() {
        $sql = "SELECT 
                    MIN(Gia_tien) as min_price,
                    MAX(Gia_tien) as max_price
                FROM {$this->table}
                WHERE Trang_thai = 'active'";
        
        return $this->db->query($sql)->fetch();
    }
    
    /**
     * Tìm kiếm sản phẩm (LIKE search - more compatible)
     * 
     * @param string $keyword
     * @param int $limit
     * @return array
     */
        public function search($keyword, $limit = 10) {
        // ===== VALIDATE INPUT ✅ FIX =====
        $keyword = trim($keyword);
        
        if (strlen($keyword) < 2) {
            return []; // Keyword quá ngắn
        }
        
        if (strlen($keyword) > 200) {
            $keyword = substr($keyword, 0, 200); // Giới hạn độ dài
        }
        
        // Validate limit
        $limit = max(1, min((int)$limit, 50)); // Max 50 results
        
        // ===== EXECUTE QUERY =====
        $searchTerm = '%' . $keyword . '%';
        
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE sp.Trang_thai = 'active'
                    AND (sp.Ten LIKE ? OR sp.Mo_ta_sp LIKE ? OR sp.Ma_hien_thi LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN sp.Ten LIKE ? THEN 1
                        WHEN sp.Ma_hien_thi LIKE ? THEN 2
                        ELSE 3
                    END,
                    sp.Ngay_tao DESC
                LIMIT ?";
        
        return $this->db->query($sql, [
            $searchTerm, 
            $searchTerm, 
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $limit
        ])->fetchAll();
    }
    
    /**
     * Lấy sản phẩm liên quan (cùng danh mục)
     * 
     * @param int $productId
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getRelatedProducts($productId, $categoryId, $limit = 8) {
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE sp.ID_danh_muc = ?
                    AND sp.ID_sp != ?
                    AND sp.Trang_thai = 'active'
                ORDER BY RAND()
                LIMIT {$limit}";
        
        return $this->db->query($sql, [$categoryId, $productId])->fetchAll();
    }
    
    /**
     * Lấy sản phẩm mới nhất
     * 
     * @param int $limit
     * @return array
     */
    public function getLatestProducts($limit = 12) {
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE sp.Trang_thai = 'active'
                ORDER BY sp.Ngay_tao DESC
                LIMIT {$limit}";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Lấy sản phẩm bán chạy (từ VIEW: v_san_pham_ban_chay)
     * Fallback to latest products if no orders exist yet
     * 
     * @param int $limit
     * @return array
     */
    public function getBestSellers($limit = 8) {
        $sql = "SELECT 
                    sp.*,
                    SUM(ct.So_luong) as Da_ban
                FROM {$this->table} sp
                JOIN chi_tiet_don_hang ct ON sp.ID_sp = ct.ID_sp
                JOIN don_hang dh ON ct.ID_dh = dh.ID_dh
                WHERE dh.Trang_thai = 'da_giao' AND sp.Trang_thai = 'active'
                GROUP BY sp.ID_sp
                ORDER BY Da_ban DESC
                LIMIT {$limit}";
                
        $bestsellers = $this->db->query($sql)->fetchAll();
        
        // If no bestsellers (no orders yet), fallback to latest products
        if (empty($bestsellers)) {
            return $this->getLatestProducts($limit);
        }
        
        return $bestsellers;
    }
    
    /**
     * ==========================================================================
     * ADMIN - QUẢN LÝ SẢN PHẨM
     * ==========================================================================
     */
    
    /**
     * Lấy danh sách sản phẩm cho admin (bao gồm inactive)
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductsForAdmin($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE 1=1";
        
        $params = [];
        $hasKeyword = !empty($filters['keyword']);
        $keyword = $hasKeyword ? trim($filters['keyword']) : '';
        
        // ===== FILTERS =====
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $sql .= " AND sp.ID_danh_muc = ?";
            $params[] = (int)$filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            $allowedStatus = ['active', 'inactive'];
            if (in_array($filters['status'], $allowedStatus)) {
                $sql .= " AND sp.Trang_thai = ?";
                $params[] = $filters['status'];
            }
        }
        
        // SQL LIKE for initial filtering - includes category name!
        if ($hasKeyword && strlen($keyword) > 0 && strlen($keyword) <= 200) {
            $sql .= " AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ? OR dm.Ten_danh_muc LIKE ?)";
            $searchTerm = '%' . $keyword . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // ===== ORDER BY =====
        $sql .= " ORDER BY sp.Ngay_tao DESC";
        
        // ===== LIMIT (fetch more if keyword to allow PHP filtering) =====
        $fetchLimit = $hasKeyword ? max(1, min((int)$limit * 5, 500)) : max(1, min((int)$limit, 100));
        $fetchOffset = $hasKeyword ? 0 : max(0, (int)$offset);
        
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $fetchLimit;
        $params[] = $fetchOffset;
        
        $results = $this->db->query($sql, $params)->fetchAll();
        
        // ===== VIETNAMESE DIACRITICS FILTER (PHP-side) =====
        if ($hasKeyword && strlen($keyword) > 0) {
            $filteredResults = [];
            
            foreach ($results as $product) {
                $matchInName = $this->vietnameseMatch($product['Ten'], $keyword);
                $matchInCode = $this->vietnameseMatch($product['Ma_hien_thi'] ?? '', $keyword);
                $matchInCategory = $this->vietnameseMatch($product['Ten_danh_muc'] ?? '', $keyword);
                
                if ($matchInName || $matchInCode || $matchInCategory) {
                    $filteredResults[] = $product;
                }
            }
            
            // Apply offset and limit after filtering
            return array_slice($filteredResults, (int)$offset, (int)$limit);
        }
        
        return $results;
    }

    /**
     * Đếm số lượng sản phẩm cho Admin (bao gồm active/inactive)
     * Matches getProductsForAdmin logic
     * 
     * @param array $filters
     * @return int
     */
    public function countProductsForAdmin($filters = []) {
        $hasKeyword = !empty($filters['keyword']);
        $keyword = $hasKeyword ? trim($filters['keyword']) : '';
        
        // If keyword search, need PHP-side filtering for Vietnamese accuracy
        if ($hasKeyword && strlen($keyword) > 0 && strlen($keyword) <= 200) {
            // Join with danh_muc to also search category name
            $sql = "SELECT sp.Ten, sp.Ma_hien_thi, dm.Ten_danh_muc 
                    FROM {$this->table} sp
                    LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                    WHERE 1=1";
            $params = [];
            
            // Apply non-keyword filters
            if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
                $sql .= " AND sp.ID_danh_muc = ?";
                $params[] = (int)$filters['category_id'];
            }
            
            if (!empty($filters['status'])) {
                $allowedStatus = ['active', 'inactive'];
                if (in_array($filters['status'], $allowedStatus)) {
                    $sql .= " AND sp.Trang_thai = ?";
                    $params[] = $filters['status'];
                }
            }
            
            // SQL LIKE for initial filtering - includes category!
            $sql .= " AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ? OR dm.Ten_danh_muc LIKE ?)";
            $searchTerm = '%' . $keyword . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            
            $results = $this->db->query($sql, $params)->fetchAll();
            
            // Vietnamese diacritics filtering in PHP
            $count = 0;
            foreach ($results as $product) {
                $matchInName = $this->vietnameseMatch($product['Ten'], $keyword);
                $matchInCode = $this->vietnameseMatch($product['Ma_hien_thi'] ?? '', $keyword);
                $matchInCategory = $this->vietnameseMatch($product['Ten_danh_muc'] ?? '', $keyword);
                
                if ($matchInName || $matchInCode || $matchInCategory) {
                    $count++;
                }
            }
            
            return $count;
        }
        
        // No keyword - simple SQL count
        $sql = "SELECT COUNT(*) as total FROM {$this->table} sp WHERE 1=1";
        $params = [];
        
        if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
            $sql .= " AND sp.ID_danh_muc = ?";
            $params[] = (int)$filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            $allowedStatus = ['active', 'inactive'];
            if (in_array($filters['status'], $allowedStatus)) {
                $sql .= " AND sp.Trang_thai = ?";
                $params[] = $filters['status'];
            }
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Kiểm tra sản phẩm có trong đơn hàng chưa
     * 
     * @param int $productId
     * @return bool
     */
    public function hasOrders($productId) {
        $sql = "SELECT COUNT(*) as total FROM chi_tiet_don_hang WHERE ID_sp = ?";
        $result = $this->db->query($sql, [$productId])->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Lấy tất cả sản phẩm (cho dropdown select)
     * 
     * @return array
     */
    public function getAllProducts() {
        $sql = "SELECT ID_sp, Ma_hien_thi, Ten, Don_vi_tinh, So_luong_ton
                FROM {$this->table}
                WHERE Trang_thai = 'active'
                ORDER BY Ten ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Xuất Excel sản phẩm
     * 
     * @return array
     */
    public function getAllProductsForExport() {
        $sql = "SELECT 
                    sp.Ma_hien_thi,
                    sp.Ten,
                    dm.Ten_danh_muc,
                    sp.Gia_tien,
                    sp.So_luong_ton,
                    sp.Don_vi_tinh,
                    sp.Xuat_xu,
                    sp.Trang_thai,
                    sp.Ngay_tao
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                ORDER BY sp.Ngay_tao DESC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * ==========================================================================
     * WAREHOUSE - QUẢN LÝ TỒN KHO
     * ==========================================================================
     */
    
    /**
     * Lấy sản phẩm sắp hết hàng (từ VIEW: v_san_pham_sap_het)
     * 
     * @param int $limit
     * @return array
     */
    public function getLowStockProducts($limit = 20) {
        $sql = "SELECT * FROM v_san_pham_sap_het LIMIT {$limit}";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Đếm số sản phẩm sắp hết hàng (tồn <= 10)
     * 
     * @return int
     */
    public function getLowStockCount() {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE So_luong_ton <= 10 AND Trang_thai = 'active'";
        
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Đếm số sản phẩm hết hàng
     * 
     * @return int
     */
    public function getOutOfStockCount() {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE So_luong_ton = 0 AND Trang_thai = 'active'";
        
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Tổng giá trị tồn kho
     * 
     * @return float
     */
    public function getTotalInventoryValue() {
        $sql = "SELECT SUM(So_luong_ton * Gia_tien) as total 
                FROM {$this->table} 
                WHERE Trang_thai = 'active'";
        
        $result = $this->db->query($sql)->fetch();
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Lấy tồn kho (có filter)
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getInventory($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT 
                    sp.*,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND sp.ID_danh_muc = ?";
            $params[] = $filters['category_id'];
        }
        
        // Filter theo trạng thái tồn kho
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'low') {
                $sql .= " AND sp.So_luong_ton > 0 AND sp.So_luong_ton <= 10";
            } elseif ($filters['status'] === 'out') {
                $sql .= " AND sp.So_luong_ton = 0";
            }
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $sql .= " ORDER BY sp.So_luong_ton ASC, sp.Ten ASC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Đếm tồn kho
     * 
     * @param array $filters
     * @return int
     */
    public function countInventory($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND ID_danh_muc = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'low') {
                $sql .= " AND So_luong_ton > 0 AND So_luong_ton <= 10";
            } elseif ($filters['status'] === 'out') {
                $sql .= " AND So_luong_ton = 0";
            }
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (Ten LIKE ? OR Ma_hien_thi LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Cập nhật tồn kho
     * 
     * @param int $productId
     * @param int $newStock
     * @return bool
     */
    public function updateStock($productId, $newStock) {
        return $this->update($productId, ['So_luong_ton' => $newStock]);
    }
    
    /**
     * Xuất Excel tồn kho
     * 
     * @return array
     */
    public function getAllInventoryForExport() {
        $sql = "SELECT 
                    sp.Ma_hien_thi,
                    sp.Ten,
                    dm.Ten_danh_muc,
                    sp.So_luong_ton,
                    sp.Don_vi_tinh,
                    sp.Gia_tien,
                    sp.Trang_thai
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                ORDER BY sp.So_luong_ton ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Tìm kiếm sản phẩm (cho warehouse/phiếu nhập/phiếu hủy)
     * Có hỗ trợ tiếng Việt: cá ≠ cải, rau ≠ râu
     * 
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function searchForWarehouse($keyword, $limit = 20) {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 1) return [];
        
        // Fetch more for PHP filtering
        $fetchLimit = max($limit * 5, 100);
        
        $sql = "SELECT 
                    sp.ID_sp,
                    sp.Ma_hien_thi,
                    sp.Ten,
                    sp.Don_vi_tinh,
                    sp.So_luong_ton,
                    sp.Gia_tien AS gia,
                    dm.Ten_danh_muc
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                WHERE (sp.Ten LIKE ? OR sp.Ma_hien_thi LIKE ? OR dm.Ten_danh_muc LIKE ?)
                    AND sp.Trang_thai = 'active'
                ORDER BY sp.Ten ASC
                LIMIT {$fetchLimit}";
        
        $searchTerm = '%' . $keyword . '%';
        $results = $this->db->query($sql, [$searchTerm, $searchTerm, $searchTerm])->fetchAll();
        
        // Vietnamese diacritics filter
        $kwLower = mb_strtolower($keyword, 'UTF-8');
        $filtered = [];
        
        foreach ($results as $row) {
            $tenLower = mb_strtolower($row['Ten'] ?? '', 'UTF-8');
            $maLower = mb_strtolower($row['Ma_hien_thi'] ?? '', 'UTF-8');
            $dmLower = mb_strtolower($row['Ten_danh_muc'] ?? '', 'UTF-8');
            
            if ($this->vietnameseMatch($row['Ten'] ?? '', $keyword) ||
                $this->vietnameseMatch($row['Ma_hien_thi'] ?? '', $keyword) ||
                $this->vietnameseMatch($row['Ten_danh_muc'] ?? '', $keyword)) {
                $filtered[] = $row;
                if (count($filtered) >= $limit) break;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Tìm kiếm sản phẩm cho phiếu hủy
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function searchForDisposal($keyword, $limit = 20) {
        return $this->searchForWarehouse($keyword, $limit);
    }
    
    /**
     * ==========================================================================
     * STATISTICS
     * ==========================================================================
     */
    
    /**
     * Tổng số sản phẩm
     * 
     * @return int
     */
    public function getTotalProducts() {
        return $this->count(['Trang_thai' => 'active']);
    }
    
    /**
     * Báo cáo sản phẩm
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getProductReport($dateFrom, $dateTo) {
        $sql = "SELECT 
                    sp.ID_sp,
                    sp.Ma_hien_thi,
                    sp.Ten,
                    dm.Ten_danh_muc,
                    sp.So_luong_ton,
                    IFNULL(SUM(ct.So_luong), 0) as So_luong_ban,
                    IFNULL(SUM(ct.Thanh_tien), 0) as Doanh_thu
                FROM {$this->table} sp
                LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
                LEFT JOIN chi_tiet_don_hang ct ON sp.ID_sp = ct.ID_sp
                LEFT JOIN don_hang dh ON ct.ID_dh = dh.ID_dh 
                    AND dh.Trang_thai = 'da_giao'
                    AND DATE(dh.Ngay_dat) BETWEEN ? AND ?
                WHERE sp.Trang_thai = 'active'
                GROUP BY sp.ID_sp
                ORDER BY Doanh_thu DESC";
        
        return $this->db->query($sql, [$dateFrom, $dateTo])->fetchAll();
    }
    
    /**
     * Tìm kiếm sản phẩm cho POS (theo tên hoặc mã)
     * 
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function searchForPOS($keyword, $limit = 10) {
        $keyword = trim($keyword);
        
        if (strlen($keyword) < 1) {
            return [];
        }
        
        $searchTerm = '%' . $keyword . '%';
        $limit = max(1, min((int)$limit, 30));
        
        $sql = "SELECT 
                    ID_sp,
                    Ma_hien_thi,
                    Ten,
                    Gia_tien,
                    So_luong_ton,
                    Don_vi_tinh,
                    Hinh_anh
                FROM {$this->table}
                WHERE Trang_thai = 'active'
                    AND So_luong_ton > 0
                    AND (Ten LIKE ? OR Ma_hien_thi LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN Ma_hien_thi LIKE ? THEN 1
                        ELSE 2
                    END,
                    Ten ASC
                LIMIT ?";
        
        return $this->db->query($sql, [
            $searchTerm, 
            $searchTerm,
            $searchTerm,
            $limit
        ])->fetchAll();
    }
    
    /**
     * Get active products for POS grid display
     * 
     * @param int $limit
     * @return array
     */
    public function getActiveProductsForPOS($limit = 20) {
        $sql = "SELECT 
                    ID_sp,
                    Ma_hien_thi,
                    Ten,
                    Gia_tien,
                    So_luong_ton,
                    Don_vi_tinh,
                    Hinh_anh,
                    ID_danh_muc
                FROM {$this->table}
                WHERE Trang_thai = 'active'
                    AND So_luong_ton > 0
                ORDER BY Ngay_tao DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->fetchAll();
    }
    
    /**
     * Get products by category for POS
     * 
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getProductsByCategory($categoryId, $limit = 30) {
        $sql = "SELECT 
                    ID_sp,
                    Ma_hien_thi,
                    Ten,
                    Gia_tien,
                    So_luong_ton,
                    Don_vi_tinh,
                    Hinh_anh,
                    ID_danh_muc
                FROM {$this->table}
                WHERE Trang_thai = 'active'
                    AND So_luong_ton > 0
                    AND ID_danh_muc = ?
                ORDER BY Ten ASC
                LIMIT ?";
        
        return $this->db->query($sql, [$categoryId, $limit])->fetchAll();
    }


    
    /**
     * Lấy danh sách các lô hàng còn tồn của sản phẩm (FEFO)
     * 
     * @param int $productId
     * @return array
     */
    public function getBatches($productId) {
        // Updated to use display code from phieu_nhap_kho table
        $sql = "SELECT 
                    ct.ID_chi_tiet_nhap,
                    pn.Ma_hien_thi as Ma_phieu_nhap,
                    ct.So_luong_con,
                    ct.Don_gia_nhap,
                    ct.Ngay_het_han
                FROM chi_tiet_phieu_nhap ct
                JOIN phieu_nhap_kho pn ON ct.ID_phieu_nhap = pn.ID_phieu_nhap
                WHERE ct.ID_sp = ? 
                AND ct.So_luong_con > 0
                ORDER BY 
                    CASE WHEN ct.Ngay_het_han IS NULL THEN 1 ELSE 0 END, 
                    ct.Ngay_het_han ASC, 
                    ct.ID_chi_tiet_nhap ASC";
        
        return $this->db->query($sql, [$productId])->fetchAll();
    }
    /**
     * ==========================================================================
     * ADMIN CRUD METHODS
     * ==========================================================================
     */

    /**
     * Lấy sản phẩm theo ID
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE ID_sp = ?", [$id])->fetch();
    }

    /**
     * Thêm sản phẩm mới
     * 
     * @param array $data
     * @return int|false ID sản phẩm mới
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (
                    Ten, 
                    Ma_hien_thi, 
                    ID_danh_muc, 
                    Gia_tien, 
                    So_luong_ton, 
                    Don_vi_tinh, 
                    Mo_ta_sp,
                    Hinh_anh, 
                    Trang_thai,
                    Xuat_xu,
                    Thanh_phan,
                    Ngay_tao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['Ten'],
            $data['Ma_hien_thi'] ?? null,
            $data['ID_danh_muc'],
            $data['Gia_tien'],
            $data['So_luong_ton'] ?? 0,
            $data['Don_vi_tinh'],
            $data['Mo_ta_sp'] ?? null,
            $data['Hinh_anh'] ?? null,
            $data['Trang_thai'] ?? 'active',
            $data['Xuat_xu'] ?? null,
            $data['Thanh_phan'] ?? null
        ];
        
        return $this->db->insert($sql, $params);
    }

    /**
     * Cập nhật sản phẩm
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */


    /**
     * Xóa sản phẩm
     * 
     * @param int $id
     * @return bool
     */



}