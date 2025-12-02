<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check user type and credentials
    $stmt = $pdo->prepare("SELECT * FROM superadmin WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $superadmin = $stmt->fetch();

    if ($superadmin && password_verify($password, $superadmin['password'])) {
        $_SESSION['superadmin_id'] = $superadmin['id'];
        $_SESSION['superadmin_username'] = $superadmin['username'];
        header('Location: superadmin');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: admin_dashboard');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $teacher = $stmt->fetch();

    if ($teacher && password_verify($password, $teacher['password'])) {
        $_SESSION['teacher_id'] = $teacher['teacher_id'];
        $_SESSION['teacher_username'] = $teacher['username'];
        header('Location: teacher_dashboard');
        exit;
    }

    $error = "Invalid credentials.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS LOGIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: rgb(25, 135, 84);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            display: flex;
            width: 80%;
            max-width: 900px;
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0px 30px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-10px);
            box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.3);
        }

        .login-image {
            flex: 1;
            background-image: url('images/1.png');
            background-size: cover;
            background-position: center;
            height: 20rem;
            margin-top: 3rem;
        }

        .login-form {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form h2 {
            font-weight: bold;
            color: rgb(25, 135, 84);
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 30px;
            padding: 10px 20px;
        }

        .btn-login {
            background-color: rgb(25, 135, 84);
            color: #fff;
            border-radius: 30px;
            padding: 10px 20px;
        }

        .btn-login:hover {
            background-color: #218c6e;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }

        .forgot-password {
            color: #555;
            text-decoration: none;
            margin-top: 10px;
            display: block;
            text-align:center;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 20px;
            text-align: center;
        }
        
    </style>
    <link rel="icon" href="images/favicon.png" type="image/png">
</head>
<body>
    <div class="login-container">
        <div class="login-image"></div>
        <div class="login-form">
            <h2>CVSU-IMUS AMS LOGIN</h2>
            <p>Please log in to your account to continue.</p>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-login">Login</button>
                </div>
            </form>
            <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm">
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold">Enter Your Email Address</label>
                        <input type="email" name="email" id="email" class="form-control rounded-pill" placeholder="example@email.com" required>
                    </div>
                    <div id="accountTypeOptions" style="display: none;" class="mt-4">
    <p class="fw-bold text-center">Select Account Type</p>
    <div class="row text-center">
        <div class="col-6">
            <button type="button" 
                    class="btn w-100 rounded-pill" 
                    style="background-color: #1d3557; color: #fff; font-weight: bold;"
                    id="teacherOption">
                🎓 Teacher
            </button>
        </div>
        <div class="col-6">
            <button type="button" 
                    class="btn w-100 rounded-pill" 
                    style="background-color: #e63946; color: #fff; font-weight: bold;"
                    id="adminOption">
                🛠️ Admin
            </button>
        </div>
    </div>
</div>
                    <button type="button" 
        class="btn btn-success btn-lg rounded-pill w-100 shadow-sm"
        onclick="submitForgotPassword()" style="margin-top:1rem;">
    Submit
</button>
                </form>
                <div id="forgotPasswordMessage" class="mt-4 text-center"></div>
            </div>
        </div>
    </div>
</div>


    <script>
        function submitForgotPassword() {
            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('forgotPasswordMessage');
            const accountTypeOptions = document.getElementById('accountTypeOptions');
            accountTypeOptions.style.display = 'none';
            messageDiv.innerHTML = '';

            fetch('forgot_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else if (data.status === 'choose') {
                    accountTypeOptions.style.display = 'block';
                    document.getElementById('teacherOption').onclick = () => submitForgotPasswordWithType(email, 'teachers');
                    document.getElementById('adminOption').onclick = () => submitForgotPasswordWithType(email, 'admins');
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(() => {
                messageDiv.innerHTML = `<div class="alert alert-danger">An error occurred. Please try again.</div>`;
            });
        }

        function submitForgotPasswordWithType(email, userType) {
            const messageDiv = document.getElementById('forgotPasswordMessage');
            messageDiv.innerHTML = '';

            fetch('forgot_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, user_type: userType })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(() => {
                messageDiv.innerHTML = `<div class="alert alert-danger">An error occurred. Please try again.</div>`;
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
