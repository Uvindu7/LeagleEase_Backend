<?php
header("Access-Control-Allow-Origin: *"); // allow all origins
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

header("Content-Type: application/json");
require_once __DIR__ . '/../services/password_service.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];
    $service = new PasswordService();
    $result = $service->sendOtp($email);
    echo json_encode($result);
} else {
    echo json_encode(["success" => false, "error" => "Email is required"]);
}
