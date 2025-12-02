<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Check request method
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture POST data
    $enrollment_code = $_POST['reference_id'] ?? null; // Reference to the enrollment_code column
    $fingerprint_id = $_POST['fingerprint_id'] ?? null;
    $fingerprint_template = $_POST['fingerprint_template'] ?? null;

    // Validate inputs
    if (!$enrollment_code || !$fingerprint_id || !$fingerprint_template) {
        http_response_code(400);
        echo "Missing enrollment code, fingerprint template, or fingerprint ID.";
        exit;
    }

    try {
        // Check if the teacher exists using the enrollment_code
        $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE enrollment_code = :enrollment_code");
        $stmt->execute(['enrollment_code' => $enrollment_code]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacher) {
            http_response_code(404);
            echo "Teacher not found.";
            exit;
        }

        // Get the teacher's ID
        $teacher_id = $teacher['teacher_id'];

        // Update the teacher's fingerprint ID and fingerprint template
        $updateStmt = $pdo->prepare("
            UPDATE teachers 
            SET fingerprint_id = :fingerprint_id, fingerprint_template = :fingerprint_template 
            WHERE enrollment_code = :enrollment_code
        ");
        $success = $updateStmt->execute([
            'fingerprint_id' => $fingerprint_id,
            'fingerprint_template' => $fingerprint_template,
            'enrollment_code' => $enrollment_code
        ]);

        if ($success) {
            http_response_code(200);
            echo "success";
        } else {
            http_response_code(500);
            echo "Failed to update fingerprint data.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
?>
