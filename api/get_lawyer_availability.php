<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

require_once '../db/database.php';

try {
    $conn = Database::connect();

    // Get lawyer_id from query
    $lawyer_id = isset($_GET['lawyer_id']) ? intval($_GET['lawyer_id']) : 0;
    if ($lawyer_id <= 0) {
        throw new Exception("Invalid lawyer_id");
    }

    // Current datetime
    $now = date("Y-m-d H:i:s");

    // Fetch available slots that are not booked
    $stmt = $conn->prepare("
        SELECT la.available_date, la.start_time
        FROM lawyer_availability la
        LEFT JOIN client_appointments ca
          ON ca.lawyer_id = la.lawyer_id
          AND ca.appointment_date = CONCAT(la.available_date, ' ', la.start_time)
          AND ca.status = 'confirmed'
        WHERE la.lawyer_id = ?
          AND CONCAT(la.available_date, ' ', la.start_time) >= ?
          AND ca.appointment_id IS NULL
        ORDER BY la.available_date ASC, la.start_time ASC
    ");
    $stmt->bind_param("is", $lawyer_id, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['available_date'];
        if (!isset($availability[$date])) {
            $availability[$date] = [];
        }
        $availability[$date][] = $row['start_time'];
    }

    // Keep only first 5 dates
    $availabilityArray = [];
    $count = 0;
    foreach ($availability as $date => $slots) {
        if ($count >= 5) break;
        $availabilityArray[] = [
            'date' => $date,
            'slots' => $slots
        ];
        $count++;
    }

    echo json_encode([
        "success" => "success",
        "message" => "Availability fetched",
        "data" => $availabilityArray
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
