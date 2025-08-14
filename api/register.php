<?php
require_once __DIR__ . '/../services/UserService.php';

$userService = new UserService();
$userService->register($_POST, $_FILES);
