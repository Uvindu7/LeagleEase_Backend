<?php
header("Content-Type: application/json");
require_once '../db/Database.php';
require_once '../services/AuthService.php';
require_once '../utilities/ResponseHelper.php';

try {
    $conn = Database::connect();
    $auth = new AuthService($conn);

    if (empty($_POST['email']) || empty($_POST['password'])) {
        throw new Exception("Missing email or password");
    }

    $auth->login($_POST['email'], $_POST['password']);
    ResponseHelper::success("Login successful");
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
