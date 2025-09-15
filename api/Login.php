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

$data = json_decode(file_get_contents("php://input"));
try {
    $conn = Database::connect();
    $auth = new AuthService($conn);

    if (empty($data->username) || empty($data->password)) {
        throw new Exception("Missing username or password");
    }

    $auth->login($data->username, $data->password);

    ResponseHelper::success("Login successful", ["role" => $_SESSION['role']]);
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
