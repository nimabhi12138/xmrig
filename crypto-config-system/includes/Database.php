<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("查询错误: " . $e->getMessage());
        }
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_map(function($field) { return ':' . $field; }, $fields);
        
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        $this->query($sql, $data);
        return $this->conn->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach($data as $field => $value) {
            $setParts[] = "$field = :$field";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
        $this->query($sql, array_merge($data, $whereParams));
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $params);
    }
}