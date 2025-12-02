<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo "Missing student ID.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);

    if ($stmt->fetch()) {
        http_response_code(200);
        echo "valid"; // Student ID exists
    } else {
        http_response_code(404);
        echo "invalid"; // Student ID does not exist
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
