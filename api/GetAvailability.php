<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials: true");

require_once '../db/database.php';
session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized");
    }
    $lawyer_id = $_SESSION['user_id'];

    $conn = Database::connect();
    $now = date("Y-m-d");

    $stmt = $conn->prepare("
        SELECT available_date, start_time 
        FROM lawyer_availability
        WHERE lawyer_id = ? AND available_date >= ?
        ORDER BY available_date ASC, start_time ASC
    ");
    $stmt->bind_param("is", $lawyer_id, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    $availability = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['available_date'];
        if (!isset($availability[$date])) $availability[$date] = [];
        $availability[$date][] = $row['start_time'];
    }

    $availabilityArray = [];
    foreach ($availability as $date => $slots) {
        $availabilityArray[] = ['date' => $date, 'slots' => $slots];
    }

    echo json_encode([
        "success" => "success",
        "data" => $availabilityArray
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => "error",
        "message" => $e->getMessage()
    ]);
}
