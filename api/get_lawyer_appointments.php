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

    // Join client_appointments with users (for client details)
    $stmt = $conn->prepare("
        SELECT 
            ca.appointment_id, 
            ca.appointment_date, 
            ca.status, 
            u.full_name AS client_name, 
            u.email AS client_email,
            u.phone AS client_phone,
            u.profile_picture AS client_profile
        FROM client_appointments ca
        JOIN users u ON ca.client_id = u.id
        WHERE ca.lawyer_id = ? 
          AND ca.appointment_date >= NOW()
        ORDER BY ca.appointment_date ASC
    ");
    $stmt->bind_param("i", $lawyer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode([
        "success" => "success",
        "data" => $appointments
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => "error",
        "message" => $e->getMessage()
    ]);
}
