<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo "Missing student ID.";
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM fingerprints WHERE student_id = ?");
    $stmt->execute([$student_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo "deleted";
    } else {
        http_response_code(404);
        echo "Fingerprint not found.";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
