<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials: true");

require_once '../db/database.php';

try {
    $conn = Database::connect();

    // Get filters from query params
    $specialization = $_GET['specialization'] ?? '';
    $minRating = isset($_GET['minRating']) ? floatval($_GET['minRating']) : null;
    $minFee = isset($_GET['minFee']) ? floatval($_GET['minFee']) : null;
    $maxFee = isset($_GET['maxFee']) ? floatval($_GET['maxFee']) : null;

    // Base SQL
    $sql = "SELECT 
                u.id,
                u.full_name AS name,
                COALESCE(u.profile_picture, '') AS image_url,
                ld.specialization,
                COALESCE(ld.fee, 0) AS fee,
                COALESCE(AVG(r.rating), 0) AS avg_rating
            FROM users u
            JOIN lawyer_details ld ON u.id = ld.user_id
            LEFT JOIN reviews r ON u.id = r.lawyer_id
            WHERE u.role = 'lawyer' AND ld.status = 'approved'";

    $params = [];
    $types = '';

    if ($specialization !== '') {
        $sql .= " AND ld.specialization = ?";
        $params[] = $specialization;
        $types .= 's';
    }
    if (!is_null($minFee)) {
        $sql .= " AND ld.fee >= ?";
        $params[] = $minFee;
        $types .= 'd';
    }
    if (!is_null($maxFee)) {
        $sql .= " AND ld.fee <= ?";
        $params[] = $maxFee;
        $types .= 'd';
    }

    $sql .= " GROUP BY u.id";

    if (!is_null($minRating)) {
        $sql .= " HAVING avg_rating >= ?";
        $params[] = $minRating;
        $types .= 'd';
    }

    $sql .= " ORDER BY avg_rating DESC, fee ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("SQL Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("SQL Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $lawyers = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => "success",
        "message" => "Lawyers fetched successfully",
        "data" => $lawyers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
