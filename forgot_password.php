<?php
require __DIR__ . '/vendor/autoload.php'; // Use Composer's autoload for PHPMailer
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$user_type = $data['user_type'] ?? null; // User type specified in the request

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

try {
    $teachers_user = null;
    $admins_user = null;

    // Check if email exists in teachers table
    $stmt = $pdo->prepare("SELECT teacher_id AS id, username, 'teachers' AS user_type FROM teachers WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $teachers_user = $stmt->fetch();

    // Check if email exists in admins table
    $stmt = $pdo->prepare("SELECT admin_id AS id, username, 'admins' AS user_type FROM admins WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admins_user = $stmt->fetch();

    // If email is not found in either table
    if (!$teachers_user && !$admins_user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email address.']);
        exit;
    }

    // If email exists in both tables, prompt for user type
    if ($teachers_user && $admins_user && !$user_type) {
        echo json_encode([
            'status' => 'choose',
            'message' => 'This email is associated with both a Teacher and an Admin account. Please select which account you want to reset the password for.',
            'options' => [
                ['user_type' => 'teachers', 'label' => 'Teacher'],
                ['user_type' => 'admins', 'label' => 'Admin'],
            ]
        ]);
        exit;
    }

    // Determine which account to reset
    $user = null;
    if ($user_type === 'teachers' && $teachers_user) {
        $user = $teachers_user;
    } elseif ($user_type === 'admins' && $admins_user) {
        $user = $admins_user;
    } elseif (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid account type selected.']);
        exit;
    }

    // Generate a new random password
    $newPassword = bin2hex(random_bytes(4)); // Generate an 8-character random password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update the password in the appropriate table
    if ($user['user_type'] === 'teachers') {
        $stmt = $pdo->prepare("UPDATE teachers SET password = :password WHERE email = :email");
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE email = :email");
    }
    $stmt->execute(['password' => $hashedPassword, 'email' => $email]);

    // Send the username and new password to the user's email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Replace with your email
        $mail->Password = '';         // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('cvsuimusattendance@gmail.com', 'CVSU-IMUS Attendance System');
        $mail->addAddress($email, $user['username']);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - CVSU-IMUS AMS';
        $mail->Body = "
            <p>Dear User,</p>
            <p>Your username: <strong>{$user['username']}</strong></p>
            <p>Your new password: <strong>{$newPassword}</strong></p>
            <p>Please log in and change your password immediately for security reasons.</p>
            <p>Regards,</p>
            <p>CVSU-IMUS Attendance System</p>
        ";

        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'Your username and new password have been sent to your email.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again later.']);
}

