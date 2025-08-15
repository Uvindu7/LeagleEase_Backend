<?php
// api/register.php
header('Access-Control-Allow-Origin: http://localhost:3000'); // change to your frontend origin
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../services/UserService.php';

$svc = new UserService();
$svc->register($_POST, $_FILES);
