<?php
/**
 * =============================================================================
 * CART MODEL - GIỎ HÀNG
 * =============================================================================
 * 
 * Bảng: gio_hang
 * 
 * Chức năng:
 * - Thêm/Xóa/Cập nhật sản phẩm trong giỏ
 * - Lấy thông tin giỏ hàng
 * - Tính tổng tiền
 */

class Cart extends Model {
    
    protected $table = 'gio_hang';
    protected $primaryKey = 'ID_gio';
    
    /**
     * ==========================================================================
     * CART OPERATIONS
     * ==========================================================================
     */
    
    /**
     * Lấy giỏ hàng của user (dùng VIEW: v_gio_hang_chi_tiet)
     * 
     * @param int $userId
     * @return array
     */
    public function getCartItems($userId) {
        $sql = "SELECT gh.*, sp.Ten, sp.Gia_tien, sp.Hinh_anh, sp.So_luong_ton, sp.Don_vi_tinh, 
                       (gh.So_luong * sp.Gia_tien) as Thanh_tien
                FROM gio_hang gh
                JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ? 
                ORDER BY gh.Ngay_them DESC";
        
        return $this->db->query($sql, [$userId])->fetchAll();
    }

    /**
     * Lấy danh sách items cụ thể trong giỏ hàng
     */
    public function getCartItemsByIds($userId, $cartIds) {
        if (empty($cartIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $sql = "SELECT gh.*, sp.Ten, sp.Gia_tien, sp.Hinh_anh, sp.So_luong_ton, sp.Don_vi_tinh, 
                       (gh.So_luong * sp.Gia_tien) as Thanh_tien
                FROM gio_hang gh
                JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ? AND gh.ID_gio IN ($placeholders)
                ORDER BY gh.Ngay_them DESC";
        
        $params = array_merge([$userId], $cartIds);
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Lấy 1 item trong giỏ hàng
     * 
     * @param int $userId
     * @param int $productId
     * @return array|null
     */
    public function getCartItem($userId, $productId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE ID_tk = ? AND ID_sp = ? 
                LIMIT 1";
        
        return $this->db->query($sql, [$userId, $productId])->fetch();
    }
    
    /**
     * Lấy cart item theo ID
     * 
     * @param int $cartId
     * @return array|null
     */
    public function getCartItemById($cartId) {
        return $this->findById($cartId);
    }
    
    /**
     * Thêm sản phẩm vào giỏ
     * 
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @return int|false Cart ID
     */
    public function addToCart($userId, $productId, $quantity = 1) {
        // Kiểm tra đã có trong giỏ chưa
        $existing = $this->getCartItem($userId, $productId);
        
        if ($existing) {
            // Đã có → cập nhật số lượng
            $newQuantity = $existing['So_luong'] + $quantity;
            $this->updateQuantity($existing['ID_gio'], $newQuantity);
            return $existing['ID_gio'];
        } else {
            // Chưa có → thêm mới
            return $this->create([
                'ID_tk' => $userId,
                'ID_sp' => $productId,
                'So_luong' => $quantity
            ]);
        }
    }
    
    /**
     * Cập nhật số lượng
     * 
     * @param int $cartId
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity($cartId, $quantity) {
        if ($quantity <= 0) {
            return $this->delete($cartId);
        }
        
        return $this->update($cartId, ['So_luong' => $quantity]);
    }
    
    /**
     * Xóa sản phẩm khỏi giỏ
     * 
     * @param int $cartId
     * @return bool
     */
    public function removeFromCart($cartId) {
        return $this->delete($cartId);
    }
    
    /**
     * Xóa toàn bộ giỏ hàng
     * 
     * @param int $userId
     * @return bool
     */
    public function clearCart($userId) {
        $sql = "DELETE FROM {$this->table} WHERE ID_tk = ?";
        $this->db->query($sql, [$userId]);
        
        return $this->db->rowCount() > 0;
    }
    
    /**
     * ==========================================================================
     * CALCULATIONS
     * ==========================================================================
     */
    
    /**
     * Đếm số items trong giỏ (tổng số lượng tất cả sản phẩm)
     * 
     * @param int $userId
     * @return int
     */
    public function getCartCount($userId) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} gh
                JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ? AND sp.Trang_thai = 'active'";
        $result = $this->db->query($sql, [$userId])->fetch();
        
        return (int) $result['total'];
    }
    
    /**
     * Tính tổng số lượng sản phẩm (tổng cộng số lượng)
     * 
     * @param int $userId
     * @return int
     */
    public function getTotalQuantity($userId) {
        $sql = "SELECT SUM(So_luong) as total FROM {$this->table} WHERE ID_tk = ?";
        $result = $this->db->query($sql, [$userId])->fetch();
        
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Tính tổng tiền giỏ hàng
     * 
     * @param int $userId
     * @return array ['subtotal', 'shipping_fee', 'total']
     */
    public function getCartSummary($userId) {
        $sql = "SELECT 
                    SUM(sp.Gia_tien * gh.So_luong) as subtotal
                FROM {$this->table} gh
                INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ? AND sp.Trang_thai = 'active'";
        
        $result = $this->db->query($sql, [$userId])->fetch();
        
        $subtotal = (float) ($result['subtotal'] ?? 0);
        
        // Phí vận chuyển: 20.000đ, miễn phí nếu đơn >= 150.000đ
        $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
        
        $total = $subtotal + $shippingFee;
        
        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total
        ];
    }
    
    /**
     * ==========================================================================
     * VALIDATION
     * ==========================================================================
     */
    
    /**
     * Kiểm tra giỏ hàng có sản phẩm hết hàng không
     * 
     * @param int $userId
     * @return array Danh sách sản phẩm có vấn đề
     */
    public function validateCart($userId) {
        $sql = "SELECT 
                    gh.ID_gio,
                    gh.ID_sp,
                    sp.Ten,
                    gh.So_luong,
                    sp.So_luong_ton,
                    CASE
                        WHEN sp.So_luong_ton = 0 THEN 'out_of_stock'
                        WHEN gh.So_luong > sp.So_luong_ton THEN 'insufficient_stock'
                        ELSE 'ok'
                    END as status
                FROM {$this->table} gh
                INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ?";
        
        $items = $this->db->query($sql, [$userId])->fetchAll();
        
        $errors = [];
        foreach ($items as $item) {
            if ($item['status'] !== 'ok') {
                $errors[] = [
                    'cart_id' => $item['ID_gio'],
                    'product_id' => $item['ID_sp'],
                    'product_name' => $item['Ten'],
                    'requested_quantity' => $item['So_luong'],
                    'available_quantity' => $item['So_luong_ton'],
                    'status' => $item['status']
                ];
            }
        }
        
        return $errors;
    }
    
    /**
     * Kiểm tra sản phẩm có thể thêm vào giỏ không
     * 
     * @param int $userId
     * @param int $productId
     * @param int $quantity
     * @return array ['valid' => bool, 'message' => string]
     */
    public function canAddToCart($userId, $productId, $quantity) {
        // Lấy thông tin sản phẩm
        $sql = "SELECT So_luong_ton, Trang_thai FROM san_pham WHERE ID_sp = ?";
        $product = $this->db->query($sql, [$productId])->fetch();
        
        if (!$product) {
            return ['valid' => false, 'message' => 'Sản phẩm không tồn tại'];
        }
        
        if ($product['Trang_thai'] !== 'active') {
            return ['valid' => false, 'message' => 'Sản phẩm không khả dụng'];
        }
        
        if ($product['So_luong_ton'] < 1) {
            return ['valid' => false, 'message' => 'Sản phẩm đã hết hàng'];
        }
        
        // Kiểm tra số lượng đã có trong giỏ
        $existing = $this->getCartItem($userId, $productId);
        $currentQuantity = $existing ? $existing['So_luong'] : 0;
        $totalQuantity = $currentQuantity + $quantity;
        
        if ($totalQuantity > $product['So_luong_ton']) {
            return [
                'valid' => false, 
                'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm trong kho"
            ];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }
    
    /**
     * Tự động điều chỉnh số lượng nếu vượt quá tồn kho
     * 
     * @param int $userId
     * @return int Số items đã điều chỉnh
     */
    public function adjustQuantities($userId) {
        $sql = "SELECT 
                    gh.ID_gio,
                    gh.So_luong,
                    sp.So_luong_ton
                FROM {$this->table} gh
                INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.ID_tk = ? AND gh.So_luong > sp.So_luong_ton";
        
        $items = $this->db->query($sql, [$userId])->fetchAll();
        
        $adjusted = 0;
        foreach ($items as $item) {
            if ($item['So_luong_ton'] > 0) {
                // Điều chỉnh về số lượng tồn kho
                $this->updateQuantity($item['ID_gio'], $item['So_luong_ton']);
                $adjusted++;
            } else {
                // Xóa khỏi giỏ nếu hết hàng
                $this->removeFromCart($item['ID_gio']);
                $adjusted++;
            }
        }
        
        return $adjusted;
    }
    
    /**
     * ==========================================================================
     * UTILITIES
     * ==========================================================================
     */
    
    /**
     * Merge giỏ hàng (khi user đăng nhập)
     * Dùng khi có session cart → merge vào database cart
     * 
     * @param int $userId
     * @param array $sessionCart
     * @return bool
     */
    public function mergeCart($userId, $sessionCart) {
        if (empty($sessionCart)) {
            return true;
        }
        
        $this->beginTransaction();
        
        try {
            foreach ($sessionCart as $item) {
                $this->addToCart(
                    $userId, 
                    $item['product_id'], 
                    $item['quantity']
                );
            }
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollBack();
            return false;
        }
    }
    
    /**
     * Xóa items hết hạn (quá 30 ngày chưa checkout)
     * 
     * @return int Số items đã xóa
     */
    public function cleanupExpiredCarts() {
        $sql = "DELETE FROM {$this->table} 
                WHERE Ngay_them < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $this->db->query($sql);
        
        return $this->db->rowCount();
    }
    
    /**
     * Lấy giỏ hàng bị bỏ quên (abandoned carts)
     * 
     * @param int $days Số ngày chưa thay đổi
     * @return array
     */
    public function getAbandonedCarts($days = 7) {
        $sql = "SELECT 
                    tk.ID,
                    tk.Email,
                    tk.Ho_ten,
                    COUNT(gh.ID_gio) as So_items,
                    SUM(sp.Gia_tien * gh.So_luong) as Gia_tri,
                    MAX(gh.Ngay_them) as Lan_them_cuoi
                FROM {$this->table} gh
                INNER JOIN tai_khoan tk ON gh.ID_tk = tk.ID
                INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                WHERE gh.Ngay_them < DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY gh.ID_tk
                HAVING So_items > 0
                ORDER BY Gia_tri DESC";
        
        return $this->db->query($sql, [$days])->fetchAll();
    }
    
    /**
     * Thống kê giỏ hàng
     * 
     * @return array
     */
    public function getCartStatistics() {
        $sql = "SELECT 
                    COUNT(DISTINCT ID_tk) as So_nguoi_co_gio,
                    COUNT(*) as Tong_items,
                    AVG(items_per_user) as TB_items_moi_nguoi,
                    AVG(cart_value) as Gia_tri_TB
                FROM (
                    SELECT 
                        gh.ID_tk,
                        COUNT(*) as items_per_user,
                        SUM(sp.Gia_tien * gh.So_luong) as cart_value
                    FROM {$this->table} gh
                    INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
                    GROUP BY gh.ID_tk
                ) as cart_summary";
        
        return $this->db->query($sql)->fetch();
    }
}