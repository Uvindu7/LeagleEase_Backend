<?php
// services/FileUploader.php
class FileUploader {
    private $uploadDir;

    public function __construct($uploadDir) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function upload($file, $prefix = '') {
        if (!isset($file) || $file['error'] !== 0) {
            return ["success" => false, "message" => "File not uploaded or invalid"];
        }

        $filename = uniqid($prefix) . "_" . basename($file['name']);
        $path = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ["success" => false, "message" => "Failed to save file"];
        }

        return [
            "success" => true,
            "path" => $path,
            "relative" => "uploads/" . $filename
        ];
    }
}
