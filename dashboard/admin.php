<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.html'); exit;
}
require_once __DIR__ . '/../config/config.php';
$pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
$rows = $pdo->query("SELECT ld.id, ld.full_name, ld.lawyer_id, ld.register_date, ld.document_path, ld.status, u.email FROM lawyer_details ld JOIN users u ON u.id = ld.user_id WHERE ld.status = 'pending'")->fetchAll(PDO::FETCH_ASSOC);
