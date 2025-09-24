<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db/database.php';

$conn = Database::connect();

$sql = "SELECT u.id AS user_id, u.full_name as name, u.email, u.role, 
               u.profile_picture, 
               ld.status AS lawyer_status, 
               ld.specialization, 
               ld.fee
        FROM users u
        LEFT JOIN lawyer_details ld ON u.id = ld.user_id";

$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
$conn->close();
