
<?php
class ResponseHelper {
    public static function success($msg, $data = []) {
        echo json_encode([
            "success" => "success",
            "message" => htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'),
            "data" => $data
        ]);
        exit();
    }

    public static function error($msg) {
        echo json_encode([
            "status" => "error",
            "message" => htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
        ]);
        exit();
    }
}
?>