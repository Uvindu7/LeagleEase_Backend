<?php
// services/UserService.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utilities/FileUploader.php';

class UserService {
    private $db;
    public function __construct() {
        try {
            $this->db = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB conn error']); exit;
        }
    }

    // Register: expects form-data (POST) and $_FILES for 'verification_doc' if role=lawyer
    public function register(array $post, array $files) {
        // map names from frontend: firstName, lastName, email, phone, password, role, gender, lawyerId, registerDate
        $first = trim($post['firstName'] ?? '');
        $last  = trim($post['lastName'] ?? '');
        $full  = trim("$first $last");
        $email = strtolower(trim($post['email'] ?? ''));
        $phone = trim($post['phone'] ?? '');
        $pass  = $post['password'] ?? '';
        $role  = $post['role'] ?? 'user'; // 'user' or 'lawyer'
        $gender= $post['gender'] ?? null;

        if (!$first || !$last || !$email || !$pass || !$role) {
            echo json_encode(['success'=>false,'message'=>'Missing required fields']); exit;
        }

        // check email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success'=>false,'message'=>'Email already registered']); exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $is_verified = ($role === 'lawyer') ? 0 : 1;

        // insert user
        $ins = $this->db->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, gender, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $ins->execute([$full, $email, $phone, $hash, $role, $gender, $is_verified]);
        $userId = $this->db->lastInsertId();

        // if lawyer, save lawyer_details and upload doc
        if ($role === 'lawyer') {
            $lawyerId = $post['lawyerId'] ?? '';
            $registerDate = $post['registerDate'] ?? null;
            if (!$lawyerId || !$registerDate || !isset($files['verification_doc'])) {
                echo json_encode(['success'=>false,'message'=>'Missing lawyerId/registerDate/document']); exit;
            }

            $uploadRes = FileUploader::upload($files['verification_doc'], 'lawyer_');
            if (!$uploadRes['success']) {
                echo json_encode(['success'=>false,'message'=>$uploadRes['message']]); exit;
            }
            $docPath = $uploadRes['relative'];

            $ins2 = $this->db->prepare("INSERT INTO lawyer_details (user_id, lawyer_id, full_name, register_date, document_path, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $ins2->execute([$userId, $lawyerId, $full, $registerDate, $docPath]);
        }

        // auto-login: start session
        session_start();
        $_SESSION['user_id'] = (int)$userId;
        $_SESSION['full_name'] = $full;
        $_SESSION['role'] = $role;
        $_SESSION['is_verified'] = $is_verified;

        echo json_encode(['success'=>true,'message'=>'Registered and logged in','role'=>$role]); exit;
    }

    // Login: accepts JSON or form POST (email/username and password)
    public function login(array $input) {
        // accept both 'username' or 'email'
        $identifier = strtolower(trim($input['username'] ?? $input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (!$identifier || !$password) { echo json_encode(['success'=>false,'message'=>'Missing credentials']); exit; }

        $stmt = $this->db->prepare("SELECT id, full_name, password_hash, role, is_verified FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success'=>false,'message'=>'Invalid email or password']); exit;
        }

        if ($user['role'] === 'lawyer' && (int)$user['is_verified'] === 0) {
            echo json_encode(['success'=>false,'message'=>'Lawyer account pending verification']); exit;
        }

        session_start();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_verified'] = (int)$user['is_verified'];

        echo json_encode(['success'=>true,'message'=>'Login successful','role'=>$user['role']]); exit;
    }

    // Logout
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        echo json_encode(['success'=>true,'message'=>'Logged out']); exit;
    }

    // Admin: verify or reject a lawyer (lawyer_details.id and action 'approve'/'reject')
    public function verifyLawyer(int $detailsId, string $action) {
        session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
        }
        $action = strtolower($action) === 'approve' ? 'approved' : 'rejected';
        // update lawyer_details
        $u = $this->db->prepare("UPDATE lawyer_details SET status = ? WHERE id = ?");
        $u->execute([$action, $detailsId]);
        if ($u->rowCount() === 0) {
            echo json_encode(['success'=>false,'message'=>'Not found or unchanged']); exit;
        }
        // if approved, mark user is_verified = 1
        if ($action === 'approved') {
            $get = $this->db->prepare("SELECT user_id FROM lawyer_details WHERE id = ?");
            $get->execute([$detailsId]);
            $r = $get->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $up = $this->db->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
                $up->execute([$r['user_id']]);
            }
        }
        echo json_encode(['success'=>true,'message'=>'Lawyer ' . $action]); exit;
    }
}



