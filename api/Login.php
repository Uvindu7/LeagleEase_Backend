<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../services/Userservice.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['username'] ?? '';
$password = $data['password'] ?? '';

$userService = new UserService();
$userService->loginUser($email, $password);


