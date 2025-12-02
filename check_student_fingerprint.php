<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        http_response_code(400);
        echo "Missing student ID.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT fingerprint_template FROM fingerprints WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);
    $fingerprint = $stmt->fetch();

    if ($fingerprint && !empty($fingerprint['fingerprint_template'])) {
        http_response_code(200);
        echo "exists";
    } else {
        http_response_code(404);
        echo "not found";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
