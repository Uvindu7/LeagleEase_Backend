<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../db/database.php';
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
        http_response_code(200);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized");
    }
    $lawyer_id = intval($_SESSION['user_id']);

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['availability']) || !is_array($input['availability'])) {
        throw new Exception("Invalid input. Expecting availability array.");
    }

    // Limit to max 5 days
    if (count($input['availability']) > 5) {
        throw new Exception("You can only set up to 5 days of availability.");
    }

    $conn = Database::connect();

    // Insert new availability
    $insert = $conn->prepare("INSERT INTO lawyer_availability (lawyer_id, available_date, start_time) VALUES (?, ?, ?)");
    foreach ($input['availability'] as $day) {
        if (!isset($day['date']) || !isset($day['slots']) || !is_array($day['slots'])) {
            continue;
        }
        $date = $day['date'];

        foreach ($day['slots'] as $slot) {
            $insert->bind_param("iss", $lawyer_id, $date, $slot);
            $insert->execute();
        }
    }
    $insert->close();

    // Fetch updated availability
    $stmt = $conn->prepare("
        SELECT available_date, start_time 
        FROM lawyer_availability 
        WHERE lawyer_id = ? AND available_date >= CURDATE()
        ORDER BY available_date ASC, start_time ASC
    ");
    $stmt->bind_param("i", $lawyer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $availability = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['available_date'];
        if (!isset($availability[$date])) $availability[$date] = [];
        $availability[$date][] = $row['start_time'];
    }
    $stmt->close();

    $availabilityArray = [];
    foreach ($availability as $date => $slots) {
        $availabilityArray[] = ['date' => $date, 'slots' => $slots];
    }

    echo json_encode([
        "success" => "success",
        "message" => "Availability updated successfully.",
        "data" => $availabilityArray
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => "error",
        "message" => $e->getMessage()
    ]);
}
