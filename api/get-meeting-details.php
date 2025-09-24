<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Your database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

try {
    $appointment_id = $_GET['appointment_id'] ?? null;
    $your_user_id = $_GET['user_id'] ?? null;
    
    if (!$appointment_id || !$your_user_id) {
        throw new Exception("Appointment ID and User ID are required.");
    }

    $sql = "SELECT client_id, lawyer_id, appointment_date FROM client_appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment) {
        throw new Exception("Appointment not found.");
    }
    
    $other_party_id = ($appointment['client_id'] == $your_user_id) ? $appointment['lawyer_id'] : $appointment['client_id'];
    
    $sql_your_name = "SELECT full_name FROM users WHERE id = ?";
    $stmt_your_name = $conn->prepare($sql_your_name);
    $stmt_your_name->bind_param("i", $your_user_id);
    $stmt_your_name->execute();
    $result_your_name = $stmt_your_name->get_result();
    $your_name = $result_your_name->fetch_assoc();
    
    $sql_other_name = "SELECT full_name FROM users WHERE id = ?";
    $stmt_other_name = $conn->prepare($sql_other_name);
    $stmt_other_name->bind_param("i", $other_party_id);
    $stmt_other_name->execute();
    $result_other_name = $stmt_other_name->get_result();
    $other_name = $result_other_name->fetch_assoc();

    $roomName = 'leagle-' . $appointment_id . '-' . uniqid();

    echo json_encode([
        'roomName' => $roomName,
        'otherPartyName' => $other_name['full_name'],
        'yourName' => $your_name['full_name'],
        'appointmentTime' => $appointment['appointment_date']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>