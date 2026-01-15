<?php
/**
 * MIDDLEWARE CLASS - KIỂM TRA QUYỀN TRUY CẬP
 * 
 * Class này xử lý:
 * - Kiểm tra người dùng đã đăng nhập chưa
 * - Kiểm tra quyền truy cập (Customer, Admin, Warehouse)
 * - Chặn truy cập không hợp lệ
 * 
 * CÁCH DÙNG:
 * Middleware::customer();  // Chặn nếu chưa đăng nhập
 * Middleware::admin();     // Chỉ cho phép Admin
 * Middleware::warehouse(); // Chỉ cho phép Quản lý kho
 */

class Middleware {
    
    /**
     * Kiểm tra người dùng đã đăng nhập chưa (dành cho khách hàng)
     * Nếu chưa đăng nhập → redirect về /auth/login
     * 
     * CÁCH DÙNG:
     * public function cart() {
     *     Middleware::customer();  // Chặn nếu chưa đăng nhập
     *     // Code tiếp theo...
     * }
     */
    public static function customer() {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Vui lòng đăng nhập để tiếp tục');
            redirect('/public/auth/login');
            exit;
        }
    }
    
    /**
     * Kiểm tra quyền ADMIN
     * Chỉ cho phép người dùng có role = 'ADMIN'
     * 
     * CÁCH DÙNG:
     * public function __construct() {
     *     Middleware::admin();  // Chặn nếu không phải admin
     * }
     */
    public static function admin() {
        // Bước 1: Kiểm tra đã đăng nhập chưa
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Vui lòng đăng nhập');
            redirect('/public/auth/login');
            exit;
        }
        
        // Bước 2: Kiểm tra có phải Admin không
        if (!Session::isAdmin()) {
            Session::flash('error', 'Bạn không có quyền truy cập trang này');
            
            // Redirect về trang phù hợp với role
            if (Session::isWarehouse()) {
                redirect('/public/warehouse');
            } else {
                redirect('/');
            }
            exit;
        }
    }
    
    /**
     * Kiểm tra quyền QUẢN LÝ KHO
     * Chỉ cho phép người dùng có role = 'QUAN_LY_KHO' hoặc 'ADMIN'
     * (Admin có thể truy cập tất cả)
     * 
     * CÁCH DÙNG:
     * public function __construct() {
     *     Middleware::warehouse();
     * }
     */
    public static function warehouse() {
        // Bước 1: Kiểm tra đã đăng nhập chưa
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Vui lòng đăng nhập');
            redirect('/public/auth/login');
            exit;
        }
        
        // Bước 2: Kiểm tra có phải Warehouse hoặc Admin không
        if (!Session::isWarehouse() && !Session::isAdmin()) {
            Session::flash('error', 'Bạn không có quyền truy cập trang này');
            redirect('/');
            exit;
        }
    }
    
    /**
     * Kiểm tra người dùng CHƯA đăng nhập (dành cho trang login/register)
     * Nếu đã đăng nhập → redirect về trang chủ phù hợp với role
     * 
     * CÁCH DÙNG:
     * public function login() {
     *     Middleware::guest();  // Nếu đã đăng nhập thì redirect
     *     // Hiển thị form login...
     * }
     */
    public static function guest() {
        if (Session::isLoggedIn()) {
            // Redirect về trang phù hợp với role
            if (Session::isAdmin()) {
                redirect('/admin');
            } elseif (Session::isWarehouse()) {
                redirect('/warehouse');
            } else {
                redirect('/');
            }
            exit;
        }
    }
    
    /**
     * Kiểm tra quyền truy cập theo nhiều roles
     * 
     * @param array $allowedRoles Mảng các role được phép ['ADMIN', 'QUAN_LY_KHO']
     * 
     * CÁCH DÙNG:
     * Middleware::checkRoles(['ADMIN', 'QUAN_LY_KHO']);
     */
    public static function checkRoles($allowedRoles = []) {
        // Kiểm tra đã đăng nhập chưa
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Vui lòng đăng nhập');
            redirect('/public/auth/login');
            exit;
        }
        
        // Kiểm tra role có nằm trong danh sách cho phép không
        $userRole = Session::getUserRole();
        if (!in_array($userRole, $allowedRoles)) {
            Session::flash('error', 'Bạn không có quyền truy cập');
            redirect('/');
            exit;
        }
    }
    
    /**
     * Kiểm tra CSRF token (bảo mật form)
     * 
     * @param string $token Token từ form
     * @return bool
     * 
     * CÁCH DÙNG:
     * if (!Middleware::verifyCsrf($_POST['csrf_token'])) {
     *     die('Invalid CSRF token');
     * }
     */
    public static function verifyCsrf($token) {
        if (!Session::verifyCsrfToken($token)) {
            Session::flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            return false;
        }
        return true;
    }
    
    /**
     * Kiểm tra request method (GET, POST, PUT, DELETE)
     * 
     * @param string $method Method cần kiểm tra
     * @return bool
     * 
     * CÁCH DÙNG:
     * if (!Middleware::checkMethod('POST')) {
     *     $this->json(['error' => 'Method not allowed'], 405);
     * }
     */
    public static function checkMethod($method) {
        return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
    }
    
    /**
     * Kiểm tra có phải AJAX request không
     * 
     * @return bool
     * 
     * CÁCH DÙNG:
     * if (!Middleware::isAjax()) {
     *     die('Only AJAX requests allowed');
     * }
     */
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Rate limiting - Giới hạn số request (chống spam)
     * 
     * @param string $key Unique key (VD: 'login_' . $_SERVER['REMOTE_ADDR'])
     * @param int $maxAttempts Số lần thử tối đa
     * @param int $timeWindow Khoảng thời gian (giây)
     * @return bool
     * 
     * CÁCH DÙNG:
     * if (!Middleware::rateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
     *     die('Quá nhiều lần thử. Vui lòng đợi 5 phút.');
     * }
     */
    public static function rateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        $attempts = Session::get('rate_limit_' . $key, []);
        $now = time();
        
        // Lọc bỏ các attempts quá cũ
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Kiểm tra đã vượt quá giới hạn chưa
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        // Thêm attempt mới
        $attempts[] = $now;
        Session::set('rate_limit_' . $key, $attempts);
        
        return true;
    }
    
    /**
     * Kiểm tra IP có bị ban không
     * 
     * @param array $bannedIps Mảng các IP bị cấm
     * @return bool
     * 
     * CÁCH DÙNG:
     * if (Middleware::checkBannedIp(['192.168.1.100', '10.0.0.1'])) {
     *     die('Your IP has been banned');
     * }
     */
    public static function checkBannedIp($bannedIps = []) {
        $userIp = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (in_array($userIp, $bannedIps)) {
            http_response_code(403);
            die('Access Denied');
        }
        
        return false;
    }
    
    /**
     * Log activity (ghi lại hoạt động của user)
     * 
     * @param string $action Hành động (VD: 'login', 'add_to_cart', 'checkout')
     * @param array $data Dữ liệu thêm
     * 
     * CÁCH DÙNG:
     * Middleware::logActivity('login', ['user_id' => 1, 'ip' => $_SERVER['REMOTE_ADDR']]);
     */
    public static function logActivity($action, $data = []) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => Session::getUserId(),
            'user_role' => Session::getUserRole(),
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        // Log vào file
        $logFile = ROOT_PATH . '/public/logs/activity_' . date('Y-m-d') . '.log';
        
        // Tạo thư mục logs nếu chưa có
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        file_put_contents(
            $logFile, 
            json_encode($log) . PHP_EOL, 
            FILE_APPEND
        );
    }
    
    /**
     * Redirect nếu đã xác thực (dùng cho API)
     * 
     * @param string $message
     * @param int $statusCode
     */
    public static function jsonUnauthorized($message = 'Unauthorized', $statusCode = 401) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}