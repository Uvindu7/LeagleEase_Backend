<?php
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/database.php';

$lawyer_id = $_GET['lawyer_id'] ?? null;
$slot = $_GET['slot'] ?? null;
$client_id = $_GET['client_id'] ?? null;

if (!$lawyer_id || !$slot || !$client_id) {
    header("Location: http://localhost:5173/bookappointment?payment=failed");
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect(); // mysqli object

    // Ensure client exists
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    $stmt->close();

    if (!$client) {
        header("Location: http://localhost:5173/bookappointment?payment=failed");
        exit();
    }

    // Get lawyer details
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $lawyer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lawyer = $result->fetch_assoc();
    $stmt->close();

    if (!$lawyer) {
        header("Location: http://localhost:5173/bookappointment?payment=failed");
        exit();
    }

    // Insert appointment
    $stmt = $conn->prepare("INSERT INTO client_appointments (client_id, lawyer_id, appointment_date, status) VALUES (?, ?, ?, ?)");
    $status = 'confirmed';
    $stmt->bind_param("iiss", $client_id, $lawyer_id, $slot, $status);
    $stmt->execute();
    $stmt->close();

    // 🔔 Insert payment success notification for client
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, subject, message) VALUES (?, ?, ?)");
    $subject = "Payment Successful";
    $message = "Your payment for the appointment slot {$slot} was successful. Appointment confirmed.";
    $stmt->bind_param("iss", $client_id, $subject, $message);
    $stmt->execute();
    $stmt->close();

    // 🔔 Notification for lawyer - new appointment
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, subject, message) VALUES (?, ?, ?)");
    $subject = "New Appointment Booked";
    $message = "You have a new appointment with {$client['full_name']} on {$slot}.";
    $stmt->bind_param("iss", $lawyer_id, $subject, $message);
    $stmt->execute();
    $stmt->close();

    // 🔔 Notification for client - appointment confirmed with lawyer
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, subject, message) VALUES (?, ?, ?)");
    $subject = "Appointment Confirmed";
    $message = "Your appointment with {$lawyer['full_name']} is confirmed for {$slot}.";
    $stmt->bind_param("iss", $client_id, $subject, $message);
    $stmt->execute();
    $stmt->close();

    // Redirect to frontend with success
    header("Location: http://localhost:5173/bookappointment?payment=success");
    exit();

} catch (Exception $e) {
    error_log($e->getMessage());

    // 🔔 Insert payment failed notification
    if ($client_id) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, subject, message) VALUES (?, ?, ?)");
        $subject = "Payment Failed";
        $message = "Your payment for the appointment slot {$slot} failed.";
        $stmt->bind_param("iss", $client_id, $subject, $message);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: http://localhost:5173/bookappointment?payment=failed");
    exit();
}
