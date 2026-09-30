<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: public, max-age=60"); // cache for 60s on repeat visits

require_once '../db/database.php';

try {
    $conn = Database::connect();

    // Get lawyer_id from query
    $lawyer_id = isset($_GET['lawyer_id']) ? intval($_GET['lawyer_id']) : 0;
    if ($lawyer_id <= 0) {
        throw new Exception("Invalid lawyer_id");
    }

    // Current date and time (separate for index-friendly comparison)
    $today     = date("Y-m-d");
    $nowTime   = date("H:i:s");
    $nowDT     = date("Y-m-d H:i:s");

    // Fetch available (non-booked) upcoming slots using index-friendly comparisons.
    // We use DATE()/TIME() on ca.appointment_date so the JOIN can leverage the
    // idx_appt_lawyer_date_status index instead of scanning the whole table.
    $stmt = $conn->prepare("
        SELECT la.available_date, la.start_time
        FROM lawyer_availability la
        LEFT JOIN client_appointments ca
          ON  ca.lawyer_id        = la.lawyer_id
          AND DATE(ca.appointment_date) = la.available_date
          AND TIME(ca.appointment_date) = la.start_time
          AND ca.status           = 'confirmed'
        WHERE la.lawyer_id = ?
          AND (
                la.available_date > ?
             OR (la.available_date = ? AND la.start_time >= ?)
          )
          AND ca.appointment_id IS NULL
        ORDER BY la.available_date ASC, la.start_time ASC
        LIMIT 100
    ");
    $stmt->bind_param("isss", $lawyer_id, $today, $today, $nowTime);
    $stmt->execute();
    $result = $stmt->get_result();
    $availability = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['available_date'];
        if (!isset($availability[$date])) {
            $availability[$date] = [];
        }
        // Format time as H:i (e.g. "09:00") for cleaner display
        $availability[$date][] = date("g:i A", strtotime($row['start_time']));
    }

    // Keep only first 5 dates
    $availabilityArray = [];
    $count = 0;
    foreach ($availability as $date => $slots) {
        if ($count >= 5) break;
        $availabilityArray[] = [
            'date'  => date("D, M j", strtotime($date)), // e.g. "Wed, Oct 2"
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
