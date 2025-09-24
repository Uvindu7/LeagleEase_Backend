<?php
header("Access-Control-Allow-Origin: *"); // allow all origins (for dev)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

header("Content-Type: application/json");
require_once __DIR__ . '/../services/password_service.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $newPassword = $_POST['password']; // ✅ matches frontend key
    $service = new PasswordService();
    $result = $service->changePassword($email, $newPassword);
    echo json_encode($result);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Email and password are required"
    ]);
}
