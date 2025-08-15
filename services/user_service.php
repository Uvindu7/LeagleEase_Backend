<?php
class UserService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function isEmailTaken($email) {
        $stmt = $this->conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function register($data, &$userId, &$fullName) {
        $fullName = $data['firstName'] . ' ' . $data['lastName'];
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, gender, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param("ssssss", $fullName, $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['gender']);

        if (!$stmt->execute()) {
            throw new Exception("User registration failed: " . $stmt->error);
        }

        $userId = $this->conn->insert_id;
    }
}
