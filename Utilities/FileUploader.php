<?php
class FileUploader {
    public static function upload($file, $prefix = "file_") {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid($prefix) . "_" . basename($file['name']);
        $path = $uploadDir . "/" . $filename;
        $relativePath = "uploads/" . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return [false, "Document upload failed"];
        }
        return [true, $relativePath];
    }
}

