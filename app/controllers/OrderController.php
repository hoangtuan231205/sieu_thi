<?php
/**
 * ORDER CONTROLLER - FIXED VERSION
 * Các method được sửa lỗi xung đột
 */

class OrderController extends Controller {
    
    private $orderModel;
    
    public function __construct() {
        Middleware::customer();
        $this->orderModel = $this->model('Order');
    }
    
    /**
     * METHOD: index() - LỊCH SỬ ĐƠN HÀNG - FIXED
     */
    public function index() {
        $userId = Session::getUserId();
        
        $filters = [
            'status' => get('status', ''),
            'page' => max(1, (int)get('page', 1))
        ];
        
        $totalOrders = $this->orderModel->countUserOrders($userId, $filters['status']);
        
        $perPage = 10;
        $pagination = $this->paginate($totalOrders, $perPage, $filters['page']);
        
        $orders = $this->orderModel->getUserOrders(
            $userId,
            $filters['status'],
            $pagination['per_page'],
            $pagination['offset']
        );
        
        // Attach snippets for thumbnails
        foreach ($orders as &$order) {
            $order['details'] = $this->orderModel->getOrderDetails($order['ID_dh']);
        }
        
        $statusCounts = [
            'all' => $this->orderModel->countUserOrders($userId, ''),
            'dang_xu_ly' => $this->orderModel->countUserOrders($userId, 'dang_xu_ly'),
            'dang_giao' => $this->orderModel->countUserOrders($userId, 'dang_giao'),
            'da_giao' => $this->orderModel->countUserOrders($userId, 'da_giao'),
            'huy' => $this->orderModel->countUserOrders($userId, 'huy')
        ];
        
        $data = [
            'page_title' => 'Đơn hàng của tôi - FreshMart',
            'orders' => $orders,
            'filters' => $filters,
            'pagination' => $pagination,
            'status_counts' => $statusCounts,
            'categories' => $this->model('Category')->getCategoriesTree(),
            'cart_count' => Session::getCartCount(),  // ✅ FIXED
            'is_logged_in' => true
        ];
        
        $this->view('customer/orders', $data);
    }
    
    /**
     * METHOD: detail() - CHI TIẾT ĐƠN HÀNG - FIXED
     */
    public function detail($orderId = null) {
        if (!$orderId || !is_numeric($orderId)) {
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect(BASE_URL . '/orders');
        }
        
        $userId = Session::getUserId();
        
        $order = $this->orderModel->findById($orderId);
        
        if (!$order) {
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect(BASE_URL . '/orders');
        }
        
        if ($order['ID_tk'] != $userId) {
            Session::flash('error', 'Bạn không có quyền xem đơn hàng này');
            redirect(BASE_URL . '/orders');
        }
        
        $orderDetails = $this->orderModel->getOrderDetails($orderId);
        
        $timeline = $this->orderModel->getOrderTimeline($order);
        
        $canCancel = $order['Trang_thai'] === 'dang_xu_ly';
        
        $data = [
            'page_title' => 'Chi tiết đơn hàng #' . $orderId . ' - FreshMart',
            'order' => $order,
            'order_details' => $orderDetails,
            'timeline' => $timeline,
            'can_cancel' => $canCancel,
            'categories' => $this->model('Category')->getCategoriesTree(),
            'cart_count' => Session::getCartCount(),  // ✅ FIXED
            'is_logged_in' => true
        ];
        
        $this->view('customer/order_detail', $data);
    }
    
    /**
     * METHOD: cancel() - HỦY ĐƠN HÀNG - FIXED
     */
    public function cancel($orderId = null) {
        if (!$this->isMethod('POST')) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Phương thức không hợp lệ'], 400);
                return;
            }
            redirect(BASE_URL . '/orders');
        }

        // Lấy order_id từ JSON body vì AJAX request gửi qua body
        if (!$orderId) {
            $data = json_decode(file_get_contents('php://input'), true);
            $orderId = $data['order_id'] ?? null;
        }
        
        if (!$orderId || !is_numeric($orderId)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Đơn hàng không tồn tại'], 400);
                return;
            }
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect(BASE_URL . '/orders');
        }
        
        $userId = Session::getUserId();
        
        $order = $this->orderModel->findById($orderId);
        
        if (!$order) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Đơn hàng không tồn tại'], 404);
                return;
            }
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect(BASE_URL . '/orders');
        }
        
        if ($order['ID_tk'] != $userId) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Bạn không có quyền hủy đơn hàng này'], 403);
                return;
            }
            Session::flash('error', 'Bạn không có quyền hủy đơn hàng này');
            redirect(BASE_URL . '/orders');
        }
        
        if ($order['Trang_thai'] !== 'dang_xu_ly') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Chỉ có thể hủy đơn hàng đang chờ xác nhận'], 400);
                return;
            }
            Session::flash('error', 'Chỉ có thể hủy đơn hàng đang chờ xác nhận');
            redirect(BASE_URL . '/orders/detail/' . $orderId);
        }
        
        $result = $this->orderModel->cancelOrder($orderId);
        
        if ($result) {
            Middleware::logActivity('cancel_order', [
                'user_id' => $userId,
                'order_id' => $orderId
            ]);
            
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Đã hủy đơn hàng #' . $orderId . ' thành công', 'order_id' => $orderId]);
                return;
            }
            Session::flash('success', 'Đã hủy đơn hàng thành công');
        } else {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Hủy đơn hàng thất bại. Vui lòng thử lại.'], 500);
                return;
            }
            Session::flash('error', 'Hủy đơn hàng thất bại. Vui lòng thử lại.');
        }
        
        redirect(BASE_URL . '/orders/detail/' . $orderId);
    }
    
    /**
     * METHOD: track() - THEO DÕI ĐƠN HÀNG (AJAX) - FIXED
     */
    public function track($orderId = null) {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
        }
        
        if (!$orderId || !is_numeric($orderId)) {
            $this->json(['error' => 'Invalid order ID'], 400);
        }
        
        $userId = Session::getUserId();
        $order = $this->orderModel->findById($orderId);
        
        if (!$order || $order['ID_tk'] != $userId) {
            $this->json(['error' => 'Order not found'], 404);
        }
        
        $timeline = $this->orderModel->getOrderTimeline($order);
        
        $this->json([
            'success' => true,
            'order_id' => $orderId,
            'status' => $order['Trang_thai'],
            'status_text' => $this->getStatusText($order['Trang_thai']),
            'timeline' => $timeline,
            'updated_at' => date('d/m/Y H:i', strtotime($order['Ngay_cap_nhat']))
        ]);
    }
    
    /**
     * METHOD: reorder() - MUA LẠI ĐƠN HÀNG
     */
    public function reorder($orderId = null) {
        if (!$orderId || !is_numeric($orderId)) {
            Session::flash('error', 'Đơn hàng không hợp lệ');
            redirect(BASE_URL . '/orders');
            exit;
        }

        $userId = Session::getUserId();
        
        // Security Check
        $order = $this->orderModel->findById($orderId);
        if (!$order || $order['ID_tk'] != $userId) {
            Session::flash('error', 'Đơn hàng không tồn tại hoặc không phải của bạn.');
            redirect(BASE_URL . '/orders');
            exit;
        }
        
        // 1. Get Order Details
        $details = $this->orderModel->getOrderDetails($orderId);
        
        if (empty($details)) {
            Session::flash('error', 'Đơn hàng không có sản phẩm hoặc không tồn tại.');
            redirect(BASE_URL . '/orders');
            exit;
        }
        
        $cartModel = $this->model('Cart');
        $productModel = $this->model('Product');
        
        // 2. Add Valid Products to Cart
        $successCount = 0;
        $failCount = 0;
        
        foreach ($details as $item) {
            // Check product existence
            $productId = $item['ID_sp'] ?? null;
            if (!$productId) continue;

            $product = $productModel->findById($productId);
            
            // Normalize status for comparison consistency
            $status = isset($product['Trang_thai']) ? strtolower(trim($product['Trang_thai'])) : '';
            $stock = isset($product['So_luong_ton']) ? (int)$product['So_luong_ton'] : 0;
            
            // Validation: Must exist, be active, and have stock
            if ($product && $status === 'active' && $stock > 0) {
                // Determine quantity to add (limit by available stock)
                $qtyToAdd = min($item['So_luong'], $stock);
                
                // Add to cart (additive)
                $cartModel->addToCart($userId, $productId, $qtyToAdd);
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        // 3. Handle Result & Redirect
        if ($successCount > 0) {
            // Recalculate badge immediately
            Session::syncCartCount($userId);
            
            $msg = 'Đã thêm sản phẩm vào giỏ hàng.';
            if ($failCount > 0) {
                $msg .= ' (Một số sản phẩm không còn kinh doanh hoặc hết hàng đã bị bỏ qua)';
            }
            
            Session::flash('success', $msg);
            redirect(BASE_URL . '/cart');
            exit;
        } else {
            Session::flash('error', 'Rất tiếc, các sản phẩm trong đơn hàng này hiện đã hết hàng hoặc ngừng kinh doanh.');
            redirect(BASE_URL . '/orders');
            exit;
        }
    }

    
    /**
     * Helper: Lấy text hiển thị cho trạng thái
     */
    private function getStatusText($status) {
        $statusTexts = [
            'dang_xu_ly' => 'Đang xử lý',
            'dang_giao' => 'Đang giao',
            'da_giao' => 'Đã giao',
            'huy' => 'Trả hàng/Hủy đơn'
        ];
        
        return $statusTexts[$status] ?? 'Không xác định';
    }
}