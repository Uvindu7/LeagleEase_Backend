<?php
class LawyerService {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function saveLawyerDetails($userId, $lawyerId, $registerDate, $docPath, $specialization) {
        $stmt = $this->conn->prepare("
            INSERT INTO lawyer_details 
            (user_id, lawyer_id, register_date, document_path, specialization, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("issss", $userId, $lawyerId, $registerDate, $docPath, $specialization);

        if (!$stmt->execute()) {
            throw new Exception("Lawyer detail insert failed: " . $stmt->error);
        }
    }
}
