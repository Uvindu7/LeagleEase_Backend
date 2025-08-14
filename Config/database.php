<?php
// config/Database.php
require_once __DIR__ . '/config.php';

class Database {
    private $conn;

    public function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die(json_encode(["success" => false, "message" => "Database connection failed."]));
        }
        return $this->conn;
    }
}
