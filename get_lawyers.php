<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Include database connection
require_once 'db_connect.php';

// Get query parameters
$specialization = $_GET['specialization'] ?? '';
$minRating = $_GET['minRating'] ?? '';
$minFee = $_GET['minFee'] ?? '';
$maxFee = $_GET['maxFee'] ?? '';

try {
    // Build base query
    $sql = "SELECT * FROM lawyers WHERE 1=1";

    if (!empty($specialization)) {
        $sql .= " AND specialization = :specialization";
    }
    if (!empty($minRating)) {
        $sql .= " AND rating >= :minRating";
    }
    if (!empty($minFee)) {
        $sql .= " AND fee >= :minFee";
    }
    if (!empty($maxFee)) {
        $sql .= " AND fee <= :maxFee";
    }

    $stmt = $pdo->prepare($sql);

    if (!empty($specialization)) {
        $stmt->bindParam(':specialization', $specialization);
    }
    if (!empty($minRating)) {
        $stmt->bindParam(':minRating', $minRating);
    }
    if (!empty($minFee)) {
        $stmt->bindParam(':minFee', $minFee);
    }
    if (!empty($maxFee)) {
        $stmt->bindParam(':maxFee', $maxFee);
    }

    $stmt->execute();
    $lawyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($lawyers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Query error: " . $e->getMessage()]);
}

