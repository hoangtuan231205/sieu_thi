<?php
// Start output buffering immediately to catch any spurious whitespace or errors before headers
ob_start();
/**
 * =============================================================================
 * INDEX.PHP - ENTRY POINT (ĐIỂM VÀO DUY NHẤT CỦA WEBSITE)
 * =============================================================================
 * 
 * Tất cả requests đều đi qua file này nhờ .htaccess
 * 
 * LUỒNG HOẠT ĐỘNG:
 * 1. Load config
 * 2. Khởi động session
 * 3. Load core classes
 * 4. Chạy router (App)
 * 5. Router gọi Controller tương ứng
 * 6. Controller gọi Model và View
 * 7. Trả về HTML cho người dùng
 */

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/config.php';


Session::start();


$app = new App();


?>