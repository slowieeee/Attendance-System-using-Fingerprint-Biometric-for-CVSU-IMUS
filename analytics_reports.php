<?php
session_start();
require 'config.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch filters: Section Codes for the logged-in teacher
$stmt = $pdo->prepare("
    SELECT DISTINCT section_code, section_name
    FROM sections
    WHERE teacher_id = :teacher_id
");
$stmt->execute(['teacher_id' => $teacher_id]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected section filter
$selected_section = $_GET['section'] ?? '';

// Timeframe selector (default to 'daily')
$timeframe = $_GET['timeframe'] ?? 'daily';
$today = date('Y-m-d');
$this_week_start = date('Y-m-d', strtotime('monday this week'));
$this_week_end = date('Y-m-d', strtotime('sunday this week'));
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

// Set start and end dates for selected timeframe
switch ($timeframe) {
    case 'weekly':
        $start_date = $this_week_start;
        $end_date = $this_week_end;
        break;
    case 'monthly':
        $start_date = $this_month_start;
        $end_date = $this_month_end;
        break;
    default: // daily
        $start_date = $today;
        $end_date = $today;
}

// Initialize query filters
$filter_condition = "AND teacher_id = :teacher_id AND class_date BETWEEN :start_date AND :end_date";
$filter_params = [
    ':teacher_id' => $teacher_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date,
];

// Add section filter if a section is selected
if (!empty($selected_section)) {
    $filter_condition .= " AND section_code = :section_code";
    $filter_params[':section_code'] = $selected_section;
}

// Count attendance for analytics
$analytics_query = "
    SELECT 
        COUNT(CASE WHEN status = 'Present' THEN 1 END) AS total_present,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) AS total_absent
    FROM attendance
    WHERE 1=1 $filter_condition
";

$timeframe_analytics = $pdo->prepare($analytics_query);
$timeframe_analytics->execute($filter_params);
$timeframe_stats = $timeframe_analytics->fetch(PDO::FETCH_ASSOC);

// Fetch most absent students
$most_absent_query = "
    SELECT student_id, student_name, COUNT(*) AS absent_count
    FROM attendance
    WHERE status = 'Absent' $filter_condition
    GROUP BY student_id, student_name
    ORDER BY absent_count DESC
    LIMIT 5
";

$most_absent_stmt = $pdo->prepare($most_absent_query);
$most_absent_stmt->execute($filter_params);
$most_absent_students = $most_absent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance data for charts
$chart_query = "
    SELECT 
        class_date,
        COUNT(CASE WHEN status = 'Present' THEN 1 END) AS present_count,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) AS absent_count
    FROM attendance
    WHERE 1=1 $filter_condition
    GROUP BY class_date
    ORDER BY class_date ASC
";

$chart_stmt = $pdo->prepare($chart_query);
$chart_stmt->execute($filter_params);
$attendance_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = array_column($attendance_data, 'class_date');
$present_counts = array_column($attendance_data, 'present_count');
$absent_counts = array_column($attendance_data, 'absent_count');
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background-color: rgb(25, 135, 84);
            color: white;
        }

        .navbar .navbar-brand {
            color: white;
            font-weight: bold;
        }
        
        .container {
            margin-top: 20px;
            flex-grow: 1;
        }

        .chart-container, .analytics-section, .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-buttons {
            margin-bottom: 20px;
            text-align: center;
            margin-top: 20px;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
        }
        footer img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(1.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }
        .navbar img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(3.5); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
            margin-left:-2.55%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Analytics & Reports</a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-outline-light" onclick="window.location.href='teacher_dashboard'">Back to Dashboard
            </button>
        </div>
    </nav>

    <div class="container">
        <!-- Section Filter -->
        <div class="filter-section">
            <label for="section" class="form-label">Filter by Section:</label>
            <select name="section" id="section" class="form-select">
                <option value="">All Sections</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?php echo htmlspecialchars($section['section_code']); ?>" <?php echo $selected_section == $section['section_code'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($section['section_name']); ?> (<?php echo htmlspecialchars($section['section_code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Timeframe Selector -->
        <div class="filter-buttons">
            <a href="analytics_reports.php?timeframe=daily&section=<?php echo $selected_section; ?>" class="btn btn-outline-success <?php echo $timeframe == 'daily' ? 'active' : ''; ?>">Daily</a>
            <a href="analytics_reports.php?timeframe=weekly&section=<?php echo $selected_section; ?>" class="btn btn-outline-success <?php echo $timeframe == 'weekly' ? 'active' : ''; ?>">Weekly</a>
            <a href="analytics_reports.php?timeframe=monthly&section=<?php echo $selected_section; ?>" class="btn btn-outline-success <?php echo $timeframe == 'monthly' ? 'active' : ''; ?>">Monthly</a>
        </div>

        <!-- Analytics Section -->
        <div class="row text-center">
            <div class="col-md-4">
                <div class="analytics-section">
                    <h5>Attendance Overview</h5>
                    <p>Present: <strong><?php echo $timeframe_stats['total_present']; ?></strong></p>
                    <p>Absent: <strong><?php echo $timeframe_stats['total_absent']; ?></strong></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytics-section">
                    <h5>Most Absent Students</h5>
                    <ul class="list-group">
                        <?php foreach ($most_absent_students as $student): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($student['student_name']); ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $student['absent_count']; ?> Absents</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5>Attendance Distribution</h5>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Attendance Chart -->
        <div class="chart-container">
            <h5>Attendance Trends</h5>
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="logo">
    </footer>

    <script>
        // Auto-filter on section change
        document.getElementById('section').addEventListener('change', function () {
            const selectedSection = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('section', selectedSection);
            window.location.href = url.toString();
        });

        // Line Chart for Attendance Trends
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Present',
                        data: <?php echo json_encode($present_counts); ?>,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        fill: true
                    },
                    {
                        label: 'Absent',
                        data: <?php echo json_encode($absent_counts); ?>,
                        borderColor: '#FF5733',
                        backgroundColor: 'rgba(255, 87, 51, 0.2)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Pie Chart for Attendance Distribution
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?php echo $timeframe_stats['total_present']; ?>, <?php echo $timeframe_stats['total_absent']; ?>],
                    backgroundColor: ['#4CAF50', '#FF5733']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
