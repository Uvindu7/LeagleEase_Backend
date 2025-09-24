<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../db/Database.php';
require_once '../services/user_service.php';
require_once '../services/lawyer_service.php';
require_once '../Utilities/AzureHelper.php';
require_once '../utilities/response_helper.php';

try {
    $conn = Database::connect();
    $userService = new UserService($conn);
    $lawyerService = new LawyerService($conn);

    // ✅ Required fields for all users
    $required = ['firstName', 'lastName', 'email', 'phone', 'password', 'role', 'gender'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) throw new Exception("Missing field: $field");
    }

    // ✅ Check email already registered
    if ($userService->isEmailTaken($_POST['email'])) {
        throw new Exception("Email already registered");
    }

    // ✅ Register user
    $userService->register($_POST, $userId, $fullName);

    // ✅ If Lawyer role → Save lawyer details with Azure upload
    if ($_POST['role'] === 'lawyer') {
        $lawyerRequired = ['lawyerId', 'registerDate', 'specialization'];
        foreach ($lawyerRequired as $field) {
            if (empty($_POST[$field])) throw new Exception("Missing field: $field");
        }
        if (!isset($_FILES['verification_doc'])) throw new Exception("Verification document missing");

        // Upload verification doc to Azure Blob
        $azure = new AzureHelper();
        $fileName = "lawyer_" . $userId . "_" . time() . ".pdf";
        $uploadResult = $azure->uploadFile("verificationdoc", $_FILES['verification_doc']['tmp_name'], $fileName);

        if (!$uploadResult['success']) {
            throw new Exception("Verification doc upload failed: " . $uploadResult['message']);
        }

        $lawyerService->saveLawyerDetails(
            $userId,
            $_POST['lawyerId'],
            $_POST['registerDate'],
            $uploadResult['url'],
            $_POST['specialization']
        );
    }

    ResponseHelper::success("✅ Registration successful");
} catch (Exception $e) {
    ResponseHelper::error($e->getMessage());
}
