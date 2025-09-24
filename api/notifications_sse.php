<?php
session_start();

$frontend_origin = "http://localhost:5173";
header("Access-Control-Allow-Origin: $frontend_origin");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");

require_once '../db/database.php';

// Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    echo "event: error\n";
    echo "data: User not logged in\n\n";
    flush();
    exit();
}

$client_id = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->connect();

    while (true) {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        echo "event: notifications\n";
        echo "data: " . json_encode($notifications) . "\n\n";
        ob_flush();
        flush();

        sleep(5); // poll interval
    }
} catch (Exception $e) {
    echo "event: error\n";
    echo "data: " . $e->getMessage() . "\n\n";
    flush();
}
