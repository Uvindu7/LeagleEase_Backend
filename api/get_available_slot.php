<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "project";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

// Define all possible time slots
$allTimeSlots = ["05:00 PM","06:00 PM","07:00 PM","08:00 PM","09:00 PM"];

// Get upcoming 7 days
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date("Y-m-d", strtotime("+$i day"));
}

$resultData = [];

foreach ($days as $date) {
    $availableSlots = [];

    // Pick a random number of slots for the day
    $numSlots = rand(1, count($allTimeSlots));
    $randomSlots = array_rand(array_flip($allTimeSlots), $numSlots); // random unique slots

    // Ensure $randomSlots is always an array
    if (!is_array($randomSlots)) {
        $randomSlots = [$randomSlots];
    }

    // Check if slots are booked in DB
    foreach ($randomSlots as $slot) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lawyer_appoinments WHERE appoiment_date = ? AND appoinment_time = ?");
        $stmt->bind_param("ss", $date, $slot);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res['count'] == 0) {
            $availableSlots[] = $slot;
        }
    }

    $resultData[] = [
        "date" => $date,
        "available_slots" => $availableSlots
    ];
}

echo json_encode($resultData);
$conn->close();
?>
