<?php
require __DIR__ . '/vendor/autoload.php'; // Use Composer's autoload
require 'config.php'; // Use the external config.php for database connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enrollment_code = $_POST['enrollment_code'] ?? null;

    if (!$enrollment_code) {
        http_response_code(400);
        echo "Missing enrollment code.";
        exit;
    }

    try {
        // Fetch teacher details based on enrollment code
        $stmt = $pdo->prepare("SELECT email, name FROM teachers WHERE enrollment_code = :enrollment_code");
        $stmt->execute(['enrollment_code' => $enrollment_code]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacher) {
            http_response_code(404);
            echo "Teacher not found.";
            exit;
        }

        $email = $teacher['email'];
        $name = $teacher['name'];

        // Generate OTP
        $otp = mt_rand(100000, 999999);

        // Update OTP in the database
        $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $updateStmt = $pdo->prepare("UPDATE teachers SET otp = :otp, otp_expires_at = :expires_at WHERE enrollment_code = :enrollment_code");
        if (!$updateStmt->execute(['otp' => $otp, 'expires_at' => $expires_at, 'enrollment_code' => $enrollment_code])) {
            http_response_code(500);
            echo "Failed to save OTP.";
            exit;
        }

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'cvsuimusattendance@gmail.com'; // Replace with your email
            $mail->Password = 'mzls tsqj gqqi pauh';         // Replace with your email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@example.com', 'Cvsu-Imus Attendance System');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code // DO NOT REPLY';
            $mail->Body = "<p>Hi $name,</p><p>Your OTP code is <strong>$otp</strong>.</p><p>This code will expire in 10 minutes.</p>";

            $mail->send();

            http_response_code(200);
            echo "otp_sent";
        } catch (Exception $e) {
            http_response_code(500);
            echo "Failed to send OTP.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
