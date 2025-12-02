<?php
require 'config.php'; // Include your config.php for database connection

// Ensure the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reference_id = $_POST['reference_id'] ?? null; // This could be student_id or enrollment_code
    $is_teacher = $_POST['is_teacher'] ?? null;

    if (!$reference_id || $is_teacher === null) {
        http_response_code(400);
        echo "Missing reference ID or is_teacher flag.";
        exit;
    }

    // Prepare the SQL query based on whether it's a teacher or a student
    if ($is_teacher == "1") {
        // Fetch fingerprint_id for a teacher
        $stmt = $pdo->prepare("SELECT fingerprint_id FROM teachers WHERE enrollment_code = :reference_id");
    } else {
        // Fetch fingerprint_id for a student
        $stmt = $pdo->prepare("SELECT fingerprint_id FROM fingerprints WHERE student_id = :reference_id");
    }

    $stmt->execute([':reference_id' => $reference_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        http_response_code(200);
        echo $result['fingerprint_id'];
    } else {
        http_response_code(404);
        echo "Fingerprint ID not found.";
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
