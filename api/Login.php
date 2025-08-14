<?php
require_once __DIR__ . '/../services/UserService.php';

$userService = new UserService();
$userService->login($_POST);
