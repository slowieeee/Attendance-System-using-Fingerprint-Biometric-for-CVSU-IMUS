<?php
session_start();
require 'config.php'; // Use the external config.php for database connection

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Handle POST request to log teacher attendance (for ESP32)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json'); // Ensure JSON response

    $fingerprint_id = $_POST['fingerprint_id'] ?? null;
    $section_code = $_POST['section_code'] ?? null;

    // Validate inputs
    if (!$fingerprint_id || !$section_code) {
        http_response_code(400);
        echo json_encode(["error" => "Missing fingerprint_id or section_code."]);
        exit;
    }

    try {
        // Step 1: Find the teacher_id from the teachers table using the fingerprint_id
        $stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE fingerprint_id = :fingerprint_id");
        $stmt->execute(['fingerprint_id' => $fingerprint_id]);
        $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacherData) {
            http_response_code(404);
            echo json_encode(["error" => "No teacher found for the given fingerprint_id."]);
            exit;
        }

        $teacher_id = $teacherData['teacher_id'];

        // Step 2: Fetch subject, room, and start_time from schedules table
        $current_day = date("l"); // e.g., "Monday"
        $current_time = date("H:i:s"); // e.g., "14:30:00"

        $scheduleStmt = $pdo->prepare("
            SELECT subject, room, start_time 
            FROM schedules 
            WHERE teacher_id = :teacher_id AND section_code = :section_code 
              AND day = :current_day
              AND :current_time BETWEEN start_time AND end_time
        ");
        $scheduleStmt->execute([
            'teacher_id' => $teacher_id,
            'section_code' => $section_code,
            'current_day' => $current_day,
            'current_time' => $current_time,
        ]);
        $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            http_response_code(404);
            echo json_encode(["error" => "No schedule found for the given teacher_id and section_code."]);
            exit;
        }

        $subject = $schedule['subject'];
        $room = $schedule['room'];
        $start_time = $schedule['start_time'];
        $current_time = date("Y-m-d H:i:s");
        $class_date = date("Y-m-d");

        // Step 3: Check for an existing incomplete attendance record
        $checkStmt = $pdo->prepare("
            SELECT * 
            FROM teacher_attendance 
            WHERE teacher_id = :teacher_id 
              AND section_code = :section_code 
              AND class_date = :class_date
              AND time_out IS NULL
        ");
        $checkStmt->execute([
            'teacher_id' => $teacher_id,
            'section_code' => $section_code,
            'class_date' => $class_date,
        ]);
        $existingAttendance = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAttendance) {
            // Step 4: Update time_out
            $updateStmt = $pdo->prepare("
                UPDATE teacher_attendance 
                SET time_out = :time_out 
                WHERE teacher_id = :teacher_id 
                  AND section_code = :section_code 
                  AND class_date = :class_date
                  AND time_out IS NULL
            ");
            if ($updateStmt->execute([
                'time_out' => $current_time,
                'teacher_id' => $teacher_id,
                'section_code' => $section_code,
                'class_date' => $class_date,
            ])) {
                http_response_code(200);
                echo json_encode(["message" => "Time-out logged successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to log time-out."]);
            }
        } else {
            // Step 5: Insert new attendance record with lateness calculation
            $scheduled_time = strtotime(date("Y-m-d") . " " . $start_time);
            $logged_time = strtotime($current_time);
            $lateness = ($logged_time - $scheduled_time) / 60; // Calculate lateness in minutes

            // Determine the status
            $status = ($lateness > 15) ? 'Late' : 'Present';

            // Insert new attendance record
            $insertStmt = $pdo->prepare("
                INSERT INTO teacher_attendance 
                (teacher_id, section_code, subject, room, time_in, class_date, status) 
                VALUES (:teacher_id, :section_code, :subject, :room, :time_in, :class_date, :status)
            ");
            if ($insertStmt->execute([
                'teacher_id' => $teacher_id,
                'section_code' => $section_code,
                'subject' => $subject,
                'room' => $room,
                'time_in' => $current_time,
                'class_date' => $class_date,
                'status' => $status,
            ])) {
                http_response_code(200);
                echo json_encode([
                    "message" => "Time-in logged successfully.",
                    "status" => $status,
                    "lateness" => $lateness
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to log time-in."]);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
    exit;
}


// Fetch attendance logs for display
$sql = "SELECT 
            ta.attendance_id, 
            t.name AS teacher_name, 
            ta.time_in, 
            ta.time_out, 
            ta.class_date, 
            ta.status, 
            ta.section_code, 
            ta.subject
        FROM teacher_attendance ta
        JOIN teachers t ON ta.teacher_id = t.teacher_id
        ORDER BY ta.class_date DESC, ta.time_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$attendanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Attendance Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar {
            background-color: rgb(25, 135, 84);
            padding: 10px 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .navbar .navbar-brand {
            color: white;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .navbar .btn-back {
            color: white;
            border: none;
            background-color: transparent;
            transition: color 0.3s ease;
            margin-left:13rem;
        }
        .navbar .btn-back:hover {
            color: #ddd;
        }
        .container-dashboard {
            flex-grow: 1;
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
        }
        .heading-section {
            text-align: center;
            margin-bottom: 40px;
        }
        .heading-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: rgb(25, 135, 84);
        }
        .heading-section p {
            font-size: 1.2rem;
            color: #6c757d;
        }
        .filter-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }
        .navbar img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(3.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }
        footer img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(1.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="#">Teacher Attendance Logs</a>
            <img src="images/3.png" alt="Header Image" class="mx-auto">
            <button class="btn-back" onclick="location.href='admin_dashboard'">Back to Dashboard
            </button>
        </div>
    </nav>

    <div class="container-dashboard">
        <!-- Page Heading -->
        <div class="heading-section">
            <h1>Teacher Attendance Logs</h1>
            <p>Filter and manage teacher attendance efficiently.</p>
        </div>

        <!-- Filters Section -->
        <div class="filter-container">
            <form method="GET" action="teacher_attendance.php" class="row g-3">
                <div class="col-md-3">
                    <label for="filterTeacher" class="form-label">Teacher Name</label>
                    <input type="text" name="teacher_name" id="filterTeacher" class="form-control" placeholder="Enter teacher name">
                </div>
                <div class="col-md-3">
                    <label for="filterDate" class="form-label">Date</label>
                    <input type="date" name="class_date" id="filterDate" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="filterSection" class="form-label">Section Code</label>
                    <input type="text" name="section_code" id="filterSection" class="form-control" placeholder="Enter section code">
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">All</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="teacher_attendance.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Attendance ID</th>
                        <th>Teacher Name</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Class Date</th>
                        <th>Status</th>
                        <th>Section Code</th>
                        <th>Subject</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($attendanceLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['attendance_id']); ?></td>
                            <td><?= htmlspecialchars($log['teacher_name']); ?></td>
                            <td><?= htmlspecialchars($log['time_in']); ?></td>
                            <td><?= htmlspecialchars($log['time_out'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($log['class_date']); ?></td>
                            <td><?= htmlspecialchars($log['status']); ?></td>
                            <td><?= htmlspecialchars($log['section_code']); ?></td>
                            <td><?= htmlspecialchars($log['subject']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($attendanceLogs)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No attendance records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance Management System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="mx-auto">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
