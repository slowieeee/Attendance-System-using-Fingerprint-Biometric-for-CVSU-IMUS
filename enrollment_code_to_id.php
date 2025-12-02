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

    $teacher = $stmt->fetch();
    if ($teacher) {
        http_response_code(200);
        echo $teacher['teacher_id'];
    } else {
        http_response_code(404);
        echo "Teacher not found.";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
