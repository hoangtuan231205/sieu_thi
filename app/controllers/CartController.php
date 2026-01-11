<?php


class CartController extends Controller {
    
    private $cartModel;
    private $productModel;
    private $logger;
    
    public function __construct() {
        // Middleware::customer() sẽ redirect, không phù hợp cho AJAX
        // Kiểm tra ở từng method thay vì constructor
        
        $this->cartModel = $this->model('Cart');
        $this->productModel = $this->model('Product');
        $this->logger = new Logger('cart');
    }
    
    /**
     * ==========================================================================
     * METHOD: index() - XEM GIỎ HÀNG
     * ==========================================================================
     */
    public function index() {
        try {
            $userId = Session::getUserId();
            
            // Lấy items trong giỏ
            $cartItems = $this->cartModel->getCartItems($userId);
            
            // Tính toán subtotal và total items
            $subtotal = 0;
            $totalItems = 0;
            
            foreach ($cartItems as $item) {
                $subtotal += $item['Thanh_tien'];
                $totalItems += $item['So_luong'];
            }
            
            // Tính phí vận chuyển
            $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
            $total = $subtotal + $shippingFee;
            
            // Kiểm tra tình trạng kho hàng
            $stockStatus = $this->validateCartStock($cartItems);
            
            // Prepare data cho view
            $data = [
                'page_title' => 'Giỏ hàng - FreshMart',
                'cart_items' => $cartItems,
                'subtotal' => $subtotal,
                'subtotal_formatted' => formatPrice($subtotal),
                'shipping_fee' => $shippingFee,
                'shipping_fee_formatted' => formatPrice($shippingFee),
                'total' => $total,
                'total_formatted' => formatPrice($total),
                'total_items' => $totalItems,
                'has_out_of_stock' => $stockStatus['has_out_of_stock'],
                'has_insufficient_stock' => $stockStatus['has_insufficient_stock'],
                'can_checkout' => $stockStatus['can_checkout'] && count($cartItems) > 0,
                'categories' => $this->model('Category')->getCategoriesTree(),
                'cart_count' => Session::getCartCount(),
                'is_logged_in' => true
            ];
            
            $this->view('customer/cart', $data);
            
        } catch (Exception $e) {
            $this->logger->error('Error loading cart: ' . $e->getMessage());
            Session::flash('error', 'Có lỗi xảy ra khi tải giỏ hàng');
            redirect('/');
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: add() - THÊM SẢN PHẨM VÀO GIỎ (AJAX)
     * ==========================================================================
     */
    
    public function add() {
        try {
            // ===== BƯỚC 0: KIỂM TRA ĐĂNG NHẬP =====
            if (!Session::isLoggedIn()) {
                $this->logger->warning('Add to cart failed - User not logged in');
                return $this->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng'
                ], 401);
            }
            
            // ===== BƯỚC 1: KIỂM TRA REQUEST =====
            if (!$this->isAjax() || !$this->isMethod('POST')) {
                $this->logger->warning('Add to cart failed - Invalid request method', [
                    'is_ajax' => $this->isAjax(),
                    'method' => $_SERVER['REQUEST_METHOD']
                ]);
                return $this->json(['error' => 'Invalid request'], 400);
            }
            
            // ===== BƯỚC 2: VERIFY CSRF TOKEN ✅ FIX =====
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                $this->logger->warning('Add to cart failed - Invalid CSRF token');
                return $this->json([
                    'success' => false,
                    'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'
                ], 403);
            }
            
            // ===== BƯỚC 3: RATE LIMITING ✅ FIX =====
            $userId = Session::getUserId();
            $rateLimitKey = 'cart_add_' . $userId;
            
            if (!Middleware::rateLimit($rateLimitKey, 10, 60)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Bạn đang thao tác quá nhanh. Vui lòng đợi 1 phút.'
                ]);
            }
            
            // ===== BƯỚC 4: LẤY VÀ VALIDATE INPUT =====
            $productId = (int)post('product_id', 0);
            $quantity = (int)post('quantity', 1);
            
            // Validate product ID
            if (!$productId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm không hợp lệ'
                ]);
            }
            
            // ✅ FIX: Validate quantity max
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            if ($quantity > 999) {
                return $this->json([
                    'success' => false,
                    'message' => 'Số lượng tối đa là 999'
                ]);
            }
            
            // ===== BƯỚC 5: KIỂM TRA SẢN PHẨM TỒN TẠI =====
            $product = $this->productModel->findById($productId);
            
            if (!$product) {
                $this->logger->warning("Add to cart failed - Product not found: $productId");
                return $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ]);
            }
            
            // ===== BƯỚC 6: VALIDATE TRẠNG THÁI SẢN PHẨM =====
            if ($product['Trang_thai'] !== 'active') {
                return $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm tạm thời không khả dụng'
                ]);
            }
            
            // ===== BƯỚC 7: KIỂM TRA KHO HÀNG =====
            if ($product['So_luong_ton'] < 1) {
                return $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm đã hết hàng'
                ]);
            }
            
            $result = $this->addToCartWithLock($userId, $productId, $product, $quantity);
            
            // If success, include cart_id in response
            if (isset($result['success']) && $result['success']) {
                $existingItem = $this->cartModel->getCartItem($userId, $productId);
                if ($existingItem) {
                    $result['cart_id'] = $existingItem['ID_gio'];
                }
            }
            
            return $this->json($result);
            
        } catch (Exception $e) {
            $this->logger->error('Error adding to cart: ' . $e->getMessage(), [
                'user_id' => Session::getUserId(),
                'product_id' => $productId ?? 0,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
        }
    }
private function addToCartWithLock($userId, $productId, $product, $quantity) {
        $db = Database::getInstance();
        
        try {
            // ===== BƯỚC 1: BEGIN TRANSACTION =====
            $db->beginTransaction();
            
            // ===== BƯỚC 2: LOCK PRODUCT ROW ✅ FIX RACE CONDITION =====
            $sql = "SELECT So_luong_ton FROM san_pham WHERE ID_sp = ? FOR UPDATE";
            $lockedProduct = $db->query($sql, [$productId])->fetch();
            
            if (!$lockedProduct) {
                $db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }
            
            // ===== BƯỚC 3: KIỂM TRA SẢN PHẨM ĐÃ CÓ TRONG GIỎ CHƯA =====
            $existingItem = $this->cartModel->getCartItem($userId, $productId);
            
            if ($existingItem) {
                // Sản phẩm đã có → tính tổng số lượng
                $newQuantity = $existingItem['So_luong'] + $quantity;
                
                // Kiểm tra vượt quá tồn kho
                if ($newQuantity > $lockedProduct['So_luong_ton']) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Chỉ còn {$lockedProduct['So_luong_ton']} sản phẩm trong kho",
                        'max_quantity' => $lockedProduct['So_luong_ton']
                    ];
                }
                
                // Cập nhật số lượng
                $this->cartModel->updateQuantity($existingItem['ID_gio'], $newQuantity);
                
                // Commit transaction
                $db->commit();
                
                // Update cart count
                $cartCount = $this->cartModel->getCartCount($userId);
                Session::setCartCount($cartCount);
                
                $this->logger->info("Updated cart item {$existingItem['ID_gio']} for user $userId");
                
                return [
                    'success' => true,
                    'message' => 'Đã cập nhật số lượng trong giỏ hàng',
                    'cart_count' => $cartCount
                ];
                
            } else {
                // Sản phẩm mới → kiểm tra số lượng
                if ($quantity > $lockedProduct['So_luong_ton']) {
                    $db->rollBack();
                    return [
                        'success' => false,
                        'message' => "Chỉ còn {$lockedProduct['So_luong_ton']} sản phẩm trong kho",
                        'max_quantity' => $lockedProduct['So_luong_ton']
                    ];
                }
                
                // Thêm vào giỏ
                $this->cartModel->addToCart($userId, $productId, $quantity);
                
                // Commit transaction
                $db->commit();
                
                // Update cart count
                $cartCount = $this->cartModel->getCartCount($userId);
                Session::setCartCount($cartCount);
                
                $this->logger->info("Added product $productId to cart for user $userId");
                
                return [
                    'success' => true,
                    'message' => 'Đã thêm vào giỏ hàng',
                    'cart_count' => $cartCount
                ];
            }
            
        } catch (Exception $e) {
            // Rollback on error
            if ($db->pdo->inTransaction()) {
                $db->rollBack();
            }
            
            throw $e; // Re-throw to be caught by parent
        }
    }
    

    
    /**
     * Helper: Cập nhật item đã tồn tại trong giỏ
     */
    private function updateExistingItem($existingItem, $product, $quantity, $userId) {
        $newQuantity = $existingItem['So_luong'] + $quantity;
        
        // Kiểm tra vượt quá tồn kho
        if ($newQuantity > $product['So_luong_ton']) {
            return $this->json([
                'success' => false,
                'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm trong kho",
                'max_quantity' => $product['So_luong_ton']
            ]);
        }
        
        // Cập nhật số lượng
        $result = $this->cartModel->updateQuantity($existingItem['ID_gio'], $newQuantity);
        
        if ($result) {
            // Cập nhật cart count trong session (unique items)
            $cartCount = $this->cartModel->getCartCount($userId);
            Session::setCartCount($cartCount);
            
            // Log activity
            $this->logger->info("Updated cart item {$existingItem['ID_gio']} for user $userId");
            
            return $this->json([
                'success' => true,
                'message' => 'Đã cập nhật số lượng trong giỏ hàng',
                'cart_count' => $cartCount
            ]);
        }
        
        return $this->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
        ]);
    }
    
    /**
     * Helper: Thêm item mới vào giỏ
     */
    private function addNewItem($userId, $productId, $product, $quantity) {
        // Kiểm tra số lượng yêu cầu
        if ($quantity > $product['So_luong_ton']) {
            return $this->json([
                'success' => false,
                'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm trong kho",
                'max_quantity' => $product['So_luong_ton']
            ]);
        }
        
        // Thêm vào giỏ
        $result = $this->cartModel->addToCart($userId, $productId, $quantity);
        
        if ($result) {
            // Cập nhật cart count trong session
            $cartCount = $this->cartModel->getCartCount($userId);
            Session::setCartCount($cartCount);
            
            // Log activity
            $this->logger->info("Added product $productId to cart for user $userId");
            
            return $this->json([
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cartCount
            ]);
        }
        
        return $this->json([
            'success' => false,
            'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
        ]);
    }
    public function update() {
        try {
            // ===== KIỂM TRA REQUEST =====
            if (!$this->isAjax() || !$this->isMethod('POST')) {
                return $this->json(['error' => 'Invalid request'], 400);
            }
            
            // ===== VERIFY CSRF TOKEN ✅ FIX =====
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                return $this->json([
                    'success' => false,
                    'message' => 'Phiên làm việc không hợp lệ'
                ], 403);
            }
            
            // ===== RATE LIMITING ✅ FIX =====
            $userId = Session::getUserId();
            $rateLimitKey = 'cart_update_' . $userId;
            
            if (!Middleware::rateLimit($rateLimitKey, 20, 60)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Bạn đang thao tác quá nhanh'
                ]);
            }
            
            // ===== VALIDATE INPUT =====
            $cartId = (int)post('cart_id', 0);
            $quantity = (int)post('quantity', 1);
            
            if (!$cartId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ]);
            }
            
            // ✅ FIX: Validate quantity
            if ($quantity < 1) {
                $quantity = 1;
            }
            
            if ($quantity > 999) {
                return $this->json([
                    'success' => false,
                    'message' => 'Số lượng tối đa là 999'
                ]);
            }
            
            // ===== KIỂM TRA QUYỀN SỞ HỮU =====
            $cartItem = $this->cartModel->getCartItemById($cartId);
            
            if (!$cartItem || $cartItem['ID_tk'] != $userId) {
                $this->logger->warning("Unauthorized cart update attempt", [
                    'user_id' => $userId,
                    'cart_id' => $cartId
                ]);
                
                return $this->json([
                    'success' => false,
                    'message' => 'Không có quyền thao tác'
                ]);
            }
            
            // ===== KIỂM TRA SẢN PHẨM =====
            $product = $this->productModel->findById($cartItem['ID_sp']);
            
            if (!$product || $product['Trang_thai'] !== 'active') {
                return $this->json([
                    'success' => false,
                    'message' => 'Sản phẩm không khả dụng'
                ]);
            }
            
            // ===== KIỂM TRA TỒN KHO =====
            if ($quantity > $product['So_luong_ton']) {
                return $this->json([
                    'success' => false,
                    'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm trong kho",
                    'max_quantity' => $product['So_luong_ton']
                ]);
            }
            
            // ===== CẬP NHẬT SỐ LƯỢNG =====
            $result = $this->cartModel->updateQuantity($cartId, $quantity);
            
            if ($result) {
                // Tính toán lại giá trị
                $itemTotal = $quantity * $product['Gia_tien'];
                
                $cartCount = $this->cartModel->getCartCount($userId);
                Session::setCartCount($cartCount);
                
                $cartSummary = $this->cartModel->getCartSummary($userId);
                
                $this->logger->info("Updated cart item $cartId quantity to $quantity");
                
                return $this->json([
                    'success' => true,
                    'message' => 'Đã cập nhật số lượng',
                    'item_total' => $itemTotal,
                    'item_total_formatted' => formatPrice($itemTotal),
                    'cart_count' => $cartCount,
                    'subtotal' => $cartSummary['subtotal'],
                    'subtotal_formatted' => formatPrice($cartSummary['subtotal']),
                    'shipping_fee' => $cartSummary['shipping_fee'],
                    'shipping_fee_formatted' => formatPrice($cartSummary['shipping_fee']),
                    'total' => $cartSummary['total'],
                    'total_formatted' => formatPrice($cartSummary['total'])
                ]);
            }
            
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Error updating cart: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
        }
    }
    public function remove() {
        try {
            // ===== KIỂM TRA REQUEST =====
            if (!$this->isAjax() || !$this->isMethod('POST')) {
                return $this->json(['error' => 'Invalid request'], 400);
            }
            
            // ===== VERIFY CSRF TOKEN ✅ FIX =====
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                return $this->json([
                    'success' => false,
                    'message' => 'Phiên làm việc không hợp lệ'
                ], 403);
            }
            
            $userId = Session::getUserId();
            $cartId = (int)post('cart_id', 0);
            
            if (!$cartId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ]);
            }
            
            // ===== KIỂM TRA QUYỀN SỞ HỮU =====
            $cartItem = $this->cartModel->getCartItemById($cartId);
            
            if (!$cartItem || $cartItem['ID_tk'] != $userId) {
                $this->logger->warning("Unauthorized cart remove attempt", [
                    'user_id' => $userId,
                    'cart_id' => $cartId
                ]);
                
                return $this->json([
                    'success' => false,
                    'message' => 'Không có quyền thao tác'
                ]);
            }
            
            // ===== XÓA SẢN PHẨM =====
            $result = $this->cartModel->removeFromCart($cartId);
            
            if ($result) {
                $cartCount = $this->cartModel->getCartCount($userId);
                Session::setCartCount($cartCount);
                
                $cartSummary = $this->cartModel->getCartSummary($userId);
                
                $this->logger->info("Removed cart item $cartId for user $userId");
                
                return $this->json([
                    'success' => true,
                    'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
                    'cart_count' => $cartCount,
                    'subtotal' => $cartSummary['subtotal'],
                    'subtotal_formatted' => formatPrice($cartSummary['subtotal']),
                    'shipping_fee' => $cartSummary['shipping_fee'],
                    'shipping_fee_formatted' => formatPrice($cartSummary['shipping_fee']),
                    'total' => $cartSummary['total'],
                    'total_formatted' => formatPrice($cartSummary['total'])
                ]);
            }
            
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Error removing from cart: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
            ]);
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: clear() - XÓA TOÀN BỘ GIỎ HÀNG
     * ==========================================================================
     */
    public function clear() {
        try {
            $userId = Session::getUserId();
            
            $result = $this->cartModel->clearCart($userId);
            
            if ($result) {
                Session::setCartCount(0);
                $this->logger->info("Cleared cart for user $userId");
                Session::flash('success', 'Đã xóa toàn bộ giỏ hàng');
            } else {
                Session::flash('error', 'Có lỗi xảy ra');
            }
            
            redirect('/cart');
            
        } catch (Exception $e) {
            $this->logger->error('Error clearing cart: ' . $e->getMessage());
            Session::flash('error', 'Có lỗi xảy ra');
            redirect('/cart');
        }
    }
    
    
    /**
     * ==========================================================================
     * METHOD: buyNow() - MUA NGAY (DIRECT CHECKOUT)
     * ==========================================================================
     * 
     * Chức năng: Lưu sản phẩm vào session và chuyển hướng thẳng đến trang thanh toán
     * CHỈ MUA 1 SẢN PHẨM (qty=1), không phải toàn bộ giỏ hàng
     */
    public function buyNow() {
        try {
            // ===== BƯỚC 1: KIỂM TRA ĐĂNG NHẬP =====
            if (!Session::isLoggedIn()) {
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Vui lòng đăng nhập để mua hàng'
                    ], 401);
                }
                Session::flash('error', 'Vui lòng đăng nhập để mua hàng');
                redirect('/auth/login');
                return;
            }
            
            // ===== BƯỚC 2: KIỂM TRA REQUEST METHOD =====
            if (!$this->isMethod('POST')) {
                if ($this->isAjax()) {
                    return $this->json(['error' => 'Invalid request'], 400);
                }
                redirect('/products');
                return;
            }
            
            // ===== BƯỚC 3: VERIFY CSRF TOKEN =====
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Phiên làm việc không hợp lệ'
                    ], 403);
                }
                Session::flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
                redirect('/products');
                return;
            }
            
            // ===== BƯỚC 4: LẤY VÀ VALIDATE INPUT =====
            $productId = (int)post('product_id', 0);
            
            // CRITICAL FIX: Allow dynamic quantity from User Input, default to 1
            $quantity = (int)post('quantity', 1);
            if ($quantity < 1) $quantity = 1;
            
            // Validate product ID
            if (!$productId) {
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sản phẩm không hợp lệ'
                    ]);
                }
                Session::flash('error', 'Sản phẩm không hợp lệ');
                redirect('/products');
                return;
            }
            
            // ===== BƯỚC 5: KIỂM TRA SẢN PHẨM TỒN TẠI =====
            $product = $this->productModel->findById($productId);
            
            if (!$product) {
                $this->logger->warning("Buy now failed - Product not found: $productId");
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sản phẩm không tồn tại'
                    ]);
                }
                Session::flash('error', 'Sản phẩm không tồn tại');
                redirect('/products');
                return;
            }
            
            // ===== BƯỚC 6: VALIDATE TRẠNG THÁI SẢN PHẨM =====
            if ($product['Trang_thai'] !== 'active') {
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sản phẩm tạm thời không khả dụng'
                    ]);
                }
                Session::flash('error', 'Sản phẩm tạm thời không khả dụng');
                redirect('/products/detail/' . $productId);
                return;
            }
            
            // ===== BƯỚC 7: KIỂM TRA KHO HÀNG =====
            if ($product['So_luong_ton'] < 1) {
                if ($this->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Sản phẩm đã hết hàng'
                    ]);
                }
                Session::flash('error', 'Sản phẩm đã hết hàng');
                redirect('/products/detail/' . $productId);
                return;
            }
            
            // ===== BƯỚC 8: LƯU VÀO SESSION DIRECT_CHECKOUT =====
            $_SESSION['direct_checkout'] = [
                'product_id' => $productId,
                'quantity' => $quantity, // Always 1
                'timestamp' => time()
            ];
            
            // Log activity
            $this->logger->info("User initiated buy now for product $productId (qty=1)", [
                'user_id' => Session::getUserId(),
                'product_id' => $productId
            ]);
            
            // Ensure session is written before redirect
            session_write_close();
            
            // ===== BƯỚC 9: RETURN SUCCESS FOR AJAX =====
            if ($this->isAjax()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Đang chuyển đến trang thanh toán...'
                ]);
            }
            
            // Fallback redirect for non-AJAX
            redirect('/checkout');
            
        } catch (Exception $e) {
            $this->logger->error('Error in buyNow: ' . $e->getMessage(), [
                'user_id' => Session::getUserId(),
                'product_id' => $productId ?? 0,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($this->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra. Vui lòng thử lại.'
                ]);
            }
            
            Session::flash('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
            redirect('/products');
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: count() - ĐẾM SỐ LƯỢNG ITEMS TRONG GIỎ (AJAX)
     * ==========================================================================
     */

    public function count() {
        try {
            if (!$this->isAjax()) {
                $this->json(['error' => 'Invalid request'], 400);
                return;
            }
            
            // Kiểm tra đăng nhập
            if (!Session::isLoggedIn()) {
                $this->json([
                    'success' => false,
                    'count' => 0
                ]);
                return;
            }
            
            $userId = Session::getUserId();
            $count = $this->cartModel->getCartCount($userId);
            
            $this->json([
                'success' => true,
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Error counting cart items: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra'
            ]);
        }
    }
    
    /**
     * ==========================================================================
     * HELPER: Kiểm tra tình trạng kho hàng của giỏ
     * ==========================================================================
     */
    private function validateCartStock($cartItems) {
        $hasOutOfStock = false;
        $hasInsufficientStock = false;
        
        foreach ($cartItems as $item) {
            if ($item['So_luong_ton'] == 0) {
                $hasOutOfStock = true;
            }
            if ($item['So_luong'] > $item['So_luong_ton']) {
                $hasInsufficientStock = true;
            }
        }
        
        return [
            'has_out_of_stock' => $hasOutOfStock,
            'has_insufficient_stock' => $hasInsufficientStock,
            'can_checkout' => !$hasOutOfStock && !$hasInsufficientStock
        ];
    }
}