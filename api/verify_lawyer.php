<?php
require_once '../db/Database.php';
require_once '../utilities/ResponseHelper.php';

$conn = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lawyerId = $_POST['lawyerId'];
    $stmt = $conn->prepare("UPDATE lawyer_details SET status = 'verified' WHERE lawyer_id = ?");
    $stmt->bind_param("s", $lawyerId);
    if ($stmt->execute()) {
        ResponseHelper::success("Lawyer verified");
    } else {
        ResponseHelper::error("Verification failed");
    }
}
