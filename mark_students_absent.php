<?php
require 'config.php'; // Ensure this file contains the PDO setup

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Ensure the request is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $section_code = $_POST['section_code'] ?? null;

    // Validate input
    if (!$section_code) {
        http_response_code(400);
        echo json_encode(["error" => "Missing section_code."]);
        exit;
    }

    try {
        // Debugging: Log the received section_code
        error_log("Received section_code: $section_code");

        // Fetch teacher_id, subject, and room for the current section and schedule
        $scheduleStmt = $pdo->prepare("
            SELECT teacher_id, subject, room 
            FROM schedules 
            WHERE section_code = :section_code 
              AND day = DAYNAME(CURRENT_DATE())
              AND :current_time BETWEEN start_time AND end_time
        ");
        $scheduleStmt->execute([
            'section_code' => $section_code,
            'current_time' => date("H:i:s"),
        ]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            // Debugging: Log no active schedule found
            error_log("No active schedule found for section_code: $section_code at time: " . date("H:i:s"));
            http_response_code(404);
            echo json_encode(["error" => "No active schedule found for the given section_code."]);
            exit;
        }

        $teacher_id = $schedule['teacher_id'];
        $subject = $schedule['subject'];
        $room = $schedule['room'];

        // Debugging: Log the fetched schedule
        error_log("Fetched schedule - Teacher ID: $teacher_id, Subject: $subject, Room: $room");

        // Fetch students in the specified section who haven't logged attendance for today
        $class_date = date("Y-m-d");
        $stmt = $pdo->prepare("
            SELECT students.student_id, students.name 
            FROM students 
            WHERE section = (SELECT section_name FROM sections WHERE section_code = :section_code)
              AND student_id NOT IN (
                  SELECT student_id 
                  FROM attendance 
                  WHERE section_code = :section_code 
                    AND class_date = :class_date
              )
        ");
        $stmt->execute([
            'section_code' => $section_code,
            'class_date' => $class_date,
        ]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($students)) {
            // Mark absent for all students who haven't logged attendance
            $insertStmt = $pdo->prepare("
                INSERT INTO attendance (teacher_id, section_code, student_id, student_name, subject, room, class_date, status) 
                VALUES (:teacher_id, :section_code, :student_id, :student_name, :subject, :room, :class_date, 'Absent')
            ");

            foreach ($students as $student) {
                $insertStmt->execute([
                    'teacher_id' => $teacher_id,
                    'section_code' => $section_code,
                    'student_id' => $student['student_id'],
                    'student_name' => $student['name'],
                    'subject' => $subject,
                    'room' => $room,
                    'class_date' => $class_date,
                ]);

                // Debugging: Log each student marked absent
                error_log("Marked Absent - Student ID: " . $student['student_id'] . ", Name: " . $student['name']);
            }

            http_response_code(200);
            echo json_encode(["message" => "Students marked absent successfully."]);
        } else {
            // Debugging: Log if all students have logged attendance
            error_log("All students logged attendance or no students in section: $section_code");
            http_response_code(404);
            echo json_encode(["error" => "All students have logged attendance or no students in section."]);
        }
    } catch (PDOException $e) {
        // Debugging: Log database error
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method."]);
}


?>