<?php
class AuthService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
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
        $_SESSION['user_id'] = htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8');
        $_SESSION['full_name'] = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $_SESSION['role'] = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');

        return $user;
    }

    public function logout() {
        session_start();
        session_destroy();
    }
}
?>