<?php
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_51RwGmxC0IqUbyDKdpVIH236aAl4uYJszdjTiFfZsQBO50px0aQlkKGEHuFSaPAVTZQ4XvCRwO5lIcubBDqWNjFIY00qN83BjRQ');

header('Content-Type: application/json');
$input = json_decode(file_get_contents("php://input"), true);

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Consultation with ' . $input['lawyer'],
                    'description' => 'Slot: ' . $input['slot'] . ' | Notes: ' . $input['description'],
                ],
                'unit_amount' => $input['price'], // in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost:5173/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost:5173/cancel',
    ]);

    echo json_encode(['id' => $checkout_session->id]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
