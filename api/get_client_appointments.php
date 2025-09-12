<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fixed client_id for testing
$clientId = 1;

// Corrected SQL using your actual columns
$sql = "
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.status,
    l.full_name
FROM client_appointments a
JOIN lawyer_details l ON a.lawyer_id = l.lawyer_id
WHERE a.client_id = ?
  AND a.appointment_date >= NOW()
ORDER BY a.appointment_date ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(["error" => "SQL prepare failed: " . $conn->error]));
}

$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        "id" => $row["appointment_id"],
        "date" => date("Y-m-d", strtotime($row["appointment_date"])),
        "time" => date("g:i A", strtotime($row["appointment_date"])),
        "lawyer" => $row["full_name"],
        "status" => ucfirst($row["status"])
    ];
}

echo json_encode($appointments);
$conn->close();
?>
