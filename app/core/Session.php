<?php
/**
 * =============================================================================
 * SESSION CLASS - QUẢN LÝ SESSION
 * =============================================================================
 * 
 * Class này xử lý:
 * - Bật/tắt session
 * - Lưu/lấy/xóa dữ liệu session
 * - Flash messages (thông báo hiển thị 1 lần)
 * - Kiểm tra đăng nhập
 * 
 * CÁCH DÙNG:
 * Session::set('user_id', 123);
 * $userId = Session::get('user_id');
 * Session::flash('success', 'Đăng nhập thành công!');
 */

class Session {
    
    /**
     * Khởi động session
     * Gọi function này ở đầu ứng dụng
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Cấu hình session
            ini_set('session.cookie_httponly', 1);  // Bảo mật: không cho JS đọc cookie
            ini_set('session.use_only_cookies', 1); // Chỉ dùng cookies
            ini_set('session.cookie_lifetime', SESSION_LIFETIME);
            
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID để tránh session fixation attack
            if (!self::has('_session_started')) {
                session_regenerate_id(true);
                self::set('_session_started', time());
            }
        }
    }
    
    /**
     * Lưu giá trị vào session
     * 
     * @param string $key Tên key
     * @param mixed $value Giá trị
     * 
     * VÍ DỤ:
     * Session::set('user_id', 123);
     * Session::set('cart', ['product_1' => 2, 'product_2' => 1]);
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Lấy giá trị từ session
     * 
     * @param string $key Tên key
     * @param mixed $default Giá trị mặc định nếu không tồn tại
     * @return mixed
     * 
     * VÍ DỤ:
     * $userId = Session::get('user_id');
     * $username = Session::get('username', 'Guest');
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Kiểm tra key có tồn tại không
     * 
     * @param string $key
     * @return bool
     * 
     * VÍ DỤ:
     * if (Session::has('user_id')) {
     *     echo "Đã đăng nhập";
     * }
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Xóa một key khỏi session
     * 
     * @param string $key
     * 
     * VÍ DỤ:
     * Session::delete('temp_data');
     */
    public static function delete($key) {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Xóa toàn bộ session (đăng xuất)
     * 
     * VÍ DỤ:
     * Session::destroy();
     * redirect('/login');
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Xóa session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(
                    session_name(), 
                    '', 
                    time() - 3600, 
                    '/'
                );
            }
            
            session_destroy();
        }
    }
    
    /**
     * Flash message - Thông báo hiển thị 1 lần
     * 
     * @param string $key Tên key (thường là 'success', 'error', 'warning', 'info')
     * @param string $message Nội dung thông báo
     * 
     * VÍ DỤ:
     * Session::flash('success', 'Đăng nhập thành công!');
     * Session::flash('error', 'Sai tên đăng nhập hoặc mật khẩu');
     */
    public static function flash($key, $message) {
        $_SESSION['_flash'][$key] = $message;
    }
    
    /**
     * Lấy flash message và xóa sau khi lấy
     * 
     * @param string $key
     * @return string|null
     * 
     * VÍ DỤ trong view:
     * <?php if ($msg = Session::getFlash('success')): ?>
     *     <div class="alert success"><?= $msg ?></div>
     * <?php endif; ?>
     */
    public static function getFlash($key) {
        if (isset($_SESSION['_flash'][$key])) {
            $message = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $message;
        }
        return null;
    }
    
    /**
     * Kiểm tra có flash message không
     * 
     * @param string $key
     * @return bool
     */
    public static function hasFlash($key) {
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Lấy tất cả flash messages
     * 
     * @return array
     */
    public static function getAllFlashes() {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }
    
    // =========================================================================
    // CÁC HÀM LIÊN QUAN ĐẾN AUTHENTICATION (Đăng nhập)
    // =========================================================================
    
    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     * 
     * @return bool
     * 
     * VÍ DỤ:
     * if (Session::isLoggedIn()) {
     *     echo "Xin chào " . Session::get('username');
     * }
     */
    public static function isLoggedIn() {
        return self::has('user_id');
    }
    
    /**
     * Lấy user ID của người đang đăng nhập
     * 
     * @return int|null
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Lấy tên người dùng
     * 
     * @return string|null
     */
    public static function getUserName() {
        return self::get('user_name') ?? self::get('user_email');
    }
    
    /**
     * Lấy role/quyền của người đang đăng nhập
     * 
     * @return string|null ('KH', 'ADMIN', 'QUAN_LY_KHO')
     */
    public static function getUserRole() {
        return self::get('user_role');
    }
    
    /**
     * Kiểm tra có phải Admin không
     * 
     * @return bool
     */
    public static function isAdmin() {
        return self::get('user_role') === 'ADMIN';
    }
    
    /**
     * Kiểm tra có phải Quản lý kho không
     * 
     * @return bool
     */
    public static function isWarehouse() {
        return self::get('user_role') === 'QUAN_LY_KHO';
    }
    
    /**
     * Kiểm tra có phải Khách hàng không
     * 
     * @return bool
     */
    public static function isCustomer() {
        return self::get('user_role') === 'KH';
    }
    
    /**
     * Lưu thông tin user sau khi đăng nhập
     * 
     * @param array $user Thông tin user từ database
     * 
     * VÍ DỤ:
     * $user = ['ID' => 1, 'Phan_quyen' => 'ADMIN', 'Ho_ten' => 'Nguyen Van A'];
     * Session::login($user);
     */
    public static function login($user) {
        self::set('user_id', $user['ID']);
        self::set('user_role', $user['Phan_quyen']);
        self::set('user_name', $user['Ho_ten'] ?? $user['Tai_khoan']);
        self::set('user_email', $user['Email'] ?? '');
        
        // Regenerate session ID để tránh session fixation
        session_regenerate_id(true);
    }
    
    /**
     * Đăng xuất
     * 
     * VÍ DỤ:
     * Session::logout();
     * redirect('/login');
     */
    public static function logout() {
        self::destroy();
    }
    
    // =========================================================================
    // SHOPPING CART HELPERS
    // =========================================================================
    
    /**
     * Lấy số lượng items trong giỏ hàng (từ database)
     * Note: Cái này chỉ là helper, logic thực sự nằm ở CartModel
     * 
     * @return int
     */
    public static function getCartCount() {
        return self::get('cart_count', 0);
    }
    
    /**
     * Cập nhật số lượng giỏ hàng trong session
     * 
     * @param int $count
     */
    public static function setCartCount($count) {
        self::set('cart_count', (int)$count);
    }

    /**
     * Đồng bộ số lượng giỏ hàng từ database vào session
     * 
     * @param int|null $userId Nếu null sẽ lấy từ session hiện tại
     * @return int
     */
    public static function syncCartCount($userId = null) {
        if (!$userId) $userId = self::getUserId();
        if (!$userId) {
            self::setCartCount(0);
            return 0;
        }

        // Tải Model Cart để lấy số lượng thực tế
        // Lưu ý: Phải dùng require do autoload có thể chưa chạy hết
        $cartFile = dirname(__DIR__) . '/models/Cart.php';
        if (file_exists($cartFile)) {
            require_once $cartFile;
            $cart = new Cart();
            $count = $cart->getCartCount($userId);
            self::setCartCount($count);
            return $count;
        }

        return 0;
    }
    
    // =========================================================================
    // CSRF TOKEN (Bảo mật form)
    // =========================================================================
    
    /**
     * Tạo CSRF token
     * 
     * @return string
     * 
     * VÍ DỤ trong form:
     * <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
     */
    public static function getCsrfToken() {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }
    
    /**
     * Xác thực CSRF token
     * 
     * @param string $token Token từ form
     * @return bool
     * 
     * VÍ DỤ:
     * if (!Session::verifyCsrfToken($_POST['csrf_token'])) {
     *     die('Invalid CSRF token');
     * }
     */
    public static function verifyCsrfToken($token) {
        return hash_equals(self::get('csrf_token'), $token);
    }
    
    // =========================================================================
    // DEBUG HELPERS
    // =========================================================================
    
    /**
     * Lấy toàn bộ session data (chỉ dùng để debug)
     * 
     * @return array
     */
    public static function all() {
        return $_SESSION;
    }
    
    /**
     * Đếm số items trong session
     * 
     * @return int
     */
    public static function count() {
        return count($_SESSION);
    }
}