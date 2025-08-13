<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: *");

require_once __DIR__ . '/../services/Userservice.php';

$userService = new UserService();
$userService->registerUser($_POST, $_FILES);

