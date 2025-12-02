<?php
session_start();
require 'config.php'; // Ensure this file contains the PDO setup

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

// Fetch analytics
$today = date('Y-m-d');
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_week_end = date('Y-m-d', strtotime('sunday this week'));
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

try {
    // Total present teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'Present' AND class_date = :today");
    $stmt->execute(['today' => $today]);
    $present_today = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'Present' AND class_date BETWEEN :week_start AND :week_end");
    $stmt->execute(['week_start' => $this_week_start, 'week_end' => $this_week_end]);
    $present_week = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'Present' AND class_date BETWEEN :month_start AND :month_end");
    $stmt->execute(['month_start' => $this_month_start, 'month_end' => $this_month_end]);
    $present_month = $stmt->fetchColumn();

    // Total late teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'late' AND class_date = :today");
    $stmt->execute(['today' => $today]);
    $late_today = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'late' AND class_date BETWEEN :week_start AND :week_end");
    $stmt->execute(['week_start' => $this_week_start, 'week_end' => $this_week_end]);
    $late_week = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM teacher_attendance WHERE status = 'late' AND class_date BETWEEN :month_start AND :month_end");
    $stmt->execute(['month_start' => $this_month_start, 'month_end' => $this_month_end]);
    $late_month = $stmt->fetchColumn();

    // Fetch teachers for Excel export
    $stmt = $pdo->prepare("SELECT DISTINCT teacher_id, (SELECT name FROM teachers WHERE teacher_id = ta.teacher_id) AS teacher_name FROM teacher_attendance ta");
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching analytics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-left: 9rem;
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
        .analytics-container {
            margin-bottom: 30px;
        }
        .chart-section {
            margin-top: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .chart-container {
            flex: 1;
            min-width: 300px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .export-container {
            flex: 1;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="#">Attendance Reports</a>
            <button class="btn-back" onclick="location.href='admin_dashboard'">Back to Dashboard</button>
        </div>
    </nav>

    <div class="container-dashboard">
        <div class="heading-section">
            <h1>Attendance Reports Analytics</h1>
            <p>View detailed attendance reports and analytics.</p>
        </div>

        <div class="row text-center analytics-container">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title text-success">Present Today</h5>
                        <p class="display-6 fw-bold"><?php echo $present_today; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Present This Week</h5>
                        <p class="display-6 fw-bold"><?php echo $present_week; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title text-info">Present This Month</h5>
                        <p class="display-6 fw-bold"><?php echo $present_month; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Late Today</h5>
                        <p class="display-6 fw-bold"><?php echo $late_today; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-container">
                <h5 class="mb-3">Attendance Analytics</h5>
                <canvas id="barChart"></canvas>
            </div>
            <div class="export-container">
                <h5>Export Attendance Records</h5>
                <ul class="list-group">
                    <?php foreach ($teachers as $teacher): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                            <a href="export_teacher.php?teacher_id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-sm btn-primary">Export Excel</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance Management System. All rights reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['Today', 'This Week', 'This Month'],
                datasets: [
                    {
                        label: 'Present',
                        data: [<?php echo $present_today; ?>, <?php echo $present_week; ?>, <?php echo $present_month; ?>],
                        backgroundColor: '#4CAF50'
                    },
                    {
                        label: 'Late',
                        data: [<?php echo $late_today; ?>, <?php echo $late_week; ?>, <?php echo $late_month; ?>],
                        backgroundColor: '#FF5733'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
