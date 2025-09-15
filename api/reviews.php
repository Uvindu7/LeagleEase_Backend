<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173"); // safer than *
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "project"; // change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]));
}

// Handle GET: fetch reviews for a lawyer
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['lawyer_id'])) {
    $lawyer_id = intval($_GET['lawyer_id']);
    $sql = "SELECT review_id, appointment_id, client_id, lawyer_id, rating, comments, created_at 
            FROM reviews 
            WHERE lawyer_id = ? 
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $lawyer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    echo json_encode($reviews);
    exit;
}

// Handle POST: insert new review
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "Invalid JSON"]);
        exit;
    }

    $appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : null;
    $client_id = isset($data['client_id']) ? intval($data['client_id']) : null;
    $lawyer_id = intval($data['lawyer_id']);
    $rating = intval($data['rating']);
    $comments = $data['comments'];

    $sql = "INSERT INTO reviews (appointment_id, client_id, lawyer_id, rating, comments) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiis", $appointment_id, $client_id, $lawyer_id, $rating, $comments);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Review submitted"]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }
    exit;
}

// Default response
echo json_encode(["success" => false, "message" => "Invalid request"]);
$conn->close();
?>
