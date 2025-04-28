<?php
// filepath: c:\xampp\htdocs\WebEngineering\includes\utils\DatabaseHelper.php

class DatabaseHelper {
    private $mysqli;
    private static $instance = null;
    
    private function __construct() {
        require_once __DIR__ . '/../database/db_connect.php';
        global $mysqli;
        $this->mysqli = $mysqli;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseHelper();
        }
        return self::$instance;
    }
    
    public function executeQuery($sql, $types = "", $params = []) {
        $stmt = $this->mysqli->prepare($sql);
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $success = $stmt->execute();
        return [
            'success' => $success,
            'statement' => $stmt
        ];
    }
    
    public function fetchAll($sql, $types = "", $params = []) {
        $result = $this->executeQuery($sql, $types, $params);
        
        if ($result['success']) {
            return $result['statement']->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    public function fetchOne($sql, $types = "", $params = []) {
        $result = $this->executeQuery($sql, $types, $params);
        
        if ($result['success']) {
            $fetchResult = $result['statement']->get_result()->fetch_assoc();
            $result['statement']->close();
            return $fetchResult;
        }
        
        return null;
    }
    
    public function insert($sql, $types, $params) {
        $result = $this->executeQuery($sql, $types, $params);
        
        if ($result['success']) {
            $insertId = $this->mysqli->insert_id;
            $result['statement']->close();
            return $insertId;
        }
        
        return false;
    }
}
?>