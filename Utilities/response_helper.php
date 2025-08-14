<?php
// Utilities/ResponseHelper.php
class ResponseHelper {
    public static function json($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => $success,
            "message" => $message,
            "data"    => $data
        ]);
        exit();
    }
}
