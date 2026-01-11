<?php
/**
 * CHECKOUT CONTROLLER - FIXED VERSION
 * Các method được sửa lỗi xung đột
 */

class CheckoutController extends Controller {
    
    private $cartModel;
    private $orderModel;
    private $productModel;
    
    public function __construct() {
        Middleware::customer();
        
        $this->cartModel = $this->model('Cart');
        $this->orderModel = $this->model('Order');
        $this->productModel = $this->model('Product');
    }
  
    public function index() {
        $userId = Session::getUserId();
        $cartItems = [];
        $isDirectCheckout = false;
        
        // =====================================================================
        // KIỂM TRA LUỒNG: DIRECT CHECKOUT hay CART CHECKOUT
        // =====================================================================
        
        $selectedItems = post('items', get('items', ''));
        
        // Ưu tiên 1: Nếu có items từ giỏ hàng -> Xóa Direct Session cũ và xử lý như Cart Checkout
        if (!empty($selectedItems)) {
            if (isset($_SESSION['direct_checkout'])) {
                unset($_SESSION['direct_checkout']);
            }
        }
        
        if (isset($_SESSION['direct_checkout'])) {
            // ===== LUỒNG 1: MUA NGAY (DIRECT CHECKOUT) =====
            $isDirectCheckout = true;
            $directData = $_SESSION['direct_checkout'];
            
            // Lấy thông tin sản phẩm từ database
            $product = $this->productModel->findById($directData['product_id']);
            
            if (!$product) {
                unset($_SESSION['direct_checkout']);
                Session::flash('error', 'Sản phẩm không tồn tại');
                redirect(BASE_URL . '/products');
                return;
            }
            
            // Kiểm tra lại tồn kho
            if ($product['So_luong_ton'] < $directData['quantity']) {
                unset($_SESSION['direct_checkout']);
                Session::flash('error', 'Sản phẩm không đủ số lượng trong kho');
                redirect(BASE_URL . '/products/detail/' . $product['ID_sp']);
                return;
            }
            
            // Tạo mock cart item từ sản phẩm
            $cartItems = [[
                'ID_sp' => $product['ID_sp'],
                'Ten' => $product['Ten'],
                'Gia_tien' => $product['Gia_tien'],
                'So_luong' => $directData['quantity'],
                'Thanh_tien' => $product['Gia_tien'] * $directData['quantity'],
                'Hinh_anh' => $product['Hinh_anh'],
                'So_luong_ton' => $product['So_luong_ton'],
                'Don_vi_tinh' => $product['Don_vi_tinh']
            ]];
            
        } else {
            // ===== LUỒNG 2: CHECKOUT TỪ GIỎ HÀNG =====
            $selectedIds = !empty($selectedItems) ? explode(',', $selectedItems) : [];
            
            if (!empty($selectedIds)) {
                $cartItems = $this->cartModel->getCartItemsByIds($userId, $selectedIds);
            } else {
                $cartItems = $this->cartModel->getCartItems($userId);
            }
            
            if (empty($cartItems)) {
                Session::flash('error', 'Giỏ hàng trống hoặc sản phẩm không hợp lệ.');
                redirect(BASE_URL . '/cart');
                return;
            }
        }
        
        // =====================================================================
        // VALIDATE STOCK CHO TẤT CẢ SẢN PHẨM
        // =====================================================================
        $errors = [];
        foreach ($cartItems as $item) {
            if ($item['So_luong_ton'] == 0) {
                $errors[] = "Sản phẩm '{$item['Ten']}' đã hết hàng";
            } elseif ($item['So_luong'] > $item['So_luong_ton']) {
                $errors[] = "Sản phẩm '{$item['Ten']}' chỉ còn {$item['So_luong_ton']} sản phẩm";
            }
        }
        
        if (!empty($errors)) {
            if ($isDirectCheckout) {
                unset($_SESSION['direct_checkout']);
            }
            Session::flash('error', implode('<br>', $errors));
            redirect(BASE_URL . '/cart');
            return;
        }
        
        // =====================================================================
        // TÍNH TOÁN TỔNG TIỀN
        // =====================================================================
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['Thanh_tien'];
        }
        
        $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
        $total = $subtotal + $shippingFee;
        
        // =====================================================================
        // LẤY THÔNG TIN USER
        // =====================================================================
        $userModel = $this->model('User');
        $user = $userModel->findById($userId);
        
        // =====================================================================
        // CHUẨN BỊ DATA CHO VIEW
        // =====================================================================
        $data = [
            'page_title' => 'Thanh toán - FreshMart',
            'cart_items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'user' => $user,
            'cart_summary' => [
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total
            ],
            'is_direct_checkout' => $isDirectCheckout,
            'selected_ids' => $selectedItems ?? '',
            'categories' => $this->model('Category')->getCategoriesTree(),
            'cart_count' => Session::getCartCount(),
            'is_logged_in' => true,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('customer/checkout', $data);
    }
    
    
    /**
     * METHOD: process() - XỬ LÝ ĐẶT HÀNG
     * 
     * Hỗ trợ cả checkout từ giỏ hàng và direct checkout
     */
    public function process() {
        if (!$this->isMethod('POST')) {
            redirect(BASE_URL . '/checkout');
        }
        
        $userId = Session::getUserId();
        
        if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
            Session::flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            redirect(BASE_URL . '/checkout');
        }
        
        $validation = $this->validate($_POST, [
            'receiver_name' => 'required|max:200',
            'receiver_phone' => 'required|numeric',
            'receiver_address' => 'required'
        ]);
        
        if (!$validation['valid']) {
            $firstError = reset($validation['errors']);
            Session::flash('error', $firstError[0]);
            redirect(BASE_URL . '/checkout');
        }

        // Additional validation for address length
        $address = $this->sanitize(post('receiver_address', ''));
        if (strlen($address) < 10) {
            Session::flash('error', 'Địa chỉ giao hàng phải có ít nhất 10 ký tự.');
            redirect(BASE_URL . '/checkout');
        }
        
        $orderData = [
            'receiver_name' => $this->sanitize(post('receiver_name')),
            'receiver_phone' => $this->sanitize(post('receiver_phone')),
            'receiver_address' => $this->sanitize(post('receiver_address')),
            'note' => $this->sanitize(post('note', '')),
            'selected_ids' => post('selected_ids', '')
        ];
        
        // =====================================================================
        // XÁC ĐỊNH LUỒNG: DIRECT CHECKOUT hay CART CHECKOUT
        // =====================================================================
        $isDirectCheckout = isset($_SESSION['direct_checkout']);
        $cartItems = [];
        $selectedIds = [];
        
        if ($isDirectCheckout) {
            // ===== LUỒNG 1: MUA NGAY (DIRECT CHECKOUT) =====
            $directData = $_SESSION['direct_checkout'];
            $product = $this->productModel->findById($directData['product_id']);
            
            if (!$product || $product['So_luong_ton'] < $directData['quantity']) {
                unset($_SESSION['direct_checkout']);
                Session::flash('error', 'Sản phẩm không khả dụng hoặc không đủ số lượng');
                redirect(BASE_URL . '/products');
                return;
            }
            
            // Tạo mock cart item
            $cartItems = [[
                'ID_sp' => $product['ID_sp'],
                'Ten' => $product['Ten'],
                'Gia_tien' => $product['Gia_tien'],
                'So_luong' => $directData['quantity'],
                'Thanh_tien' => $product['Gia_tien'] * $directData['quantity'],
                'Hinh_anh' => $product['Hinh_anh'],
                'So_luong_ton' => $product['So_luong_ton'],
                'Don_vi_tinh' => $product['Don_vi_tinh']
            ]];
            
        } else {
            // ===== LUỒNG 2: CHECKOUT TỪ GIỎ HÀNG =====
            $selectedIds = !empty($orderData['selected_ids']) ? explode(',', $orderData['selected_ids']) : [];
            
            if (!empty($selectedIds)) {
                $cartItems = $this->cartModel->getCartItemsByIds($userId, $selectedIds);
            } else {
                $cartItems = $this->cartModel->getCartItems($userId);
            }
        }
        
        if (empty($cartItems)) {
            Session::flash('error', 'Giỏ hàng trống');
            redirect(BASE_URL . '/cart');
            return;
        }
        
        foreach ($cartItems as $item) {
            if ($item['So_luong'] > $item['So_luong_ton']) {
                Session::flash('error', "Sản phẩm '{$item['Ten']}' không đủ số lượng trong kho");
                redirect(BASE_URL . '/cart');
                return;
            }
        }
        
        try {
            // =====================================================================
            // TẠO ĐƠN HÀNG
            // =====================================================================
            if ($isDirectCheckout) {
                // Tạo đơn hàng từ direct checkout
                $orderId = $this->orderModel->createOrderFromDirectCheckout(
                    $userId,
                    $orderData['receiver_name'],
                    $orderData['receiver_phone'],
                    $orderData['receiver_address'],
                    $cartItems[0], // Single product
                    $orderData['note']
                );
            } else {
                // Tạo đơn hàng từ giỏ hàng
                $orderId = $this->orderModel->createOrder(
                    $userId,
                    $orderData['receiver_name'],
                    $orderData['receiver_phone'],
                    $orderData['receiver_address'],
                    $orderData['note'],
                    $selectedIds
                );
            }
            
            if (!$orderId) {
                Session::flash('error', 'Đặt hàng thất bại. Vui lòng thử lại.');
                redirect(BASE_URL . '/checkout');
                return;
            }
            
            // =====================================================================
            // XÓA SESSION VÀ CẬP NHẬT CART COUNT
            // =====================================================================
            if ($isDirectCheckout) {
                unset($_SESSION['direct_checkout']);
                // Cart count không thay đổi vì không dùng giỏ hàng
            } else {
                Session::setCartCount(0);
            }
            
            // Log activity
            Middleware::logActivity('checkout', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'type' => $isDirectCheckout ? 'direct' : 'cart'
            ]);
            
            // =====================================================================
            // CHUYỂN HƯỚNG VỀ TRANG ĐƠN HÀNG CỦA TÔI
            // =====================================================================
            Session::flash('success', 'Đặt hàng thành công! Mã đơn hàng: #' . $orderId);
            redirect(BASE_URL . '/orders');
            
        } catch (Exception $e) {
            error_log("Checkout Error: " . $e->getMessage());
            
            if ($isDirectCheckout) {
                unset($_SESSION['direct_checkout']);
            }
            
            Session::flash('error', 'Có lỗi xảy ra trong quá trình đặt hàng. Vui lòng thử lại.');
            redirect(BASE_URL . '/checkout');
        }
    }
    
    /**
     * METHOD: success() - TRANG THÔNG BÁO ĐẶT HÀNG THÀNH CÔNG 
     */
    public function success() {
        $orderId = Session::getFlash('last_order_id');
        
        if (!$orderId) {
            redirect(BASE_URL . '/');
        }
        
        $order = $this->orderModel->findById($orderId);
        
        if (!$order || $order['ID_tk'] != Session::getUserId()) {
            redirect(BASE_URL . '/');
        }
        
        $orderDetails = $this->orderModel->getOrderDetails($orderId);
        
        Session::delete('last_order_id');
        
        $data = [
            'page_title' => 'Đặt hàng thành công - FreshMart',
            'order' => $order,
            'order_details' => $orderDetails,
            'categories' => $this->model('Category')->getCategoriesTree(),
            'cart_count' => 0,
            'is_logged_in' => true
        ];
        
        $this->view('customer/checkout_success', $data);
    }
    
    /**
     * METHOD: validateStock() - KIỂM TRA TỒN KHO (AJAX) - FIXED
     */
    public function validateStock() {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
        }
        
        $userId = Session::getUserId();
        $cartItems = $this->cartModel->getCartItems($userId);
        
        $errors = [];
        
        foreach ($cartItems as $item) {
            if ($item['So_luong_ton'] == 0) {
                $errors[] = [
                    'product_id' => $item['ID_sp'],
                    'product_name' => $item['Ten'],
                    'message' => 'Đã hết hàng'
                ];
            } elseif ($item['So_luong'] > $item['So_luong_ton']) {
                $errors[] = [
                    'product_id' => $item['ID_sp'],
                    'product_name' => $item['Ten'],
                    'message' => "Chỉ còn {$item['So_luong_ton']} sản phẩm"
                ];
            }
        }
        
        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Một số sản phẩm không đủ số lượng trong kho',
                'errors' => $errors
            ]);
        }
        
        $this->json([
            'success' => true,
            'message' => 'Tất cả sản phẩm đều có sẵn'
        ]);
    }
    /**
     * METHOD: updateDirectQuantity() - CẬP NHẬT SỐ LƯỢNG CHO DIRECT CHECKOUT (AJAX)
     */
    public function updateDirectQuantity() {
        try {
            if (!$this->isAjax() || !$this->isMethod('POST')) {
                return $this->json(['error' => 'Invalid request'], 400);
            }
            
            if (!isset($_SESSION['direct_checkout'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Phiên làm việc đã hết hạn'
                ]);
            }
            
            $quantity = (int)post('quantity', 1);
            if ($quantity < 1) $quantity = 1;
            
            $directData = $_SESSION['direct_checkout'];
            $product = $this->productModel->findById($directData['product_id']);
            
            if (!$product) {
                return $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            }
            
            // Check stock
            if ($quantity > $product['So_luong_ton']) {
                return $this->json([
                    'success' => false,
                    'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm",
                    'max_quantity' => $product['So_luong_ton']
                ]);
            }
            
            // Update session
            $_SESSION['direct_checkout']['quantity'] = $quantity;
            
            // Calculate totals
            $itemTotal = $quantity * $product['Gia_tien'];
            $shippingFee = ($itemTotal >= 150000) ? 0 : 20000;
            $total = $itemTotal + $shippingFee;
            
            return $this->json([
                'success' => true,
                'message' => 'Đã cập nhật số lượng',
                'item_total' => $itemTotal,
                'item_total_formatted' => formatPrice($itemTotal),
                'subtotal' => $itemTotal, // For direct checkout, subtotal is item total
                'subtotal_formatted' => formatPrice($itemTotal),
                'shipping_fee' => $shippingFee,
                'shipping_fee_formatted' => ($shippingFee == 0) ? 'Miễn phí' : formatPrice($shippingFee),
                'total' => $total,
                'total_formatted' => formatPrice($total)
            ]);
            
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
        }
    
        /**
     * METHOD: updateCartQuantity() - CẬP NHẬT SỐ LƯỢNG KHI CHECKOUT TỪ GIỎ (AJAX)
     * Đảm bảo tính toán lại chỉ cho các item đang selected
     */
    public function updateCartQuantity() {
        try {
            if (!$this->isAjax() || !$this->isMethod('POST')) {
                return $this->json(['error' => 'Invalid request'], 400);
            }
            
            $userId = Session::getUserId();
            $cartId = (int)post('cart_id');
            $quantity = (int)post('quantity', 1);
            $selectedIds = post('selected_ids', ''); // DS ID đang checkout
            
            if ($quantity < 1) $quantity = 1;
            
            // 1. Cập nhật vào DB
            $cartItem = $this->cartModel->getCartItemById($cartId);
            if (!$cartItem || $cartItem['ID_tk'] != $userId) {
                return $this->json(['success' => false, 'message' => 'Lỗi quyền truy cập']);
            }
            
            $product = $this->productModel->findById($cartItem['ID_sp']);
            if ($quantity > $product['So_luong_ton']) {
                return $this->json([
                    'success' => false, 
                    'message' => "Chỉ còn {$product['So_luong_ton']} sản phẩm"
                ]);
            }
            
            $this->cartModel->updateQuantity($cartId, $quantity);
            
            // 2. Tính lại tổng tiền dựa trên Selected IDs
            $cartItems = [];
            $idsArray = !empty($selectedIds) ? explode(',', $selectedIds) : [];
            
            if (!empty($idsArray)) {
                $cartItems = $this->cartModel->getCartItemsByIds($userId, $idsArray);
            } else {
                // Nếu không có selected_ids, giả định là checkout toàn bộ (fallback)
                $cartItems = $this->cartModel->getCartItems($userId);
            }
            
            $subtotal = 0;
            $itemTotal = 0;
            
            foreach ($cartItems as $item) {
                $subtotal += $item['Thanh_tien'];
                if ($item['ID_gio'] == $cartId) {
                    $itemTotal = $item['Thanh_tien'];
                }
            }
            
            $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
            $total = $subtotal + $shippingFee;
            
            return $this->json([
                'success' => true,
                'message' => 'Đã cập nhật',
                'item_total' => $itemTotal,
                'item_total_formatted' => formatPrice($itemTotal),
                'subtotal' => $subtotal,
                'subtotal_formatted' => formatPrice($subtotal),
                'shipping_fee' => $shippingFee,
                'shipping_fee_formatted' => ($shippingFee == 0) ? 'Miễn phí' : formatPrice($shippingFee),
                'total' => $total,
                'total_formatted' => formatPrice($total)
            ]);
            
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra']);
        }
    }
}
