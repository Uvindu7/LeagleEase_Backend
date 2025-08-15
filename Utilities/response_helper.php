<?php
class ResponseHelper {
    public static function send($success, $message, $data = []) {
        echo json_encode([
            "success" => $success,
            "message" => $message,
            "data"    => $data
        ]);
        exit;
    }
}


