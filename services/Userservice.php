<?php
require_once __DIR__ . '/../bd/Database.php';
require_once __DIR__ . '/../Utilities/Responsehelper.php';
require_once __DIR__ . '/../Utilities/FileUploader.php';

class UserService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    public function loginUser($email, $password) {
        if (!$email || !$password) {
            ResponseHelper::json(["success" => false, "message" => "Email and password are required."]);
        }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            ResponseHelper::json(["success" => false, "message" => "User not found."]);
        }

        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            ResponseHelper::json([
                "success" => true,
                "message" => "Login successful.",
                "user" => [
                    "id" => $user['user_id'],
                    "name" => $user['full_name'],
                    "role" => $user['role'],
                    "is_verified" => $user['is_verified']
                ]
            ]);
        } else {
            ResponseHelper::json(["success" => false, "message" => "Incorrect password."]);
        }
    }

    public function registerUser($data, $files) {
        $required = ['firstName', 'lastName', 'email', 'phone', 'password', 'role', 'gender'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                ResponseHelper::json(["message" => "Missing field: $f"]);
            }
        }

        $firstName = $this->conn->real_escape_string($data['firstName']);
        $lastName = $this->conn->real_escape_string($data['lastName']);
        $fullName = "$firstName $lastName";
        $email = $this->conn->real_escape_string($data['email']);
        $phone = $this->conn->real_escape_string($data['phone']);
        $role = $this->conn->real_escape_string($data['role']);
        $gender = $this->conn->real_escape_string($data['gender']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Check email
        $check = $this->conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            ResponseHelper::json(["message" => "Email already registered"]);
        }

        // Insert user
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, gender, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param("ssssss", $fullName, $email, $phone, $password, $role, $gender);
        if (!$stmt->execute()) {
            ResponseHelper::json(["message" => "Registration failed: " . $stmt->error]);
        }

        $userId = $this->conn->insert_id;

        if ($role === 'lawyer') {
            $this->registerLawyer($userId, $fullName, $data, $files);
        }

        ResponseHelper::json(["message" => "Registration successful"]);
    }

    private function registerLawyer($userId, $fullName, $data, $files) {
        if (!isset($data['lawyerId'], $data['registerDate']) || !isset($files['verification_doc'])) {
            ResponseHelper::json(["message" => "Lawyer ID, date or document missing"]);
        }

        if ($files['verification_doc']['error'] !== 0) {
            ResponseHelper::json(["message" => "Document upload failed"]);
        }

        list($success, $relativePath) = FileUploader::upload($files['verification_doc'], "lawyer_");
        if (!$success) {
            ResponseHelper::json(["message" => $relativePath]);
        }

        $lawyerId = $this->conn->real_escape_string($data['lawyerId']);
        $registerDate = $this->conn->real_escape_string($data['registerDate']);

        $stmt2 = $this->conn->prepare("INSERT INTO lawyer_details (user_id, lawyer_id, full_name, register_date, document_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt2->bind_param("issss", $userId, $lawyerId, $fullName, $registerDate, $relativePath);

        if (!$stmt2->execute()) {
            ResponseHelper::json(["message" => "Lawyer detail insert failed: " . $stmt2->error]);
        }
    }
}

