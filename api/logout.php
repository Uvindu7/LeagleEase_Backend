<?php
require_once '../services/AuthService.php';
require_once '../utilities/ResponseHelper.php';
require_once '../db/Database.php';

try {
    $conn = Database::connect();
    $auth = new AuthService($conn);
    $auth->logout();
    ResponseHelper::success("Logged out");
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
