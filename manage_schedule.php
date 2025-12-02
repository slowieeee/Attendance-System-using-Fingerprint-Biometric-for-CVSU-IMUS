<?php
session_start();
require 'config.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id']; // Retrieve logged-in teacher's ID
$action_success = false;
$action_message = '';

// Fetch sections for the logged-in teacher
$stmt = $pdo->prepare("SELECT section_code, section_name FROM sections WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
$sections = $stmt->fetchAll();

// Add a schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $section_code = $_POST['section_code'];
    $subject = $_POST['subject'];
    $room = $_POST['room'];
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Conflict Check Query
    $stmt = $pdo->prepare("
        SELECT * 
        FROM schedules 
        WHERE 
            room = :room 
            AND day = :day 
            AND (
                (:start_time >= start_time AND :start_time < end_time) OR
                (:end_time > start_time AND :end_time <= end_time) OR
                (:start_time <= start_time AND :end_time >= end_time)
            )
    ");
    $stmt->execute([
        'room' => $room,
        'day' => $day,
        'start_time' => $start_time,
        'end_time' => $end_time,
    ]);

    if ($stmt->rowCount() > 0) {
        // Conflict Found
        $error_message = "Conflict detected! Another schedule is already using this room at the selected time.";
    } else {
        // No Conflict, Proceed to Add the Schedule
        $stmt = $pdo->prepare("
            INSERT INTO schedules (teacher_id, section_code, subject, room, day, start_time, end_time)
            VALUES (:teacher_id, :section_code, :subject, :room, :day, :start_time, :end_time)
        ");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'section_code' => $section_code,
            'subject' => $subject,
            'room' => $room,
            'day' => $day,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ]);
        $success_message = "Schedule added successfully!";
    }
}

// Delete a schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];

    $stmt = $pdo->prepare("DELETE FROM schedules WHERE schedule_id = :schedule_id AND teacher_id = :teacher_id");
    if ($stmt->execute(['schedule_id' => $schedule_id, 'teacher_id' => $teacher_id])) {
        $action_success = true;
        $action_message = "Schedule deleted successfully!";
    } else {
        $action_message = "Failed to delete schedule. Please try again.";
    }
}

// Fetch schedules for the logged-in teacher
$stmt = $pdo->prepare("
    SELECT DISTINCT s.schedule_id, sec.section_name, s.subject, s.room, s.day, s.start_time, s.end_time 
    FROM schedules s
    JOIN sections sec ON s.section_code = sec.section_code
    WHERE s.teacher_id = :teacher_id AND sec.teacher_id = :teacher_id
    ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time
");
$stmt->execute(['teacher_id' => $teacher_id]);
$schedules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="images/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            flex: 1;
            margin-top: 30px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .table-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-danger {
            background-color: #e63946;
            border: none;
        }
        .btn-danger:hover {
            background-color: #d62839;
        }
        .btn-primary {
            background-color: #198754;
            border: none;
        }
        .btn-primary:hover {
            background-color: #157347;
        }
        .navbar {
            background-color: #198754;
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: bold;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
        }
        .navbar img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(3.5); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
            margin-left:4.3%;
        }
        footer img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(1.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }
    </style>
    <script>
        // Confirm schedule deletion
        function confirmDelete(scheduleId) {
            if (confirm("Are you sure you want to delete this schedule?")) {
                document.getElementById('delete_schedule_id').value = scheduleId;
                document.getElementById('delete_schedule_form').submit();
            }
        }
    </script>
</head>
<body>
    <!-- Header Section -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"></a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-outline-light" onclick="window.location.href='teacher_dashboard'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <h1 class="text-center text-success mb-4">Manage Schedule</h1>

        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success text-center">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Schedule Section -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title"></h3>
                <form method="POST" action="manage_schedule.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="section_code" class="form-label">Section</label>
                            <select name="section_code" id="section_code" class="form-select" required>
                                <option value="" disabled selected>Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['section_code']); ?>">
                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" placeholder="Enter Subject" required>
                        </div>
                        <div class="col-md-4">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" name="room" id="room" class="form-control" placeholder="Enter Room" required>
                        </div>
                        <div class="col-md-4">
                            <label for="day" class="form-label">Day</label>
                            <select name="day" id="day" class="form-select" required>
                                <option value="" disabled selected>Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-12 text-end">
                            <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Schedule List -->
        <div class="table-container">
            <h3 class="mb-4">Schedules</h3>
            <table class="table table-bordered table-striped">
                <thead class="table-success">
                    <tr>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Room</th>
                        <th>Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No schedules found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['end_time']); ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $schedule['schedule_id']; ?>)">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Hidden Form for Deleting a Schedule -->
        <form method="POST" id="delete_schedule_form" action="manage_schedule.php">
            <input type="hidden" name="schedule_id" id="delete_schedule_id">
            <input type="hidden" name="delete_schedule" value="1">
        </form>
    </div>

    <footer class="bg-dark text-white text-center py-3">
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Management Attendance System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="logo">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


