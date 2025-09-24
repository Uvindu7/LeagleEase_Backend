<?php
session_start();
set_time_limit(0); // prevent timeout
ob_implicit_flush(true);

// CORS
$frontend_origin = "http://localhost:5173";
header("Access-Control-Allow-Origin: $frontend_origin");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");

// Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    echo "event: error\n";
    echo "data: " . json_encode(["message" => "User not logged in"]) . "\n\n";
    flush();
    exit();
}

$lawyer_id = $_SESSION['user_id'];

require_once '../db/database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    while (true) {
        $stmt = $conn->prepare("
            SELECT notification_id, user_id, subject, message, is_read, created_at
            FROM notifications
            WHERE user_id = ? AND subject = 'New Appointment Booked'
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $lawyer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        // Send notifications as SSE
        echo "event: notifications\n";
        echo "data: " . json_encode($notifications) . "\n\n";

        // Make sure data is sent immediately
        ob_flush();
        flush();

        // Sleep for 5 seconds before next poll
        sleep(5);
    }
} catch (Exception $e) {
    echo "event: error\n";
    echo "data: " . json_encode(["message" => $e->getMessage()]) . "\n\n";
    flush();
}
