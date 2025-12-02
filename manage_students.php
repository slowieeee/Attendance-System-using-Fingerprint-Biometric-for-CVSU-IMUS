<?php
session_start();
require 'config.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id']; // Retrieve logged-in teacher's ID

// Pagination variables
$students_per_page = 50;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1); // Ensure page is at least 1
$offset = ($current_page - 1) * $students_per_page;

// Fetch sections for the logged-in teacher
$stmt = $pdo->prepare("SELECT section_code, section_name FROM sections WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
$sections = $stmt->fetchAll();

// Initialize filters
$selected_section_code = $_GET['section_code'] ?? '';
$student_name_filter = $_GET['student_name'] ?? '';
$student_id_filter = $_GET['student_id'] ?? '';

// Build the base query for fetching students
$sql = "
    SELECT s.student_id, s.name, sec.section_name 
    FROM students s
    JOIN sections sec ON s.section_code = sec.section_code
    WHERE sec.teacher_id = :teacher_id
";

// Add filters dynamically
$params = ['teacher_id' => $teacher_id];

if (!empty($selected_section_code)) {
    $sql .= " AND s.section_code = :section_code";
    $params['section_code'] = $selected_section_code;
}

if (!empty($student_name_filter)) {
    $sql .= " AND s.name LIKE :student_name";
    $params['student_name'] = '%' . $student_name_filter . '%';
}

if (!empty($student_id_filter)) {
    $sql .= " AND s.student_id LIKE :student_id";
    $params['student_id'] = '%' . $student_id_filter . '%';
}

// Get the total number of students for pagination
$count_sql = "SELECT COUNT(*) AS total FROM ($sql) AS filtered_students";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_students = $count_stmt->fetchColumn();

// Add LIMIT and OFFSET for pagination directly into the query
$sql .= " LIMIT $students_per_page OFFSET $offset";

// Fetch students with the applied filters and pagination
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Calculate total pages
$total_pages = ceil($total_students / $students_per_page);
?>



<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content-wrapper {
            flex: 1;
            padding: 20px;
        }
        .navbar {
            background-color: rgb(25, 135, 84);
        }
        .navbar .navbar-brand {
            color: white;
        }
        .navbar .btn-back {
            background-color: transparent;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .dashboard-title {
            margin-bottom: 30px;
            text-align: center;
        }
        .dashboard-title h2 {
            color: rgb(25, 135, 84);
        }
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .pagination a {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #007bff;
            text-decoration: none;
        }
        .pagination a.active {
            background-color: rgb(25, 135, 84);
            color: white;
            border-color: rgb(25, 135, 84);
        }
        .pagination a:hover {
            background-color: #218c6e;
            color: white;
        }
        .filter-container {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .filter-container .form-label {
            font-weight: bold;
            color: rgb(25, 135, 84);
        }
        .btn-primary {
            background-color: rgb(25, 135, 84);
            border: none;
        }
        .btn-primary:hover {
            background-color: #218c6e;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
        .navbar img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(3.5); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
            margin-left:5%;
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
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"></a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-outline-light" onclick="window.location.href='teacher_dashboard'">Back to Dashboard
            </button>
        </div>
    </nav>

    <div class="content-wrapper container">
        <div class="dashboard-title">
            <h2>Manage Students</h2>
            <p>Filter and view students associated with your sections.</p>
        </div>

        <!-- Filters Section -->
        <div class="filter-container">
            <form method="GET" action="manage_students.php" class="row g-3">
                <div class="col-md-4">
                    <label for="section_code" class="form-label">Choose Section</label>
                    <select name="section_code" id="section_code" class="form-select">
                        <option value="">All Sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['section_code']; ?>" <?php echo ($selected_section_code == $section['section_code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="student_name" class="form-label">Student Name</label>
                    <input type="text" name="student_name" id="student_name" class="form-control" placeholder="Enter student name" value="<?php echo htmlspecialchars($_GET['student_name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" placeholder="Enter student ID" value="<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>">
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="manage_students.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <h4>Students</h4>
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['section_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>&section_code=<?php echo htmlspecialchars($selected_section_code); ?>&student_name=<?php echo htmlspecialchars($_GET['student_name'] ?? ''); ?>&student_id=<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>">Previous</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <a href="?page=<?php echo $page; ?>&section_code=<?php echo htmlspecialchars($selected_section_code); ?>&student_name=<?php echo htmlspecialchars($_GET['student_name'] ?? ''); ?>&student_id=<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>" <?php if ($page == $current_page) echo 'class="active"'; ?>>
                    <?php echo $page; ?>
                </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>&section_code=<?php echo htmlspecialchars($selected_section_code); ?>&student_name=<?php echo htmlspecialchars($_GET['student_name'] ?? ''); ?>&student_id=<?php echo htmlspecialchars($_GET['student_id'] ?? ''); ?>">Next</a>
            <?php endif; ?>
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

