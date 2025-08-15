<?php
// api/logout.php
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

require_once __DIR__ . '/../services/UserService.php';
$svc = new UserService();
$svc->logout();
