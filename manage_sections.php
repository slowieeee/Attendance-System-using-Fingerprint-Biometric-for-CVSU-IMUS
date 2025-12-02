<?php
session_start();
require 'config.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id']; // Retrieve the logged-in teacher's ID
$action_success = $action_success ?? false; // Determine if the action was successful
$action_message = $action_message ?? ''; // The message to display
$popup_needed = $popup_needed ?? false; // Control whether to display the popup


// Delete a section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_section'])) {
    $section_code = $_POST['section_code'] ?? null;

    if (!$section_code) {
        $action_message = "Section code is missing.";
        error_log("Delete section failed: Section code is missing.");
        return; // Stop further execution if section_code is not provided
    }

    try {
        error_log("Initiating delete process for section_code: " . $section_code);

        // Begin transaction
        $pdo->beginTransaction();

        // Find all student IDs in the section
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE section_code = :section_code");
        $stmt->execute(['section_code' => $section_code]);
        $student_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Found students: " . implode(', ', $student_ids));

        // Delete all students with the same section code
        $stmt = $pdo->prepare("DELETE FROM students WHERE section_code = :section_code");
        $stmt->execute(['section_code' => $section_code]);
        error_log("Students deleted successfully for section_code: " . $section_code);

        // Delete the section itself
        $stmt = $pdo->prepare("
            DELETE FROM sections 
            WHERE section_code = :section_code AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            'section_code' => $section_code,
            'teacher_id' => $teacher_id,
        ]);
        error_log("Section deleted successfully for section_code: " . $section_code);

        // Commit transaction
        $pdo->commit();
        $action_success = true;
        $action_message = "Section and associated data removed successfully!";
        $popup_needed = true; // Set popup flag
    } catch (Exception $e) {
        // Rollback transaction on failure
        $pdo->rollBack();
        $action_message = "Failed to remove section and associated data: " . $e->getMessage();
        error_log("Error during deletion: " . $e->getMessage());
    }
}


// Check if a section was added successfully (via Excel upload)
if (isset($_GET['upload_status']) && $_GET['upload_status'] === 'success') {
    $action_success = true;
    $action_message = "Section added successfully!";
    $popup_needed = true; // Set popup flag for successful addition
}

// Fetch sections created by the logged-in teacher
$stmt = $pdo->prepare("SELECT * FROM sections WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
$sections = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections</title>
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
        .table th, .table td {
            vertical-align: middle;
        }
        .add-section-form {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            text-align: center;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .btn-success {
            background-color: rgb(25, 135, 84);
            border: none;
        }
        .btn-success:hover {
            background-color: #218c6e;
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
        // Display success popup
        function showSuccessPopup(message) {
            document.getElementById('popupMessage').textContent = message;
            document.getElementById('popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        // Close popup
        function closePopup() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
            window.location.href = "manage_sections.php"; // Redirect to clear POST/GET
        }

        // Trigger popup on page load if needed
        window.onload = function() {
            <?php if ($popup_needed): ?>
                showSuccessPopup("<?php echo htmlspecialchars($action_message); ?>");
            <?php endif; ?>
        };

    </script>
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
            <h2>Manage Sections</h2>
            <p>View, add, or delete sections associated with your account.</p>
        </div>

        <!-- Success Popup -->
        <div id="popup" class="popup">
            <p class="message" id="popupMessage"></p>
            <button class="btn btn-success" onclick="closePopup()">OK</button>
        </div>
        <div id="overlay" class="overlay"></div>

        <!-- Section List -->
        <div class="table-container">
            <h4>Sections</h4>
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Section Code</th>
                        <th>Subject</th>
                        <th>Section Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sections)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No sections added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sections as $section): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($section['section_code']); ?></td>
                                <td><?php echo htmlspecialchars($section['subject']); ?></td>
                                <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                                <td>
                                    <form method="POST" action="manage_sections.php" style="display:inline;">
                                        <input type="hidden" name="section_code" value="<?php echo $section['section_code']; ?>">
                                        <button type="submit" name="delete_section" class="btn btn-danger" onclick="confirmDelete(event)">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Upload Section -->
        <div class="add-section-form">
            <h4>Upload Sections via Excel</h4>
            <form method="POST" action="upload_section.php" enctype="multipart/form-data">
                <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
                <div class="mb-3">
                    <label for="file" class="form-label">Choose Excel file</label>
                    <input type="file" name="excel_file" id="file" class="form-control" accept=".xlsx, .xls" required>
                </div>
                <button type="submit" class="btn btn-success">Upload</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Management Attendance System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="logo">
    </footer>
                            
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(event) {
        if (!confirm("Are you sure you want to delete this section and all associated data?")) {
            event.preventDefault(); // Prevent form submission on cancel
        }
    }
    </script>
</body>
</html>
