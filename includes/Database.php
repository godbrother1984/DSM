<?php
/*
=============================================================================
DATABASE CLASS - แก้ไข Missing File
=============================================================================
*/

class Database {
    private static $instance = null;
    private $pdo = null;
    private $connected = false;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $config = [
                "host" => "localhost",
                "database" => "digital_signage",
                "username" => "root",
                "password" => ""
            ];
            
            // Try to load config file
            if (file_exists("config/database.php")) {
                $config = include "config/database.php";
            }
            
            $dsn = "mysql:host={$config[\"host\"]};dbname={$config[\"database\"]};charset=utf8mb4";
            
            $this->pdo = new PDO($dsn, $config["username"], $config["password"], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $this->connected = true;
        } catch (Exception $e) {
            $this->connected = false;
            // Log error but don't throw exception
            error_log("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function isConnected() {
        return $this->connected && $this->pdo !== null;
    }
    
    public function fetchAll($sql, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Database not connected");
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Database not connected");
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        if (!$this->isConnected()) {
            throw new Exception("Database not connected");
        }
        
        $columns = implode(",", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute($data)) {
            return $this->pdo->lastInsertId();
        }
        
        throw new Exception("Insert failed");
    }
    
    public function update($table, $data, $where, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Database not connected");
        }
        
        $setPairs = [];
        foreach ($data as $key => $value) {
            $setPairs[] = "{$key} = :{$key}";
        }
        $setClause = implode(", ", $setPairs);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute(array_merge($data, $params));
    }
    
    public function delete($table, $where, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception("Database not connected");
        }
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
?>