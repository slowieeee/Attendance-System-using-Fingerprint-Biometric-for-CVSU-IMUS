<?php
session_start();
require 'config.php';

// Ensure the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'];
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .navbar .btn-logout {
            color: white;
            border: none;
            background-color: transparent;
            transition: all 0.3s;
        }

        .navbar .btn-logout:hover {
            color: #ddd;
        }

        .container-dashboard {
            flex-grow: 1;
            padding: 40px 20px;
            max-width: 1200px;
            margin: auto;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: rgb(25, 135, 84);
        }

        .welcome-section p {
            font-size: 1.2rem;
            color: #6c757d;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom:20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card h5 {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .card p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        .card a {
            color: white;
            background-color: rgb(25, 135, 84);
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .card a:hover {
            background-color: #1d7b6a;
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 40rem;
            max-height:30rem;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
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
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="btn btn-logout" onclick="location.href='logout.php'">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container-dashboard">
        <div class="logo-container">
            <img src="images/2.png" alt="Logo"> <!-- Replace with your actual logo -->
        </div>

        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</h1>
            <p>Manage teacher attendance, view reports, and handle administrative tasks effortlessly.</p>
        </div>

        <div class="grid">
            <div class="card">
                <h5>Teacher Attendance</h5>
                <p>View and manage teacher attendance logs with ease.</p>
                <a href="teacher_attendance">Go to Attendance</a>
            </div>
            <div class="card">
                <h5>Generate Reports</h5>
                <p>Generate detailed attendance reports and analytics.</p>
                <a href="generate_reports">Generate Reports</a>
            </div>           
        </div>
        <div class="card">
                <h5>Manage Profile</h5>
                <p>Manage Admin Profile.</p>
                <a href="admin_profile">Manage Profile</a>
            </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance Management System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="mx-auto">
        
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
