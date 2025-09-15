<?php
session_start();
header("Content-Type: application/json");
require_once "../db/database.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => "error", "message" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => "error", "message" => "Invalid input"]);
    exit;
}

try {
    $conn = Database::connect();
    $userId = $_SESSION['user_id'];

    $fullName = htmlspecialchars($data['full_name']);
    $email = htmlspecialchars($data['email']);
    $profilePhoto = htmlspecialchars($data['profile_photo']);
    $password = $data['password'];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name=?, email=?, profile_photo=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullName, $email, $profilePhoto, $hashedPassword, $userId]);
    } else {
        $sql = "UPDATE users SET full_name=?, email=?, profile_photo=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullName, $email, $profilePhoto, $userId]);
    }

    echo json_encode(["success" => "success", "message" => "Profile updated successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => "error", "message" => $e->getMessage()]);
}
