<?php
session_start();
require 'config.php';

// Ensure the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch admin details
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

$stmt = $pdo->prepare("SELECT email FROM admins WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email address.';
        }

        // Validate password if provided
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error_message = 'Password must be at least 8 characters long.';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $error_message = 'Password must include at least one uppercase letter.';
            } elseif (!preg_match('/[a-z]/', $password)) {
                $error_message = 'Password must include at least one lowercase letter.';
            } elseif (!preg_match('/\d/', $password)) {
                $error_message = 'Password must include at least one number.';
            } elseif (!preg_match('/[@$!%*?&#]/', $password)) {
                $error_message = 'Password must include at least one special character (e.g., @$!%*?&#).';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        // Update the profile if there are no errors
        if (empty($error_message)) {
            try {
                $update_query = "UPDATE admins SET email = :email";
                $params = ['email' => $email, 'admin_id' => $admin_id];

                // If a valid password is provided, update it
                if (!empty($hashed_password)) {
                    $update_query .= ", password = :password";
                    $params['password'] = $hashed_password;
                }

                $update_query .= " WHERE admin_id = :admin_id";
                $stmt = $pdo->prepare($update_query);
                $stmt->execute($params);

                $success_message = 'Profile updated successfully.';
            } catch (Exception $e) {
                $error_message = 'Failed to update profile: ' . $e->getMessage();
            }
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
    <title>Admin Profile</title>
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

        .container-profile {
            flex-grow: 1;
            padding: 40px 20px;
            max-width: 600px;
            margin: auto;
        }

        .profile-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 8px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            animation: fadeIn 0.5s ease-in-out;
            width:30rem;
        }

        .profile-card h2 {
            text-align: center;
            color: rgb(25, 135, 84);
            margin-bottom: 20px;
        }

        .success-message, .error-message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-label {
            font-weight: bold;
            color: #343a40;
        }

        .form-control:focus {
            border-color: rgb(25, 135, 84);
            box-shadow: 0 0 5px rgba(25, 135, 84, 0.4);
        }

        .btn-success {
            background-color: rgb(25, 135, 84);
            border: none;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-success:hover {
            background-color: #1d7b6a;
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.4);
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
        }

        footer img {
            max-height: 50px;
            object-fit: contain;
        }

        .navbar .btn-back {
            color: white;
            border: none;
            background-color: transparent;
            transition: color 0.3s ease;
        }

        .navbar img {
            max-height: 50px;
            transform: scale(3.0);
            object-fit: contain;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="#">Manage Admin Profile</a>
            <img src="images/3.png" alt="Header Image" class="mx-auto">
            <button class="btn-back" onclick="location.href='admin_dashboard'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
        </div>
    </nav>

    <!-- Profile Content -->
    <div class="container-profile">
        <div class="profile-card">
            <h2>Update Profile</h2>

            <!-- Success or Error Messages -->
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($admin_username); ?>" readonly>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">New Password (optional)</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" name="update_profile" class="btn btn-success w-100">Update Profile</button>
            </form>
        </div>
    </div>

    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance Management System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="mx-auto">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
