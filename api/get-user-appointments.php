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
    $user_id = $_GET['user_id'] ?? null; 

    if (!$user_id) {
        throw new Exception("User ID is required.");
    }

    $sql = "SELECT appointment_id, appointment_date, client_id, lawyer_id 
            FROM client_appointments 
            WHERE client_id = ? OR lawyer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $other_party_id = ($row['client_id'] == $user_id) ? $row['lawyer_id'] : $row['client_id'];

        $sql_other_name = "SELECT full_name FROM users WHERE id = ?";
        $stmt_other_name = $conn->prepare($sql_other_name);
        $stmt_other_name->bind_param("i", $other_party_id);
        $stmt_other_name->execute();
        $result_other_name = $stmt_other_name->get_result();
        $other_name = $result_other_name->fetch_assoc();

        $row['otherPartyName'] = $other_name ? $other_name['full_name'] : 'N/A';
        $appointments[] = $row;
    }

    echo json_encode(['appointments' => $appointments]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>