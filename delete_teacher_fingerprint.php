<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enrollment_code = $_POST['enrollment_code'] ?? null;

    if (!$enrollment_code) {
        http_response_code(400);
        echo "Missing enrollment code.";
        exit;
    }

    // Get the fingerprint ID before deletion
    $stmt = $pdo->prepare("SELECT fingerprint_id FROM teachers WHERE enrollment_code = :enrollment_code");
    $stmt->execute(['enrollment_code' => $enrollment_code]);
    $fingerprintData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fingerprintData) {
        http_response_code(404);
        echo "Enrollment code not found.";
        exit;
    }

    $fingerprint_id = $fingerprintData['fingerprint_id'];

    // Remove the fingerprint ID and template from the database
    $deleteStmt = $pdo->prepare("UPDATE teachers SET fingerprint_id = NULL, fingerprint_template = NULL WHERE enrollment_code = :enrollment_code");
    $deleteStmt->execute(['enrollment_code' => $enrollment_code]);

    if ($deleteStmt->rowCount() > 0) {
        http_response_code(200);
        echo "deleted";
    } else {
        http_response_code(500);
        echo "Failed to delete fingerprint data.";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
