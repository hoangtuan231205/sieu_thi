<?php
/**
 * =============================================================================
 * CONFIG FILE - CONFIGURATION & AUTOLOADER
 * =============================================================================
 * 
 * ✅ CHỨA:
 * 1. Config constants
 * 2. Autoloader cho core classes
 * 
 * CÁCH DÙNG:
 * $threshold = Config::get('FREE_SHIPPING_THRESHOLD');
 * $maxQty = Config::CART_MAX_QUANTITY;
 */

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// =============================================================================
// AUTOLOADER - Tự động load các classes
// =============================================================================

spl_autoload_register(function ($className) {
    $basePath = dirname(__DIR__); // c:\xampp\htdocs\sieu_thi

    // Định nghĩa đường dẫn cho từng loại class
    $classPaths = [
        'core' => $basePath . '/app/core/',
        'controllers' => $basePath . '/app/controllers/',
        'models' => $basePath . '/app/models/',
    ];

    // Tìm class trong các thư mục
    foreach ($classPaths as $dir) {
        $file = $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// =============================================================================
// SESSION CONFIG
// =============================================================================

define('SESSION_NAME', 'SIEU_THI_SESSION');
define('SESSION_LIFETIME', 86400); // 1 ngày

// =============================================================================
// DEBUG MODE
// =============================================================================

define('DEBUG_MODE', true); // true: development, false: production

// =============================================================================
// PATH CONFIG
// =============================================================================

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('LOGS_PATH', ROOT_PATH . '/logs');

// =============================================================================
// APPLICATION CONFIG
// =============================================================================


define('BASE_URL', 'http://localhost:81/sieu_thi/public');
define('UPLOADS_DIR', BASE_URL . '/uploads');
define('ASSETS_DIR', BASE_URL . '/assets');
define('UPLOAD_PRODUCT_URL', ASSETS_DIR . '/img/products');
define('UPLOAD_PRODUCT_PATH', PUBLIC_PATH . '/assets/img/products');


// =============================================================================
// DATABASE CONFIG
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sieu_thi');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// INCLUDE HELPERS
// =============================================================================

require_once dirname(__DIR__) . '/app/helpers/functions.php';
require_once dirname(__DIR__) . '/app/helpers/image-helper.php';

// =============================================================================
// CONFIG CLASS
// =============================================================================

class Config
{

    // =========================================================================
    // SHIPPING & ORDER
    // =========================================================================

    /**
     * Ngưỡng miễn phí vận chuyển (VNĐ)
     */
    const FREE_SHIPPING_THRESHOLD = 150000;

    /**
     * Phí vận chuyển mặc định (VNĐ)
     */
    const SHIPPING_FEE = 20000;

    /**
     * Số lượng tối đa mỗi đơn hàng
     */
    const ORDER_MAX_ITEMS = 50;

    // =========================================================================
    // CART
    // =========================================================================

    /**
     * Số lượng tối đa mỗi sản phẩm trong giỏ
     */
    const CART_MAX_QUANTITY = 999;

    /**
     * Số lượng tối thiểu
     */
    const CART_MIN_QUANTITY = 1;

    /**
     * Số item tối đa trong giỏ hàng
     */
    const CART_MAX_ITEMS = 50;

    // =========================================================================
    // PAGINATION
    // =========================================================================

    /**
     * Số sản phẩm mỗi trang (customer)
     */
    const PRODUCTS_PER_PAGE = 12;

    /**
     * Số items mỗi trang (admin)
     */
    const ADMIN_ITEMS_PER_PAGE = 20;

    /**
     * Số đơn hàng mỗi trang (customer)
     */
    const ORDERS_PER_PAGE = 10;

    /**
     * Số kết quả search tối đa
     */
    const SEARCH_MAX_RESULTS = 50;

    // =========================================================================
    // PRODUCT
    // =========================================================================

    /**
     * Ngưỡng cảnh báo sắp hết hàng
     */
    const LOW_STOCK_THRESHOLD = 10;

    /**
     * Giá tối thiểu (VNĐ)
     */
    const PRODUCT_MIN_PRICE = 0;

    /**
     * Giá tối đa (VNĐ)
     */
    const PRODUCT_MAX_PRICE = 999999999;

    /**
     * Số lượng tối đa trong kho
     */
    const PRODUCT_MAX_STOCK = 999999;

    /**
     * Độ dài tối đa tên sản phẩm
     */
    const PRODUCT_NAME_MAX_LENGTH = 200;

    /**
     * Độ dài tối đa mô tả
     */
    const PRODUCT_DESCRIPTION_MAX_LENGTH = 5000;

    // =========================================================================
    // FILE UPLOAD
    // =========================================================================

    /**
     * Kích thước file tối đa (bytes) - 5MB
     */
    const MAX_UPLOAD_SIZE = 5242880;

    /**
     * Các MIME types được phép upload
     */
    const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    /**
     * Kích thước ảnh tối đa (pixels)
     */
    const MAX_IMAGE_WIDTH = 2000;
    const MAX_IMAGE_HEIGHT = 2000;

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Độ dài tối thiểu password
     */
    const PASSWORD_MIN_LENGTH = 6;

    /**
     * Độ dài tối đa password
     */
    const PASSWORD_MAX_LENGTH = 100;

    /**
     * Độ dài tối thiểu username
     */
    const USERNAME_MIN_LENGTH = 4;

    /**
     * Độ dài tối đa username
     */
    const USERNAME_MAX_LENGTH = 50;

    /**
     * Độ dài tối đa email
     */
    const EMAIL_MAX_LENGTH = 100;

    /**
     * Độ dài tối thiểu search keyword
     */
    const SEARCH_MIN_LENGTH = 2;

    /**
     * Độ dài tối đa search keyword
     */
    const SEARCH_MAX_LENGTH = 200;

    // =========================================================================
    // RATE LIMITING
    // =========================================================================

    /**
     * Số lần đăng nhập tối đa trong timeframe
     */
    const LOGIN_MAX_ATTEMPTS = 5;

    /**
     * Thời gian khóa sau khi vượt quá login attempts (seconds)
     */
    const LOGIN_LOCKOUT_TIME = 300; // 5 minutes

    /**
     * Rate limit cho cart operations (requests/minute)
     */
    const CART_RATE_LIMIT = 10;

    /**
     * Rate limit cho cart update (requests/minute)
     */
    const CART_UPDATE_RATE_LIMIT = 20;

    /**
     * Rate limit cho search (requests/minute)
     */
    const SEARCH_RATE_LIMIT = 30;

    // =========================================================================
    // WAREHOUSE
    // =========================================================================

    /**
     * Số sản phẩm tối đa trong 1 phiếu nhập
     */
    const IMPORT_MAX_PRODUCTS = 100;

    /**
     * Giá nhập tối đa (VNĐ)
     */
    const IMPORT_MAX_PRICE = 999999999;

    /**
     * Số lượng nhập tối đa mỗi lần
     */
    const IMPORT_MAX_QUANTITY = 999999;

    /**
     * Thời gian tối đa cho phép nhập hàng quá khứ (days)
     */
    const IMPORT_MAX_DAYS_BACK = 365;

    // =========================================================================
    // ORDER STATUS
    // =========================================================================

    /**
     * Danh sách trạng thái đơn hàng hợp lệ
     */
    const ORDER_STATUSES = [
        'cho_xac_nhan',
        'da_giao',
        'huy'
    ];

    /**
     * Trạng thái có thể hủy đơn
     */
    const CANCELABLE_STATUSES = ['cho_xac_nhan'];

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Lấy giá trị config
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (defined("self::{$key}")) {
            return constant("self::{$key}");
        }
        return $default;
    }

    /**
     * Kiểm tra có phải status hợp lệ không
     * 
     * @param string $status
     * @return bool
     */
    public static function isValidOrderStatus($status)
    {
        return in_array($status, self::ORDER_STATUSES);
    }

    /**
     * Kiểm tra đơn hàng có thể hủy không
     * 
     * @param string $status
     * @return bool
     */
    public static function canCancelOrder($status)
    {
        return in_array($status, self::CANCELABLE_STATUSES);
    }

    /**
     * Validate kích thước file
     * 
     * @param int $size
     * @return bool
     */
    public static function isValidFileSize($size)
    {
        return $size > 0 && $size <= self::MAX_UPLOAD_SIZE;
    }

    /**
     * Validate MIME type ảnh
     * 
     * @param string $mimeType
     * @return bool
     */
    public static function isValidImageType($mimeType)
    {
        return in_array($mimeType, self::ALLOWED_IMAGE_TYPES);
    }

    /**
     * Tính phí vận chuyển
     * 
     * @param float $subtotal
     * @return float
     */
    public static function calculateShippingFee($subtotal)
    {
        return ($subtotal >= self::FREE_SHIPPING_THRESHOLD) ? 0 : self::SHIPPING_FEE;
    }

    /**
     * Validate số lượng sản phẩm
     * 
     * @param int $quantity
     * @return array ['valid' => bool, 'quantity' => int, 'message' => string]
     */
    public static function validateQuantity($quantity)
    {
        $quantity = (int) $quantity;

        if ($quantity < self::CART_MIN_QUANTITY) {
            return [
                'valid' => false,
                'quantity' => self::CART_MIN_QUANTITY,
                'message' => 'Số lượng tối thiểu là ' . self::CART_MIN_QUANTITY
            ];
        }

        if ($quantity > self::CART_MAX_QUANTITY) {
            return [
                'valid' => false,
                'quantity' => self::CART_MAX_QUANTITY,
                'message' => 'Số lượng tối đa là ' . self::CART_MAX_QUANTITY
            ];
        }

        return [
            'valid' => true,
            'quantity' => $quantity,
            'message' => 'OK'
        ];
    }

    /**
     * Format các constants thành array (for debugging)
     * 
     * @return array
     */
    public static function all()
    {
        $reflection = new ReflectionClass(__CLASS__);
        return $reflection->getConstants();
    }
}