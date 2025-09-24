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
        $hashedPassword = password_hash($data['password'], PASSWORD_ARGON2ID);

        // ✅ Default profile picture based on role
        $profilePic = ($data['role'] === 'lawyer')
            ? "https://legaleasenew.blob.core.windows.net/profilepic/lawyer.png"
            : "https://legaleasenew.blob.core.windows.net/profilepic/client.png";

        $stmt = $this->conn->prepare("INSERT INTO users 
            (full_name, email, phone, password, role, gender, profile_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("sssssss", $fullName, $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['gender'], $profilePic);

        if (!$stmt->execute()) {
            throw new Exception("User registration failed: " . $stmt->error);
        }

        $userId = $this->conn->insert_id;
    }

    public function getUserDetails($userId) {
        $stmt = $this->conn->prepare("SELECT full_name, email, profile_picture FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No user found");
        }

        return $result->fetch_assoc();
    }
}
