<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../db/database.php';
require_once '../services/auth_service.php';
require_once '../utilities/response_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sanitize input
$input = json_decode(file_get_contents("php://input"));
$email = isset($input->username) ? filter_var(trim($input->username), FILTER_SANITIZE_EMAIL) : '';
$password = isset($input->password) ? trim($input->password) : '';

try {
    $conn = Database::connect();
    $auth = new AuthService($conn);

    if (empty($email) || empty($password)) {
        throw new Exception("Missing username or password");
    }

    // Call the login method and get the user data in return
    $userData = $auth->login($email, $password);

    if ($userData) {
        // Send the complete user data (id and role) back to the frontend
        ResponseHelper::success("Login successful", [
            "id" => $userData['id'],
            "role" => $userData['role']
        ]);
    } else {
        throw new Exception("Invalid credentials");
    }

} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
?>
