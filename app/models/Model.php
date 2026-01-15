<?php
/**
 * =============================================================================
 * BASE MODEL - CLASS CHA CHO TẤT CẢ MODELS
 * =============================================================================
 * 
 * Chức năng:
 * - Kết nối database
 * - Các hàm CRUD cơ bản (Create, Read, Update, Delete)
 * - Query builder helpers
 * 
 * Tất cả models khác sẽ kế thừa class này
 */

class Model {
    
    /**
     * Database instance
     */
    protected $db;
    
    /**
     * Tên bảng (sẽ được override ở các model con)
     */
    protected $table;
    
    /**
     * Primary key (mặc định: 'ID')
     */
    protected $primaryKey = 'ID';
    
    /**
     * Constructor - Khởi tạo database connection
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * ==========================================================================
     * CRUD CƠ BẢN
     * ==========================================================================
     */
    
    /**
     * Lấy tất cả records
     * 
     * @param array $conditions Điều kiện WHERE
     * @param string $orderBy Sắp xếp
     * @param int $limit Giới hạn số lượng
     * @return array
     * 
     * VÍ DỤ:
     * $products = $this->getAll(['Trang_thai' => 'active'], 'Ngay_tao DESC', 10);
     */
    public function getAll($conditions = [], $orderBy = '', $limit = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // WHERE conditions
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        // ORDER BY
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        // LIMIT
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Tìm record theo ID
     * 
     * @param int $id
     * @return array|null
     * 
     * VÍ DỤ:
     * $product = $this->findById(5);
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return $this->db->query($sql, [$id])->fetch();
    }
    
    /**
     * Tìm 1 record theo điều kiện
     * 
     * @param array $conditions
     * @return array|null
     * 
     * VÍ DỤ:
     * $user = $this->findOne(['Email' => 'test@gmail.com']);
     */
    public function findOne($conditions) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " LIMIT 1";
        
        return $this->db->query($sql, $params)->fetch();
    }
    
    /**
     * Tạo record mới
     * 
     * @param array $data
     * @return int|false ID của record mới tạo
     * 
     * VÍ DỤ:
     * $userId = $this->create([
     *     'Tai_khoan' => 'john',
     *     'Email' => 'john@gmail.com'
     * ]);
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql, array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Cập nhật record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     * 
     * VÍ DỤ:
     * $this->update(5, ['Ten' => 'Sữa mới', 'Gia_tien' => 30000]);
     */
    public function update($id, $data) {
        $set = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $set[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id; // ID cho WHERE clause
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " 
                WHERE {$this->primaryKey} = ?";
        // implode(', ', $set) = "name = ?, email = ?"
        $this->db->query($sql, $params);
        
        return $this->db->rowCount() > 0;
    }
    
    /**
     * Xóa record
     * 
     * @param int $id
     * @return bool
     * 
     * VÍ DỤ:
     * $this->delete(5);
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        
        $this->db->query($sql, [$id]);
        
        return $this->db->rowCount() > 0;
    }
    
    /**
     * Đếm số lượng records
     * 
     * @param array $conditions
     * @return int
     * 
     * VÍ DỤ:
     * $count = $this->count(['Trang_thai' => 'active']);
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Kiểm tra record có tồn tại không
     * 
     * @param array $conditions
     * @return bool
     * 
     * VÍ DỤ:
     * $exists = $this->exists(['Email' => 'test@gmail.com']);
     */
    public function exists($conditions) {
        return $this->count($conditions) > 0;
    }
    
    /**
     * ==========================================================================
     * QUERY BUILDER HELPERS
     * ==========================================================================
     */
    
    /**
     * Build WHERE clause
     * 
     * @param array $conditions
     * @return array ['sql' => string, 'params' => array]
     */
    protected function buildWhere($conditions) {
        if (empty($conditions)) {
            return ['sql' => '', 'params' => []];
        }
        
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // IN clause
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $where[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        return [
            'sql' => " WHERE " . implode(' AND ', $where),
            'params' => $params
        ];
    }
    
    /**
     * Execute raw SQL query
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query($sql, $params = []) {
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Execute raw SQL query và lấy 1 kết quả
     * 
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    protected function queryOne($sql, $params = []) {
        return $this->db->query($sql, $params)->fetch();
    }
    
    /**
     * Begin transaction
     */
    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    protected function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    protected function rollBack() {
        return $this->db->rollBack();
    }
    
    /**
     * Gọi stored procedure
     * 
     * @param string $procedureName
     * @param array $params
     * @return array
     */
    protected function callProcedure($procedureName, $params = []) {
        return $this->db->callProcedure($procedureName, $params);
    }
    
    /**
     * Sanitize input (loại bỏ HTML tags)
     * 
     * @param string $value
     * @return string
     */
    protected function sanitize($value) {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Debug: Dump data
     * 
     * @param mixed $data
     */
    protected function dump($data) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}