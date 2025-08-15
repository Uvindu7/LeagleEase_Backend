<?php
class LawyerService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function saveLawyerDetails($userId, $lawyerId, $fullName, $registerDate, $docPath) {
        $stmt = $this->conn->prepare("INSERT INTO lawyer_details (user_id, lawyer_id, full_name, register_date, document_path, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issss", $userId, $lawyerId, $fullName, $registerDate, $docPath);

        if (!$stmt->execute()) {
            throw new Exception("Lawyer detail insert failed: " . $stmt->error);
        }
    }
}
