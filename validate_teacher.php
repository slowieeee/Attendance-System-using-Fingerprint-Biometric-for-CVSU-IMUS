<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enrollment_code = $_POST['enrollment_code'] ?? null;

    if (!$enrollment_code) {
        http_response_code(400);
        echo "Missing enrollment code.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE enrollment_code = :enrollment_code");
    $stmt->execute(['enrollment_code' => $enrollment_code]);

    if ($stmt->fetch()) {
        http_response_code(200);
        echo "valid"; // Enrollment code exists
    } else {
        http_response_code(404);
        echo "invalid"; // Enrollment code does not exist
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
