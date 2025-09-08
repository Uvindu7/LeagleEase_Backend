<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../db/Database.php';
require_once '../services/user_service.php';
require_once '../services/lawyer_service.php';
require_once '../utilities/file_uploader.php';
require_once '../utilities/response_helper.php';

try {
    $conn = Database::connect();
    $userService = new UserService($conn);
    $lawyerService = new LawyerService($conn);

    $required = ['firstName', 'lastName', 'email', 'phone', 'password', 'role', 'gender'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) throw new Exception("Missing field: $field");
    }

    if ($userService->isEmailTaken($_POST['email'])) {
        throw new Exception("Email already registered");
    }

    $userService->register($_POST, $userId, $fullName);

    if ($_POST['role'] === 'lawyer') {
        if (empty($_POST['lawyerId']) || empty($_POST['registerDate']) || !isset($_FILES['verification_doc'])) {
            throw new Exception("Lawyer details or document missing");
        }

        $filePath = FileUploader::upload($_FILES['verification_doc']);
        $lawyerService->saveLawyerDetails($userId, $_POST['lawyerId'], $fullName, $_POST['registerDate'], $filePath);
    }

    ResponseHelper::success("Registration successful");
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
