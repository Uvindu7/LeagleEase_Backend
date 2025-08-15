<?php
class FileUploader {
    public static function upload($file) {
        if ($file['error'] !== 0) {
            throw new Exception("Upload error");
        }

        $filename = uniqid("lawyer_") . "_" . basename($file['name']);
        $path = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception("File upload failed");
        }

        return 'uploads/' . $filename;
    }
}
