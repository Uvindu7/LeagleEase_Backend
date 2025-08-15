<?php
class AuthService {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Invalid email");
        }

        $user = $result->fetch_assoc();
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Incorrect password");
        }

        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
    }

    public function logout() {
        session_start();
        session_destroy();
    }
}
