<?php
session_start();
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

// Ensure superadmin is logged in
if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch existing admins
$stmt = $pdo->prepare("SELECT admin_id, username, email FROM admins");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        // Add a new admin
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = bin2hex(random_bytes(4)); // Generate a random 8-character password

        if (!empty($username) && !empty($email)) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Check for existing admin with the same username or email
                $checkStmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username OR email = :email");
                $checkStmt->execute(['username' => $username, 'email' => $email]);
                $existingAdmin = $checkStmt->fetch();

                if ($existingAdmin) {
                    $_SESSION['error_message'] = "An admin with the same username or email already exists.";
                } else {
                    // Insert into the database
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword]);

                    // Send email with login details
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'cvsuimusattendance@gmail.com';
                        $mail->Password = 'mzls tsqj gqqi pauh';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('your_email@example.com', 'SuperAdmin');
                        $mail->addAddress($email, $username);

                        $mail->isHTML(true);
                        $mail->Subject = 'Account Created - Temporary Login Credentials';
                        $mail->Body = "
                            <p>Hello <strong>$username</strong>,</p>
                            <p>Your admin account has been created. Below are your login details:</p>
                            <ul>
                                <li><strong>Username:</strong> $username</li>
                                <li><strong>Temporary Password:</strong> $password</li>
                            </ul>
                            <p>Please log in and change your password in your profile settings.</p>
                        ";

                        $mail->send();
                        $_SESSION['success_message'] = "Admin added successfully! Login credentials have been emailed.";
                    } catch (Exception $e) {
                        $_SESSION['error_message'] = "Admin added successfully, but email could not be sent: {$mail->ErrorInfo}";
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Failed to add admin. Please try again.";
            }
        } else {
            $_SESSION['error_message'] = "Both username and email are required.";
        }
    } elseif (isset($_POST['remove_admin'])) {
        // Remove an admin
        $admin_id = $_POST['admin_id'];

        if (!empty($admin_id)) {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
            if ($stmt->execute([$admin_id])) {
                $_SESSION['success_message'] = "Admin removed successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to remove admin.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid admin ID.";
        }
    }

    header('Location: manage_admins.php');
    exit;
}
?>

<style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 80px;
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
        <h2 class="mb-4">Manage Admins</h2>
        <div id="loadingSpinner" style="display: none; text-align: center; margin: 20px;">
    <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <!-- Add New Admin Form -->
        <div class="card mb-4">
            <div class="card-header">Add New Admin</div>
            <div class="card-body">
                <form method="POST" action="manage_admins.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username:</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <button type="button" name="add_admin" class="btn btn-primary add-admin">Add Admin</button>

                </form>
            </div>
        </div>

        <!-- List of Admins -->
        <div class="card">
            <div class="card-header">All Admins</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($admins)): ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>

                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this admin?');">
                                            <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin['admin_id']); ?>">
                                            <button type="button" name="remove_admin" class="btn btn-danger btn-sm remove-admin" data-admin-id="<?php echo htmlspecialchars($admin['admin_id']); ?>">Remove</button>


                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No admins found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

