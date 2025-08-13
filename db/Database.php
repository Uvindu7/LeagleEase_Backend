<?php
class Database {
    public $conn;

    public function __construct() {
        $config = require __DIR__ . '/../Config/config.php';

        $this->conn = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if ($this->conn->connect_error) {
            die(json_encode([
                "success" => false,
                "message" => "Database connection failed."
            ]));
        }
    }
}


