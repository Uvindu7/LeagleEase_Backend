<?php
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PasswordService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Generate OTP and send email
    public function sendOtp($email) {
        $otp = rand(100000, 999999);
        $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Delete old OTP for this email
        $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE email=?");
        $stmt->execute([$email]);

        // Insert new OTP
        $stmt = $this->conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expires_at]);

        // Send mail
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'legaleaseproject1@gmail.com'; 
            $mail->Password   = 'yafd mvgt nzfd kdqm';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('legaleaseproject1@gmail.com', 'LegalEase');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Your Password Reset OTP";
            $mail->Body    = "<p>Your OTP is: <b>$otp</b></p><p>It will expire in 5 minutes.</p>";

            $mail->send();
            return ["success" => true];
        } catch (Exception $e) {
            return ["success" => false, "error" => "Mailer Error: {$mail->ErrorInfo}"];
        }
    }

    // Verify OTP
    public function verifyOtp($email, $otp) {
        $stmt = $this->conn->prepare("SELECT otp, expires_at FROM password_resets WHERE email=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['otp'] === $otp && strtotime($row['expires_at']) > time()) {
            return ["success" => true];
        } else {
            return ["success" => false, "error" => "Invalid or expired OTP"];
        }
    }

    // Change Password
    public function changePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->execute([$hashedPassword, $email]);

        // Delete OTP after successful reset
        $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE email=?");
        $stmt->execute([$email]);

        return ["success" => true, "message" => "Password updated successfully"];
    }
}
