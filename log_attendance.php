<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Ensure the request is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fingerprint_id = $_POST['fingerprint_id'] ?? null;
    $section_code = $_POST['section_code'] ?? null;

    // Validate inputs
    if (!$fingerprint_id || !$section_code) {
        http_response_code(400);
        echo "Missing fingerprint_id or section_code.";
        exit;
    }

    try {
        // Step 1: Find the student_id from the fingerprints table
        $stmt = $pdo->prepare("
            SELECT student_id 
            FROM fingerprints 
            WHERE fingerprint_id = :fingerprint_id
        ");
        $stmt->execute(['fingerprint_id' => $fingerprint_id]);
        $fingerprintData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fingerprintData) {
            http_response_code(404);
            echo "No student found for the given fingerprint_id.";
            exit;
        }

        $student_id = $fingerprintData['student_id'];

        // Step 2: Validate the student in the given section
        $stmt = $pdo->prepare("
            SELECT name 
            FROM students 
            WHERE student_id = :student_id 
              AND section = (SELECT section_name FROM sections WHERE section_code = :section_code)
        ");
        $stmt->execute([
            'student_id' => $student_id,
            'section_code' => $section_code
        ]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            http_response_code(404);
            echo "No student found for the given fingerprint_id and section_code.";
            exit;
        }

        $student_name = $student['name'];

        // Step 3: Fetch the teacher's schedule for the current time and section
        $current_day = date("l"); // e.g., Monday, Tuesday, etc.
        $current_time = date("H:i:s"); // e.g., 14:30:00

        $scheduleStmt = $pdo->prepare("
            SELECT teacher_id, subject, room 
            FROM schedules 
            WHERE section_code = :section_code 
              AND day = :current_day 
              AND :current_time BETWEEN start_time AND end_time
        ");
        $scheduleStmt->execute([
            'section_code' => $section_code,
            'current_day' => $current_day,
            'current_time' => $current_time
        ]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            http_response_code(404);
            echo "No schedule found for the given section_code.";
            exit;
        }

        $teacher_id = $schedule['teacher_id'];
        $subject = $schedule['subject'];
        $room = $schedule['room'];

        // Step 4: Log attendance
        $current_time = date("Y-m-d H:i:s");
        $class_date = date("Y-m-d");

        // Check if the student has already logged time_in today for this section
        $checkStmt = $pdo->prepare("
            SELECT * 
            FROM attendance 
            WHERE student_id = :student_id 
              AND section_code = :section_code 
              AND class_date = :class_date
              AND time_out IS NULL
        ");
        $checkStmt->execute([
            'student_id' => $student_id,
            'section_code' => $section_code,
            'class_date' => $class_date
        ]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($checkResult) {
            // Update time_out for the existing record
            $updateStmt = $pdo->prepare("
                UPDATE attendance 
                SET time_out = :current_time 
                WHERE student_id = :student_id 
                  AND section_code = :section_code 
                  AND class_date = :class_date
                  AND time_out IS NULL
            ");
            $updateSuccess = $updateStmt->execute([
                'current_time' => $current_time,
                'student_id' => $student_id,
                'section_code' => $section_code,
                'class_date' => $class_date
            ]);

            if ($updateSuccess) {
                http_response_code(200);
                echo "Time-out logged successfully.";
            } else {
                http_response_code(500);
                echo "Failed to log time-out.";
            }
        } else {
            // Insert new attendance record for time_in
            $insertStmt = $pdo->prepare("
                INSERT INTO attendance 
                (teacher_id, section_code, student_id, student_name, subject, room, time_in, class_date, status) 
                VALUES (:teacher_id, :section_code, :student_id, :student_name, :subject, :room, :current_time, :class_date, 'Present')
            ");
            $insertSuccess = $insertStmt->execute([
                'teacher_id' => $teacher_id,
                'section_code' => $section_code,
                'student_id' => $student_id,
                'student_name' => $student_name,
                'subject' => $subject,
                'room' => $room,
                'current_time' => $current_time,
                'class_date' => $class_date
            ]);

            if ($insertSuccess) {
                http_response_code(200);
                echo "Time-in logged successfully.";
            } else {
                http_response_code(500);
                echo "Failed to log time-in.";
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid request method.";
}
