<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db/database.php';

$conn = Database::connect();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE lawyer_details SET status = 'approved' WHERE user_id = $id";
    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Lawyer approved"]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "No ID provided"]);
}

$conn->close();
