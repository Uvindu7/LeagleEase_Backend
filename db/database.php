<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    public static function connect() {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        return $conn;
    }
}
