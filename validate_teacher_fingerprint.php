<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Check if POST request contains fingerprint_id
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fingerprint_id = $_POST['fingerprint_id'] ?? null;

    if (!$fingerprint_id) {
        http_response_code(400);
        echo "Missing fingerprint_id.";
        exit;
    }

    // Debugging log
    error_log("Received fingerprint_id: " . $fingerprint_id);

    try {
        // Query to check if fingerprint_id exists in the teachers table
        $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE fingerprint_id = :fingerprint_id");
        $stmt->execute(['fingerprint_id' => $fingerprint_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teacher) {
            http_response_code(200);
            echo "valid"; // Fingerprint found
        } else {
            http_response_code(404);
            echo "invalid"; // Fingerprint not found
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
