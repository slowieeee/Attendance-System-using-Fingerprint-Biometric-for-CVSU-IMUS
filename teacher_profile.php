<?php
session_start();
require 'config.php';

// Ensure the teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher details from the database
$stmt = $pdo->prepare("SELECT username, enrollment_code, name, email FROM teachers WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    echo "Error: Teacher not found.";
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate the new password if it is provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error_message = "Password must include at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error_message = "Password must include at least one lowercase letter.";
        } elseif (!preg_match('/\d/', $password)) {
            $error_message = "Password must include at least one number.";
        } elseif (!preg_match('/[@$!%*?&#]/', $password)) {
            $error_message = "Password must include at least one special character (e.g., @$!%*?&#).";
        } else {
            // If the password is valid, hash it
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    // Proceed with the update only if there's no error
    if (empty($error_message)) {
        try {
            $query = "UPDATE teachers SET name = :name, email = :email";
            $params = [
                'name' => $name,
                'email' => $email,
                'teacher_id' => $teacher_id
            ];

            if (!empty($hashed_password)) {
                $query .= ", password = :password";
                $params['password'] = $hashed_password;
            }

            $query .= " WHERE teacher_id = :teacher_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            $success_message = "Profile updated successfully.";
        } catch (Exception $e) {
            $error_message = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            margin: 20px auto;
            max-width: 900px;
        }

        .navbar {
            background-color: rgb(25, 135, 84);
        }

        .navbar .navbar-brand {
            color: white;
        }

        .navbar .btn-back {
            background-color: #ffffff;
            color: rgb(25, 135, 84);
            border: none;
        }

        .navbar .btn-back:hover {
            background-color: rgb(25, 135, 84);
            color: white;
        }

        .form-section {
            margin-top: 30px;
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width:30rem;

        }

        .form-section h3 {
            font-size: 1.8rem;
            color: rgb(25, 135, 84);
            margin-bottom: 20px;
            text-align: center;
        }

        .alert {
            text-align: center;
        }

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
            margin-left: -1%;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Teacher Profile</a>
            <img src="images/3.png" alt="Header Image" class="logo">
            <button class="btn btn-outline-light" onclick="window.location.href='teacher_dashboard'">Back to Dashboard</button>
        </div>
    </nav>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Profile Update Form -->
        <div class="form-section">
            <h3>Edit Profile</h3>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="teacher_profile.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($teacher['username']); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label for="enrollment_code" class="form-label">Enrollment Code</label>
                    <input type="text" id="enrollment_code" class="form-control" value="<?php echo htmlspecialchars($teacher['enrollment_code']); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($teacher['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" class="btn btn-success w-100">Save Changes</button>
            </form>
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
