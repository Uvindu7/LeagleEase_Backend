<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . '/../services/password_service.php';

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// Get raw input (React will send JSON)
$email = $_POST['email'] ?? null;
$otp   = $_POST['otp'] ?? null;

if ($email && $otp) {
    $service = new PasswordService();
    $result = $service->verifyOtp($email, $otp);
    echo json_encode($result);
} else {
    echo json_encode(["success" => false, "error" => "Email and OTP are required"]);
}

