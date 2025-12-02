<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enrollment_code = $_POST['enrollment_code'] ?? null;

    if (!$enrollment_code) {
        http_response_code(400);
        echo "Missing enrollment code.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT fingerprint_template FROM teachers WHERE enrollment_code = :enrollment_code");
    $stmt->execute(['enrollment_code' => $enrollment_code]);
    $teacher = $stmt->fetch();

    if ($teacher && !empty($teacher['fingerprint_template'])) {
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
