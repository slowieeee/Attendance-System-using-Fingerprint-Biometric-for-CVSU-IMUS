<?php
session_start();
require 'config.php';

if (!isset($_SESSION['superadmin_id'])) {
    echo "<p class='error-message'>Unauthorized access. Please log in.</p>";
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$success_message = '';
$error_message = '';

// Function to generate a random password
function generatePassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($characters), 0, $length);
}

// Handle POST requests for adding or deleting teachers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        $username = $_POST['username'];
        $enrollment_code = $_POST['enrollment_code'];
        $email = $_POST['email'];
        $name = $_POST['name'];
        $password = generatePassword();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert teacher into the database
            $stmt = $pdo->prepare("
                INSERT INTO teachers (username, password, enrollment_code, email, name)
                VALUES (:username, :password, :enrollment_code, :email, :name)
            ");
            $stmt->execute([
                'username' => $username,
                'password' => $hashed_password,
                'enrollment_code' => $enrollment_code,
                'email' => $email,
                'name' => $name,
            ]);

            // Fetch updated teacher list
            $stmt = $pdo->query("SELECT * FROM teachers");
            $teachers = $stmt->fetchAll();

            // Return updated table rows only
            foreach ($teachers as $teacher) {
                echo "
                    <tr>
                        <td>" . htmlspecialchars($teacher['teacher_id']) . "</td>
                        <td>" . htmlspecialchars($teacher['name']) . "</td>
                        <td>" . htmlspecialchars($teacher['email']) . "</td>
                        <td>" . htmlspecialchars($teacher['username']) . "</td>
                        <td>" . htmlspecialchars($teacher['enrollment_code']) . "</td>
                        <td>
                            <button class='btn btn-danger btn-sm delete-teacher' data-teacher-id='" . htmlspecialchars($teacher['teacher_id']) . "'>Delete</button>
                        </td>
                    </tr>
                ";
            }
            exit;
        } catch (Exception $e) {
            echo "Error adding teacher: " . $e->getMessage();
            exit;
        }
    } elseif (isset($_POST['delete_teacher'])) {
        $teacher_id = $_POST['teacher_id'];
        try {
            // Delete teacher from the database
            $stmt = $pdo->prepare("DELETE FROM teachers WHERE teacher_id = :teacher_id");
            $stmt->execute(['teacher_id' => $teacher_id]);

            // Fetch updated teacher list after deletion
            $stmt = $pdo->query("SELECT * FROM teachers");
            $teachers = $stmt->fetchAll();

            // Return updated table rows only
            foreach ($teachers as $teacher) {
                echo "
                    <tr>
                        <td>" . htmlspecialchars($teacher['teacher_id']) . "</td>
                        <td>" . htmlspecialchars($teacher['name']) . "</td>
                        <td>" . htmlspecialchars($teacher['email']) . "</td>
                        <td>" . htmlspecialchars($teacher['username']) . "</td>
                        <td>" . htmlspecialchars($teacher['enrollment_code']) . "</td>
                        <td>
                            <button class='btn btn-danger btn-sm delete-teacher' data-teacher-id='" . htmlspecialchars($teacher['teacher_id']) . "'>Delete</button>
                        </td>
                    </tr>
                ";
            }
            exit;
        } catch (Exception $e) {
            echo "Error deleting teacher: " . $e->getMessage();
            exit;
        }
    }
}



// Fetch all teachers
$stmt = $pdo->query("SELECT * FROM teachers");
$teachers = $stmt->fetchAll();
?>

<style>
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f8f9fa;
    }
    .container {
        margin-top: 20px;
        margin-bottom: 40px;
    }
    .card-header {
        background-color: #198754;
        color: white;
        font-weight: bold;
    }
    .btn-primary {
        background-color: #198754;
        border: none;
    }
    .btn-primary:hover {
        background-color: #145e38;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }
    .table {
        font-size: 0.9rem;
    }
    .table th {
        background-color: #f1f1f1;
    }
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #c3e6cb;
    }
    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="container">
    <h2 class="mb-4">Manage Teachers</h2>

    <!-- Add New Teacher Form -->
    <div class="card mb-4">
        <div class="card-header">Add New Teacher</div>
        <div class="card-body">
            <form id="addTeacherForm">
                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="enrollment_code" class="form-label">Enrollment Code:</label>
                    <input type="text" id="enrollment_code" name="enrollment_code" class="form-control" required>
                </div>
                <button type="button" class="btn btn-primary" id="submitAddTeacher">Add Teacher</button>
            </form>
        </div>
    </div>

    <!-- List of Teachers -->
    <div class="card">
        <div class="card-header">All Teachers</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Teacher ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Enrollment Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><?php echo htmlspecialchars($teacher['enrollment_code']); ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-teacher" data-teacher-id="<?php echo htmlspecialchars($teacher['teacher_id']); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Add Teacher Functionality
    document.getElementById('submitAddTeacher').addEventListener('click', function () {
        const form = document.getElementById('addTeacherForm');
        const formData = new FormData(form);
        formData.append('add_teacher', true);

        fetch('manage_teachers.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.text())
        .then(data => {
            document.querySelector('.container').innerHTML = data;
            attachListeners(); // Reattach listeners to updated content
        })
        .catch(error => console.error('Error:', error));
    });

    // Function to attach listeners for delete buttons
    function attachListeners() {
        const deleteTeacherButtons = document.querySelectorAll('.delete-teacher');
        deleteTeacherButtons.forEach(button => {
            button.addEventListener('click', function () {
                const teacherId = this.getAttribute('data-teacher-id');
                const formData = new FormData();
                formData.append('delete_teacher', true);
                formData.append('teacher_id', teacherId);

                fetch('manage_teachers.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.text())
                .then(data => {
                    document.querySelector('.container').innerHTML = data;
                    attachListeners(); // Reattach listeners to updated content
                })
                .catch(error => console.error('Error:', error));
            });
        });
    }

    // Attach listeners for delete buttons on page load
    attachListeners();
</script>

