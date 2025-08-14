<?php
// services/UserService.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Utilities/ResponseHelper.php';
require_once __DIR__ . '/FileUploader.php';

class UserService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function register($data, $files) {
        $fullName = $data['fullName'] ?? '';
        $email    = $data['email'] ?? '';
        $phone    = $data['phone'] ?? '';
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? '';

        if (!$fullName || !$email || !$phone || !$password || !$role) {
            ResponseHelper::json(false, "Missing required fields.");
        }

        $checkEmail = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            ResponseHelper::json(false, "Email already registered.");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare(
            "INSERT INTO users (full_name, email, phone, password, role, is_verified) VALUES (?, ?, ?, ?, ?, 0)"
        );
        $stmt->bind_param("sssss", $fullName, $email, $phone, $hashedPassword, $role);

        if (!$stmt->execute()) {
            ResponseHelper::json(false, "Registration failed.");
        }

        $userId = $stmt->insert_id;

        if ($role === 'lawyer') {
            $lawyerId     = $data['lawyerId'] ?? '';
            $registerDate = $data['registerDate'] ?? '';

            $uploader = new FileUploader(UPLOAD_PATH);
            $uploadResult = $uploader->upload($files['verification_doc'], 'lawyer_');

            if (!$uploadResult['success']) {
                ResponseHelper::json(false, $uploadResult['message']);
            }

            $stmt2 = $this->conn->prepare("
                INSERT INTO lawyer_details (user_id, lawyer_id, full_name, register_date, document_path, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt2->bind_param("issss", $userId, $lawyerId, $fullName, $registerDate, $uploadResult['relative']);
            $stmt2->execute();
        }

        ResponseHelper::json(true, "Registration successful.");
    }

    public function login($data) {
        session_start();

        $email    = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            ResponseHelper::json(false, "Email and password required.");
        }

        $stmt = $this->conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            ResponseHelper::json(false, "Invalid email or password.");
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        ResponseHelper::json(true, "Login successful", [
            "id" => $user['id'],
            "full_name" => $user['full_name'],
            "role" => $user['role']
        ]);
    }
}
