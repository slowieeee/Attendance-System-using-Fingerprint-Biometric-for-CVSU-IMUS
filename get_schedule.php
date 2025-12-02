<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Ensure the request is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fingerprint_id = $_POST['fingerprint_id'] ?? null;

    if (!$fingerprint_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fingerprint_id."]);
        exit;
    }

    try {
        // Debugging: Log the received fingerprint_id
        error_log("Received fingerprint_id: $fingerprint_id");

        // Step 1: Get teacher_id using the fingerprint_id
        $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE fingerprint_id = :fingerprint_id");
        $stmt->execute(['fingerprint_id' => $fingerprint_id]);
        $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacherData) {
            http_response_code(404);
            echo json_encode(["error" => "No teacher found for the given fingerprint_id."]);
            exit;
        }

        $teacher_id = $teacherData['teacher_id'];

        // Step 2: Fetch today's schedule for the teacher
        $current_day = date("l"); // e.g., Monday, Tuesday, etc.
        $current_time = date("H:i:s"); // e.g., 14:30:00

        // Debugging: Log the current day and time
        error_log("Current Day: $current_day, Current Time: $current_time");

        $scheduleStmt = $pdo->prepare("
            SELECT section_code, start_time, end_time, day 
            FROM schedules 
            WHERE teacher_id = :teacher_id 
              AND day = :current_day 
              AND :current_time BETWEEN start_time AND end_time
        ");
        $scheduleStmt->execute([
            'teacher_id' => $teacher_id,
            'current_day' => $current_day,
            'current_time' => $current_time,
        ]);

        $schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($schedules)) {
            http_response_code(404);
            echo json_encode(["error" => "No schedule found for the given teacher_id and current time."]);
            exit;
        }

        // Debugging: Log the fetched schedules
        error_log("Schedules Found: " . json_encode($schedules));

        // Return schedule data as JSON
        http_response_code(200);
        echo json_encode($schedules);
    } catch (PDOException $e) {
        // Debugging: Log any database errors
        error_log("Database Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    // Debugging: Log invalid request method
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method."]);
}
