<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enrollment_code = $_POST['enrollment_code'] ?? null;
    $otp = $_POST['otp'] ?? null;

    if (!$enrollment_code || !$otp) {
        http_response_code(400);
        echo "Missing reference ID or OTP.";
        exit;
    }

    // Verify OTP in the database
    $stmt = $pdo->prepare("
        SELECT otp, otp_expires_at
        FROM teachers
        WHERE enrollment_code = :enrollment_code
    ");
    $stmt->execute(['enrollment_code' => $enrollment_code]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        http_response_code(404);
        echo "Invalid reference ID.";
        exit;
    }

    // Check if the OTP matches and is still valid
    if ($teacher['otp'] === $otp && strtotime($teacher['otp_expires_at']) > time()) {
        // OTP is valid
        http_response_code(200);
        echo "valid";
    } else {
        // OTP is invalid or expired
        http_response_code(400);
        echo "invalid";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
