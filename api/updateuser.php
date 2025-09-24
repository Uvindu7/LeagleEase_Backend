<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once "../db/Database.php";
require_once "../utilities/AzureHelper.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => "error", "message" => "Not logged in"]);
    exit;
}

try {
    $conn = Database::connect();
    $userId = $_SESSION['user_id'];

    $fullName = htmlspecialchars($_POST['full_name'] ?? "");
    $email = htmlspecialchars($_POST['email'] ?? "");

    // ✅ Handle profile photo upload
    $profilePhotoUrl = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $azure = new AzureHelper();
        $fileName = "profile_" . $userId . "_" . time() . ".jpg";
        $uploadResult = $azure->uploadFile("profilepic", $_FILES['profile_photo']['tmp_name'], $fileName);

        if (!$uploadResult['success']) {
            throw new Exception("Profile photo upload failed: " . $uploadResult['message']);
        }
        $profilePhotoUrl = $uploadResult['url'];
    }

    // ✅ Update DB
    if ($profilePhotoUrl) {
        $sql = "UPDATE users SET full_name=?, email=?, profile_picture=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullName, $email, $profilePhotoUrl, $userId]);
    } else {
        $sql = "UPDATE users SET full_name=?, email=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullName, $email, $userId]);
    }

    echo json_encode(["success" => "success", "message" => "Profile updated successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => "error", "message" => $e->getMessage()]);
}
