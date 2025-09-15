<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../db/database.php';
require_once '../services/user_service.php';
require_once '../utilities/response_helper.php';

session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Not authenticated");
    }

    $conn = Database::connect();
    $UserService = new UserService($conn);

    $user = $UserService->getUserDetails($_SESSION['user_id']);

    ResponseHelper::success("Lawyer details fetched", $user);
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
