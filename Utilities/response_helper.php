<?php
class ResponseHelper {
    public static function success($msg, $data = []) {
        echo json_encode(["success" => "success", "message" => $msg, "data" => $data]);
        exit();
    }

    public static function error($msg) {
        echo json_encode(["status" => "error", "message" => $msg]);
        exit();
    }
}
