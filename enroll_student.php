<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Input validation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST['reference_id'] ?? null; // Student ID input by the user
    $fingerprint_template = $_POST['fingerprint_template'] ?? null;
    $fingerprint_id = $_POST['fingerprint_id'] ?? null;

    if (!$student_id || !$fingerprint_template || !$fingerprint_id) {
        http_response_code(400);
        echo "Missing student ID, fingerprint template, or fingerprint ID.";
        exit;
    }

    try {
        // Validate the existence of student_id in the students table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $student_id]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            http_response_code(404);
            echo "Student not found.";
            exit;
        }

        // Insert or update the fingerprint in the fingerprints table
        $stmt = $pdo->prepare("
            INSERT INTO fingerprints (student_id, fingerprint_id, fingerprint_template)
            VALUES (:student_id, :fingerprint_id, :fingerprint_template)
            ON DUPLICATE KEY UPDATE
            fingerprint_template = VALUES(fingerprint_template),
            fingerprint_id = VALUES(fingerprint_id)
        ");
        $success = $stmt->execute([
            'student_id' => $student_id,
            'fingerprint_id' => $fingerprint_id,
            'fingerprint_template' => $fingerprint_template
        ]);

        if ($success) {
            http_response_code(200);
            echo "success";
        } else {
            http_response_code(500);
            echo "Failed to save fingerprint data.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
