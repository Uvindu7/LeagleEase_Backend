<?php
session_start(); // make sure sessions are enabled
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

require_once '../db/Database.php';

$conn = Database::connect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    // Get logged-in user from session
    $userId = $_SESSION['user_id'] ?? null;
    $name   = $_SESSION['user_name'] ?? '';
    $email  = $_SESSION['user_email'] ?? '';

    $message = $data['message'] ?? '';

    if (empty($userId) || empty($message)) {
        echo json_encode(["success" => false, "message" => "User not logged in or message empty"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO feedback (user_id, name, email, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $name, $email, $message);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Feedback submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $sql = "SELECT f.*, u.username 
            FROM feedback f 
            LEFT JOIN users u ON f.user_id = u.id 
            ORDER BY f.created_at DESC";
    $result = $conn->query($sql);

    $feedback = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $feedback[] = $row;
        }
    }

    echo json_encode(["success" => true, "data" => $feedback]);
}
