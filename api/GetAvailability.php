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

    // Optimized query: group slots per date
    $stmt = $conn->prepare("
        SELECT available_date, GROUP_CONCAT(start_time ORDER BY start_time) AS slots
        FROM lawyer_availability
        WHERE lawyer_id = ? AND available_date >= ?
        GROUP BY available_date
        ORDER BY available_date ASC
    ");
    $stmt->bind_param("is", $lawyer_id, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    $availabilityArray = [];
    while ($row = $result->fetch_assoc()) {
        $slots = $row['slots'] ? explode(',', $row['slots']) : [];
        $availabilityArray[] = [
            'date' => $row['available_date'],
            'slots' => $slots
        ];
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

