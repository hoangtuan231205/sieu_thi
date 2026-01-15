<?php
/**
 * DATABASE CLASS - KẾT NỐI VÀ XỬ LÝ MYSQL
 * 
 * Class này xử lý:
 * - Kết nối MySQL bằng PDO
 * - Thực thi các câu query
 * - Prevent SQL Injection bằng Prepared Statements
 * - Singleton Pattern (chỉ tạo 1 kết nối duy nhất)
 * 
 * CÁCH DÙNG:
 * $db = Database::getInstance();
 * $result = $db->query("SELECT * FROM san_pham WHERE ID_sp = ?", [1]);
 */

class Database {
    
    /**
     * Instance duy nhất của Database (Singleton Pattern)
     */
    private static $instance = null;
    
    /**
     * PDO connection object
     */
    private $pdo;
    
    /**
     * PDOStatement object
     */
    private $stmt;
    
    /**
     * Constructor - Kết nối database
     * Private để áp dụng Singleton Pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Lấy instance duy nhất của Database
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Kết nối đến MySQL database
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                // Throw exception khi có lỗi
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Fetch dữ liệu dạng associative array
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Tắt emulated prepared statements (bảo mật hơn)
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // Persistent connection (kết nối lâu dài)
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log lỗi và hiển thị thông báo thân thiện
            $this->handleError($e);
        }
    }
    
    /**
     * Thực thi câu query
     * 
     * @param string $sql Câu SQL query
     * @param array $params Tham số bind (tránh SQL Injection)
     * @return Database
     * 
     * VÍ DỤ:
     * $db->query("SELECT * FROM san_pham WHERE ID_sp = ?", [1]);
     * $db->query("INSERT INTO san_pham (Ten, Gia_tien) VALUES (?, ?)", ['Sữa', 25000]);
     */
    public function query($sql, $params = []) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Lấy nhiều kết quả (fetchAll)
     * 
     * @return array
     * 
     * VÍ DỤ:
     * $products = $db->query("SELECT * FROM san_pham")->fetchAll();
     */
    public function fetchAll() {
        return $this->stmt->fetchAll();
    }
    
    /**
     * Lấy 1 kết quả (fetch)
     * 
     * @return array|false
     * 
     * VÍ DỤ:
     * $product = $db->query("SELECT * FROM san_pham WHERE ID_sp = ?", [1])->fetch();
     */
    public function fetch() {
        return $this->stmt->fetch();
    }
    
    /**
     * Lấy số hàng bị ảnh hưởng (rowCount)
     * 
     * @return int
     * 
     * VÍ DỤ:
     * $count = $db->query("DELETE FROM gio_hang WHERE ID_tk = ?", [5])->rowCount();
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Lấy ID vừa insert (lastInsertId)
     * 
     * @return string
     * 
     * VÍ DỤ:
     * $db->query("INSERT INTO san_pham (Ten) VALUES (?)", ['Sữa mới']);
     * $newId = $db->lastInsertId();
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Thực thi INSERT và trả về lastInsertId
     * 
     * @param string $sql Câu SQL INSERT
     * @param array $params Tham số bind (có thể dùng named params hoặc ?)
     * @return int|false ID của bản ghi vừa insert, hoặc false nếu thất bại
     * 
     * VÍ DỤ:
     * $id = $db->insert("INSERT INTO san_pham (Ten, Gia_tien) VALUES (:ten, :gia)", ['ten' => 'Sữa', 'gia' => 25000]);
     * $id = $db->insert("INSERT INTO san_pham (Ten) VALUES (?)", ['Sữa mới']);
     */
    public function insert($sql, $params = []) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Ghi log lỗi thay vì die() với HTML để xử lý AJAX tốt hơn
            error_log("Database Insert Error: " . $e->getMessage());
            throw $e; // Ném lại lỗi để phía gọi xử lý
        }
    }
    
    /**
     * Bắt đầu transaction
     * 
     * VÍ DỤ:
     * $db->beginTransaction();
     * $db->query("UPDATE san_pham SET So_luong_ton = So_luong_ton - 1 WHERE ID_sp = ?", [1]);
     * $db->query("INSERT INTO don_hang ...");
     * $db->commit();
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction (hủy bỏ các thay đổi)
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Gọi stored procedure
     * 
     * @param string $procedureName Tên stored procedure
     * @param array $params Tham số đầu vào
     * @return array Kết quả trả về
     * 
     * VÍ DỤ:
     * $result = $db->callProcedure('sp_dat_hang', [1, 'Nguyen Van A', '0123456789', 'Ha Noi', 'Giao nhanh']);
     */
    public function callProcedure($procedureName, $params = []) {
        try {
            // Tạo placeholders cho params
            $placeholders = rtrim(str_repeat('?, ', count($params)), ', ');
            
            // Thêm OUT parameters nếu cần
            $sql = "CALL {$procedureName}({$placeholders})";
            
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            
            // Lấy kết quả
            return $this->stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Thực thi nhiều câu query cùng lúc (batch)
     * 
     * @param array $queries Mảng các câu SQL
     * @return bool
     * 
     * VÍ DỤ:
     * $db->batchQuery([
     *     "UPDATE san_pham SET Gia_tien = Gia_tien * 1.1 WHERE ID_danh_muc = 1",
     *     "UPDATE san_pham SET Trang_thai = 'active' WHERE So_luong_ton > 0"
     * ]);
     */
    public function batchQuery($queries) {
        try {
            $this->beginTransaction();
            
            foreach ($queries as $query) {
                $this->pdo->exec($query);
            }
            
            $this->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->rollBack();
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Kiểm tra bảng có tồn tại không
     * 
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE '{$tableName}'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Đếm số bản ghi trong bảng
     * 
     * @param string $table Tên bảng
     * @param string $where Điều kiện WHERE (optional)
     * @param array $params Tham số bind
     * @return int
     * 
     * VÍ DỤ:
     * $count = $db->count('san_pham', 'Trang_thai = ?', ['active']);
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Xử lý lỗi database
     * 
     * @param PDOException $e
     */
    private function handleError($e) {
        // Log lỗi vào file (production)
        error_log("Database Error: " . $e->getMessage());
        
        // Kiểm tra xem đây có phải là AJAX request không - nếu có, ném exception thay vì die() với HTML
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            // Với AJAX requests, ném exception để phía gọi xử lý và trả về JSON
            throw $e;
        }
        
        // Hiển thị lỗi (chỉ trong development)
        if (DEBUG_MODE) {
            die("
                <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; color: #721c24; font-family: Arial;'>
                    <h2 style='margin: 0 0 10px 0;'>❌ Database Error</h2>
                    <p><strong>Message:</strong> {$e->getMessage()}</p>
                    <p><strong>File:</strong> {$e->getFile()}</p>
                    <p><strong>Line:</strong> {$e->getLine()}</p>
                    <details style='margin-top: 15px;'>
                        <summary style='cursor: pointer; font-weight: bold;'>Stack Trace</summary>
                        <pre style='background: #fff; padding: 10px; margin-top: 10px; overflow: auto;'>{$e->getTraceAsString()}</pre>
                    </details>
                </div>
            ");
        } else {
            die("
                <div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px; color: #721c24; text-align: center;'>
                    <h2>Đã xảy ra lỗi</h2>
                    <p>Vui lòng thử lại sau hoặc liên hệ quản trị viên.</p>
                </div>
            ");
        }
    }
    
    /**
     * Ngăn clone object (Singleton Pattern)
     */
    private function __clone() {}
    
    /**
     * Ngăn unserialize (Singleton Pattern)
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Đóng kết nối khi destroy object
     */
    public function __destruct() {
        $this->pdo = null;
    }
}