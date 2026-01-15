<?php
class App {
    
    /**
     * Controller mặc định khi truy cập /
     */
    protected $controller = 'HomeController';
    
    /**
     * Method mặc định
     */
    protected $method = 'index';
    
    /**
     * Mảng các tham số
     */
    protected $params = [];
    
    /**Constructor - Chạy router
     */
    public function __construct() {
        // Parse URL
        $url = $this->parseUrl();
        
        // Xử lý routing
        $this->handleRouting($url);
    }
    protected function parseUrl() {
        if (isset($_GET['url'])) {
            // Lấy URL từ query string
            $url = rtrim($_GET['url'], '/');
            
            // Lọc bỏ ký tự đặc biệt (bảo mật)
            $url = filter_var($url, FILTER_SANITIZE_URL);
            
            // Tách thành mảng
            $url = explode('/', $url);
            
            return $url;
        }
        
        // Nếu không có URL → trả về mảng rỗng (trang chủ)
        return [];
    }
    
    /**
     * Xử lý routing dựa trên URL
     * 
     * @param array $url
     */
    protected function handleRouting($url) {
        // TRƯỜNG HỢP 1: TRANG CHỦ (/)
       if (empty($url)) {
        $this->callController('HomeController', 'index', []);
        return;
    }

        // TRƯỜNG HỢP 2: /auth/login, /auth/register
        if ($url[0] === 'auth') {
            $this->controller = 'AuthController';
            
            // Lấy method (login, register, logout)
            if (isset($url[1])) {
                $this->method = $url[1];
            }
            
            // Params (nếu có)
            $this->params = array_slice($url, 2);
            
            $this->callController($this->controller, $this->method, $this->params);
            return;
        }
        
        // TRƯỜNG HỢP 3: /admin/* (ADMIN ROUTES)
        if ($url[0] === 'admin') {
            $this->controller = 'AdminController';
            // Admin routes:
            // /admin → dashboard
            // /admin/products → products list
            // /admin/orders → orders list
            if (isset($url[1])) {
        // export-products → exportProducts
            $this->method = str_replace('-', '', lcfirst(ucwords($url[1], '-')));
}
            
            // Params
            $this->params = array_slice($url, 2);
            
            $this->callController($this->controller, $this->method, $this->params);
            return;
        }
        
        // TRƯỜNG HỢP 4: /warehouse/* (WAREHOUSE ROUTES)
        if ($url[0] === 'warehouse') {
            $this->controller = 'WarehouseController';
            // Warehouse routes:
            // /warehouse → dashboard
            // /warehouse/imports → import list
            // /warehouse/inventory → inventory
            if (isset($url[1])) {
                 $this->method = str_replace('-', '', lcfirst(ucwords($url[1], '-')));
            }
            
            // Params
            $this->params = array_slice($url, 2);
            
            $this->callController($this->controller, $this->method, $this->params);
            return;
        }
        
        // TRƯỜNG HỢP 5: /products, /cart, /checkout... (CUSTOMER ROUTES)
        // Danh sách các controllers cho khách hàng
        $customerControllers = [
            'products' => 'ProductController',
            'cart' => 'CartController',
            'checkout' => 'CheckoutController',
            'orders' => 'OrderController',
            'about' => 'HomeController',
            'contact' => 'HomeController',
            'user' => 'UserController',
            'pos' => 'POSController',
        ];
        
        if (isset($customerControllers[$url[0]])) {
            $this->controller = $customerControllers[$url[0]];
            
            // Lấy method
            if (isset($url[1])) {
                // FIX: Convert kebab-case to camelCase (e.g., buy-now -> buyNow)
                $this->method = str_replace('-', '', lcfirst(ucwords($url[1], '-')));
            }
            
            // Params
            $this->params = array_slice($url, 2);
            
            $this->callController($this->controller, $this->method, $this->params);
            return;
        }
        
        // TRƯỜNG HỢP 6: 404 NOT FOUND
        $this->show404();
    }
    
    /**
     * Gọi Controller và Method
     * 
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     */
    protected function callController($controllerName, $methodName, $params = []) {
        // Đường dẫn file controller
        $controllerFile = '../app/controllers/' . $controllerName . '.php';
        
        // Kiểm tra file có tồn tại không
        if (!file_exists($controllerFile)) {
            $this->show404();
            return;
        }
        
        // Require file controller
        require_once $controllerFile;
        
        // Tạo instance của controller
        $this->controller = new $controllerName;
        
        // Kiểm tra method có tồn tại không
        if (!method_exists($this->controller, $methodName)) {
            $this->show404();
            return;
        }
        
        // giống như $this->controller->$methodName($params);
        call_user_func_array([$this->controller, $methodName], $params);
    }
    
    /**
     * Hiển thị trang 404
     */
    protected function show404() {
        http_response_code(404);
        
        // Nếu có view 404 thì hiển thị
        $view404 = '../app/views/errors/404.php';
        if (file_exists($view404)) {
            require_once $view404;
        } else {
            // Hiển thị 404 đơn giản
            echo "
            <!DOCTYPE html>
            <html lang='vi'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>404 - Không tìm thấy trang</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #333;
                    }
                    .container {
                        text-align: center;
                        background: white;
                        padding: 60px 40px;
                        border-radius: 20px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        max-width: 500px;
                    }
                    .error-code {
                        font-size: 120px;
                        font-weight: bold;
                        color: #667eea;
                        line-height: 1;
                        margin-bottom: 20px;
                    }
                    h1 {
                        font-size: 32px;
                        margin-bottom: 15px;
                        color: #2d3748;
                    }
                    p {
                        font-size: 18px;
                        color: #718096;
                        margin-bottom: 30px;
                        line-height: 1.6;
                    }
                    a {
                        display: inline-block;
                        background: #667eea;
                        color: white;
                        text-decoration: none;
                        padding: 15px 40px;
                        border-radius: 50px;
                        font-weight: 600;
                        transition: all 0.3s;
                    }
                    a:hover {
                        background: #5568d3;
                        transform: translateY(-2px);
                        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='error-code'>404</div>
                    <h1>Không tìm thấy trang</h1>
                    <p>Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
                    <a href='" . BASE_URL . "'>Về trang chủ</a>
                </div>
            </body>
            </html>
            ";
        }
        exit;
    }
}