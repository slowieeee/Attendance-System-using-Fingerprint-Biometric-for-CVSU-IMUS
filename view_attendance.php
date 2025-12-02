<?php
session_start();
require 'config.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id']; // Logged-in teacher's ID

// Fetch dropdown values for Section and Section Code
$stmt = $pdo->prepare("
    SELECT DISTINCT section_code, section_name 
    FROM sections 
    WHERE teacher_id = :teacher_id
");
$stmt->execute(['teacher_id' => $teacher_id]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameters
$student_id = $_GET['student_id'] ?? '';
$student_name = $_GET['student_name'] ?? '';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$selected_section = $_GET['section'] ?? '';
$selected_subject = $_GET['subject'] ?? '';

// Pagination setup
$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

// Build the main query
$query = "
    SELECT 
        attendance.attendance_id AS id, 
        students.student_id, 
        students.name AS student_name, 
        attendance.class_date, 
        attendance.time_in, 
        attendance.time_out, 
        attendance.status, 
        attendance.subject, 
        attendance.section_code, 
        sections.section_name 
    FROM attendance
    JOIN students ON attendance.student_id = students.student_id
    JOIN sections ON attendance.section_code = sections.section_code
    WHERE sections.teacher_id = :teacher_id
";

// Add conditions for filters
$parameters = ['teacher_id' => $teacher_id];

if (!empty($student_id)) {
    $query .= " AND students.student_id LIKE :student_id";
    $parameters['student_id'] = "%$student_id%";
}
if (!empty($student_name)) {
    $query .= " AND students.name LIKE :student_name";
    $parameters['student_name'] = "%$student_name%";
}
if (!empty($status)) {
    $query .= " AND attendance.status = :status";
    $parameters['status'] = $status;
}
if (!empty($start_date)) {
    $query .= " AND attendance.class_date >= :start_date";
    $parameters['start_date'] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND attendance.class_date <= :end_date";
    $parameters['end_date'] = $end_date;
}
if (!empty($selected_section)) {
    $query .= " AND sections.section_name = :selected_section";
    $parameters['selected_section'] = $selected_section;
}
if (!empty($selected_subject)) {
    $query .= " AND attendance.subject LIKE :selected_subject";
    $parameters['selected_subject'] = "%$selected_subject%";
}

// Add sorting and pagination
$query .= " ORDER BY attendance.class_date DESC LIMIT $records_per_page OFFSET $offset";

// Execute the main query
$stmt = $pdo->prepare($query);
$stmt->execute($parameters);
$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total records for pagination
$count_query = "
    SELECT COUNT(DISTINCT attendance.attendance_id) 
    FROM attendance
    JOIN students ON attendance.student_id = students.student_id
    JOIN sections ON attendance.section_code = sections.section_code
    WHERE sections.teacher_id = :teacher_id
";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute(['teacher_id' => $teacher_id]);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .filter-container {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
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
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"></a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-outline-light" onclick="window.location.href='teacher_dashboard'">Back to Dashboard</button>
        </div>
    </nav>

    <div class="content-wrapper container">
        <!-- Filters Section -->
        <div class="filter-container">
            <form method="GET" action="view_attendance.php" class="row g-3">
                <div class="col-md-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" placeholder="Enter student ID" value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="student_name" class="form-label">Student Name</label>
                    <input type="text" name="student_name" id="student_name" class="form-control" placeholder="Enter student name" value="<?php echo htmlspecialchars($student_name ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="section" class="form-label">Section</label>
                    <select name="section" id="section" class="form-select">
                        <option value="">All Sections</option>
                        <?php foreach ($sections ?? [] as $section): ?>
                            <option value="<?php echo htmlspecialchars($section['section_name']); ?>" <?php echo ($selected_section ?? '') === $section['section_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section['section_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        <option value="Present" <?php echo ($status ?? '') === 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Absent" <?php echo ($status ?? '') === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-success">Apply Filters</button>
                    <a href="view_attendance.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="table-container">
            <h4>Attendance Records</h4>
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Class Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendanceRecords)): ?>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                <td><?php echo htmlspecialchars($record['class_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['time_in']); ?></td>
                                <td><?php echo htmlspecialchars($record['time_out']); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="exportForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Export Attendance Records</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="exportSection" class="form-label">Section</label>
                            <select name="section" id="exportSection" class="form-select">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['section_code']); ?>">
                                        <?php echo htmlspecialchars($section['section_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="exportStartDate" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="exportStartDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="exportEndDate" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="exportEndDate" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Export</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?page=<?php echo $p; ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>

        <!-- Export to Excel -->
        <div class="text-end mt-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">Export to Excel</button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Management Attendance System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="logo">
    </footer>
    <script>
    $(document).ready(function () {
        $('#exportForm').on('submit', function (e) {
            e.preventDefault(); // Prevent default form submission
            const formData = $(this).serialize(); // Serialize form data

            $.ajax({
                url: 'export_to_excel.php',
                type: 'POST',
                data: formData,
                xhrFields: {
                    responseType: 'blob', // Expect a file blob from the server
                },
                success: function (response, status, xhr) {
                    // Extract filename from Content-Disposition header
                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'attendance_records.xlsx'; // Default filename
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = disposition.match(/filename="(.+?)"/);
                        if (matches && matches[1]) {
                            filename = matches[1];
                        }
                    }

                    // Create a URL for the blob and trigger download
                    const url = window.URL.createObjectURL(response);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename; // Use dynamic filename
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    $('#exportModal').modal('hide'); // Hide the modal
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    alert('Error exporting data. Please try again.');
                },
            });
        });
    });
</script>


</body>
</html>
