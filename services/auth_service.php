<?php
class AuthService {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        // Use correct column name: id
        $stmt = $this->conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Username or Password Invalid! Please Try Again");
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            throw new Exception("Username or Password Invalid! Please Try Again");
        }
        

        session_start();
        // Save the right session keys
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
    }

    public function logout() {
        session_start();
        session_destroy();
    }
}
