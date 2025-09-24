<?php
session_start();
$client_id = $_SESSION['user_id'];
session_write_close(); // release the lock


$frontend_origin = "http://localhost:5173";


// CORS headers
header("Access-Control-Allow-Origin: $frontend_origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../db/database.php';

try {
    // Ensure user is logged in
    if (!isset($client_id)) {
        http_response_code(401);
        echo json_encode(['success' => 'error', 'message' => 'User not logged in']);
        exit();
    }

    $conn = Database::connect();

    $stmt = $conn->prepare("
        SELECT 
            ca.appointment_id,
            ca.appointment_date,
            ca.status,
            u.full_name AS lawyer_name,
            u.profile_picture AS lawyer_profile,
            ld.specialization,
            ld.fee
        FROM client_appointments ca
        JOIN users u ON u.id = ca.lawyer_id
        JOIN lawyer_details ld ON ld.user_id = ca.lawyer_id
        WHERE ca.client_id = ?
        ORDER BY ca.appointment_date ASC
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'appointment_id' => $row['appointment_id'],
            'appointment_date' => $row['appointment_date'],
            'status' => $row['status'],
            'lawyer_name' => $row['lawyer_name'],
            'lawyer_profile' => $row['lawyer_profile'] ?: '/default-profile.png',
            'specialization' => $row['specialization'],
            'fee' => $row['fee']
        ];
    }

    echo json_encode([
        "success" => "success",
        "message" => "Appointments fetched",
        "data" => $appointments
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
