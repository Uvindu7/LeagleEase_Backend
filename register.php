<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

error_reporting(0);
ini_set('display_errors', 0);

// DB
$host = 'localhost';
$db = 'project';
$user = 'root';
$pass = 'uvindu';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["message" => "Database connection failed"]);
    exit();
}

$required = ['firstName', 'lastName', 'email', 'phone', 'password', 'role', 'gender'];
foreach ($required as $f) {
    if (!isset($_POST[$f]) || empty($_POST[$f])) {
        echo json_encode(["message" => "Missing field: $f"]);
        exit();
    }
}

$firstName = $conn->real_escape_string($_POST['firstName']);
$lastName = $conn->real_escape_string($_POST['lastName']);
$fullName = "$firstName $lastName";
$email = $conn->real_escape_string($_POST['email']);
$phone = $conn->real_escape_string($_POST['phone']);
$role = $conn->real_escape_string($_POST['role']);
$gender = $conn->real_escape_string($_POST['gender']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check email
$check = $conn->prepare("SELECT email FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$checkResult = $check->get_result();
if ($checkResult->num_rows > 0) {
    echo json_encode(["message" => "Email already registered"]);
    exit();
}

// Insert user
$stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, gender, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
$stmt->bind_param("ssssss", $fullName, $email, $phone, $password, $role, $gender);

if (!$stmt->execute()) {
    echo json_encode(["message" => "Registration failed: " . $stmt->error]);
    exit();
}

$userId = $conn->insert_id;

if ($role === 'lawyer') {
    if (
        isset($_POST['lawyerId'], $_POST['registerDate']) &&
        isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] === 0
    ) {
        $lawyerId = $conn->real_escape_string($_POST['lawyerId']);
        $registerDate = $conn->real_escape_string($_POST['registerDate']);

        // Save file
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['verification_doc'];
        $filename = uniqid("lawyer_") . "_" . basename($file['name']);
        $path = $uploadDir . "/" . $filename;
        $relativePath = "uploads/" . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            echo json_encode(["message" => "Document upload failed"]);
            exit();
        }

        $stmt2 = $conn->prepare("INSERT INTO lawyer_details (user_id, lawyer_id, full_name, register_date, document_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt2->bind_param("issss", $userId, $lawyerId, $fullName, $registerDate, $relativePath);

        if (!$stmt2->execute()) {
            echo json_encode(["message" => "Lawyer detail insert failed: " . $stmt2->error]);
            exit();
        }
    } else {
        echo json_encode(["message" => "Lawyer ID, date or document missing"]);
        exit();
    }
}

echo json_encode(["message" => "Registration successful"]);
$conn->close();

