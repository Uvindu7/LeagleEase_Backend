<?php
// ------------------ CORS ------------------
header("Access-Control-Allow-Origin: http://localhost:5173"); // your React app
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------ Stripe ------------------
require __DIR__ . '/vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51RwGmxC0IqUbyDKdpVIH236aAl4uYJszdjTiFfZsQBO50px0aQlkKGEHuFSaPAVTZQ4XvCRwO5lIcubBDqWNjFIY00qN83BjRQ'); // Replace with your Stripe secret key

header('Content-Type: application/json');

// Read booking data from frontend
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['slot'], $input['lawyer'], $input['price'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Consultation with ' . $input['lawyer'],
                    'description' => 'Slot: ' . $input['slot'] . ' | Notes: ' . ($input['description'] ?? ''),
                ],
                'unit_amount' => $input['price'], // in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost:5173/appointment?payment=success',
        'cancel_url' => 'http://localhost:5173/appointment?payment=failed',
    ]);

    echo json_encode(['id' => $checkout_session->id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
