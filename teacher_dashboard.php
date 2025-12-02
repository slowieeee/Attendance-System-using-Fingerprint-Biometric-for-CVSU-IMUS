<?php
session_start();
require 'config.php';

// Ensure the teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id']; // Get logged-in teacher's ID

// Fetch teacher's name from the database
$stmt = $pdo->prepare("SELECT name FROM teachers WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
$teacher = $stmt->fetch();

$teacher_name = $teacher ? $teacher['name'] : 'Teacher'; // Default to "Teacher" if name is not found
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }
        .content-wrapper {
            flex: 1;
        }

        /* Navigation Bar */
        .navbar {
            background-color: rgb(25, 135, 84);
        }
        .navbar .navbar-brand {
            color: white;
        }
        .navbar .btn-logout {
            background-color: #ffffff;
            color: rgb(25, 135, 84);
            border: none;
            transition: background-color 0.3s, color 0.3s;
        }
        .navbar .btn-logout:hover {
            background-color: rgb(25, 135, 84);
            color: white;
        }

        /* Welcome Section */
        .dashboard-welcome {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .dashboard-welcome h2 {
            font-size: 2.2rem;
            color: rgb(25, 135, 84);
            margin-bottom: 10px;
        }
        .dashboard-welcome p {
            font-size: 1.2rem;
            color: #6c757d;
        }

        /* Dashboard Actions */
        .grid {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .grid .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .grid .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .grid .card-body {
            text-align: center;
        }
        .grid .card-body h5 {
            font-size: 1.5rem;
            color: rgb(25, 135, 84);
            margin-bottom: 10px;
        }
        .grid .card-body p {
            color: #6c757d;
            margin-bottom: 15px;
        }
        .grid2 {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .grid2 .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .grid2 .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .grid2 .card-body {
            text-align: center;
        }
        .grid2 .card-body h5 {
            font-size: 1.5rem;
            color: rgb(25, 135, 84);
            margin-bottom: 10px;
        }
        .grid2 .card-body p {
            color: #6c757d;
            margin-bottom: 15px;
        }

        /* Footer */
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
        footer img {
            max-height: 50px;
            transform: scale(1.0);
            object-fit: contain;
        }
        .navbar img {
            max-height: 50px;
            transform: scale(3.5);
            object-fit: contain;
            margin-left: 1.55%;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"></a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-logout" onclick="window.location.href='logout'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </nav>

    <!-- Content Wrapper -->
    <div class="content-wrapper container">
        <!-- Welcome Section -->
        <div class="dashboard-welcome">
            <h2>Welcome, <?php echo htmlspecialchars($teacher_name); ?>!</h2>
            <p>Manage your sections, students, schedules, attendance, and analytics effortlessly.</p>
        </div>

        <!-- Dashboard Actions -->
        <div class="grid mt-4">
            <div class="card" onclick="location.href='manage_sections';">
                <div class="card-body">
                    <h5>Manage Sections</h5>
                    <p>View and update section details.</p>
                    <i class="fas fa-list-alt fa-2x text-success"></i>
                </div>
            </div>
            <div class="card" onclick="location.href='manage_students';">
                <div class="card-body">
                    <h5>Manage Students</h5>
                    <p>View student details.</p>
                    <i class="fas fa-users fa-2x text-primary"></i>
                </div>
            </div>
            <div class="card" onclick="location.href='manage_schedule';">
                <div class="card-body">
                    <h5>Manage Schedule</h5>
                    <p>View and update class schedules.</p>
                    <i class="fas fa-calendar-alt fa-2x text-info"></i>
                </div>
            </div>
            <div class="card" onclick="location.href='view_attendance';">
                <div class="card-body">
                    <h5>View Attendance</h5>
                    <p>Check attendance logs for your classes.</p>
                    <i class="fas fa-clipboard-list fa-2x text-warning"></i>
                </div>
            </div>
        </div>
        <div class="grid2 mt-4">
            <div class="card" onclick="location.href='analytics_reports';">
                <div class="card-body">
                    <h5>Analytics & Reports</h5>
                    <p>View detailed attendance analytics and export reports.</p>
                    <i class="fas fa-chart-line fa-2x text-danger"></i>
                </div>
            </div>
            <!-- Added Manage Profile Option -->
            <div class="card" onclick="location.href='teacher_profile';">
                <div class="card-body">
                    <h5>Manage Profile</h5>
                    <p>Update your profile and account settings.</p>
                    <i class="fas fa-user-cog fa-2x text-secondary"></i>
                </div>
            </div>
    </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Management Attendance System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="logo">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
