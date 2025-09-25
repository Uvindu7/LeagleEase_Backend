<?php
session_start();

$frontend_origin = "http://localhost:5173";

// Always send these headers for every request
header("Access-Control-Allow-Origin: $frontend_origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true"); // must be true

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php';
require_once '../db/database.php';

\Stripe\Stripe::setApiKey('sk_test_51RwGmxC0IqUbyDKdpVIH236aAl4uYJszdjTiFfZsQBO50px0aQlkKGEHuFSaPAVTZQ4XvCRwO5lIcubBDqWNjFIY00qN83BjRQ');

$input = json_decode(file_get_contents("php://input"), true);

// Ensure user is logged in via session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$client_id = $_SESSION['user_id']; // Get client_id from session

if (!$input || !isset($input['slot'], $input['lawyer_id'], $input['price'], $input['lawyer_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Check if client exists in DB
try {
    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    if (!$client) {
        http_response_code(400);
        echo json_encode(['error' => 'Client does not exist']);
        exit();
    }

    // Create Stripe Checkout session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'lkr',
                'product_data' => [
                    'name' => 'Consultation with ' . $input['lawyer_name'],
                    'description' => 'Slot: ' . $input['slot'] . ' | Notes: ' . ($input['description'] ?? ''),
                ],
                'unit_amount' => $input['price'], // in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/backend/api/appointment_success.php?lawyer_id=' . $input['lawyer_id'] . '&slot=' . urlencode($input['slot']) . '&client_id=' . $client_id,
        'cancel_url' => 'http://localhost:5173/bookappointment?payment=failed',
    ]);

    echo json_encode(['id' => $checkout_session->id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
