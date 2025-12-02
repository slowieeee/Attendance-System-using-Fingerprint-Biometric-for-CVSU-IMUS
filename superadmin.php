<?php
session_start();
require 'config.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}

$superadmin_username = $_SESSION['superadmin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
    <link rel="icon" href="images/favicon.png" type="image/png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .navbar {
            background-color: #198754;
            z-index: 1050;
            position: fixed;
            top: 0;
            width: 100%;
            height: 56px; /* Default height of the navbar */
            display: flex;
            align-items: center; /* Centers content vertically */
            justify-content: center; /* Centers content horizontally */
        }

        .navbar img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(3.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }

        /* Sidebar Styles */
        .sidebar {
            height: 96vh;
            background-color: #343a40;
            color: white;
            position: fixed;
            top: 55px;
            left: 0;
            width: 250px;
            overflow-x: hidden;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Adjust to move logout button to bottom */
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            white-space: nowrap;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed a span {
            display: none;
        }

        .sidebar a.active {
            background-color: #495057;
            font-weight: bold;
        }

        /* Logout Button */
        .logout-btn {
            text-align: center;
            padding: 20px 20px;
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            text-decoration: none;
            border-top: 1px solid #495057;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #b52a34;
        }

        /* Sidebar Toggle Button */
        .toggle-btn {
            position: absolute;
            top: 50%;
            left: 230px;
            transform: translateY(-50%);
            background-color: #343a40;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            width: 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1055;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed + .toggle-btn {
            left: 40px;
        }

        /* Content Area */
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            margin-top: 56px;
        }

        .sidebar.collapsed ~ .content {
            margin-left: 70px;
        }

        /* Footer */
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1px 0;
            margin-top: auto;
        }
        footer img {
            max-height: 50px; /* Restrict the image height to fit the navbar */
            max-width: none; /* Prevents it from resizing proportionally */
            transform: scale(1.0); /* Enlarges the image */
            object-fit: contain; /* Ensures the image fits within the container */
        }
        .sidebar.collapsed .logout-btn {
    display: none;
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <span class="navbar-brand ms-4">Super Admin Panel</span>
            <img src="images/3.png" alt="Header Image" class="mx-auto">
        </div>
    </nav>
        
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div>
            <a href="#" onclick="loadContent('manage_teachers.php')" id="manage-teachers">
                <i class="bi bi-person"></i> <span>Manage Teachers</span>
            </a>
            <a href="#" onclick="loadContent('manage_admins.php')" id="manage-admins">
                <i class="bi bi-gear"></i> <span>Manage Admins</span>
            </a>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="toggle-btn" id="sidebarToggle">&#9776;</button>

    <!-- Content Area -->
    <div class="content" id="mainContent">
        <h1 class="mb-4">Super Admin Dashboard</h1>
        <p>Welcome! Select an option from the sidebar to view details.</p>
    </div>

    <!-- Footer -->
    <footer>
        &copy; <?php echo date('Y'); ?> CVSU-IMUS Attendance Management System. All rights reserved.
        <img src="images/1.png" alt="Header Image" class="mx-auto">
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarLinks = document.querySelectorAll('.sidebar a');

        // Toggle Sidebar
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        // Dynamically load content
        function loadContent(page) {
            sidebarLinks.forEach(link => link.classList.remove('active'));
            document.querySelector(`[onclick="loadContent('${page}')"]`).classList.add('active');
            fetch(page)
                .then(response => response.text())
                .then(data => {
                    mainContent.innerHTML = data;

                    // Ensure the font-family is consistent
                    const link = document.createElement('link');
                    link.href = 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap';
                    link.rel = 'stylesheet';
                    document.head.appendChild(link);
                    attachListeners();
                    const style = document.createElement('style');
                    style.textContent = `body { font-family: 'Roboto', sans-serif; }`;
                    mainContent.appendChild(style);
                })
                .catch(error => {
                    console.error('Error loading content:', error);
                    mainContent.innerHTML = '<p>Error loading page. Please try again.</p>';
                });
        }
function attachListeners() {
    const loadingSpinner = document.getElementById('loadingSpinner');
    // Add Teacher Button
    const addTeacherButton = document.getElementById('submitAddTeacher');
    if (addTeacherButton) {
        addTeacherButton.addEventListener('click', function () {
            const form = document.getElementById('addTeacherForm');
            const formData = new FormData(form);
            formData.append('add_teacher', true);
            loadingSpinner.style.display = 'block';
            fetch('manage_teachers.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('mainContent').innerHTML = data;
                    attachListeners(); // Reattach listeners to new content
                })
                .catch(error => console.error('Error:', error));
        });
    }

    // Delete Teacher Button
    const deleteTeacherButtons = document.querySelectorAll('.delete-teacher');
        deleteTeacherButtons.forEach(button => {
            button.addEventListener('click', function () {
                const teacherId = this.getAttribute('data-teacher-id');
                const formData = new FormData();
                formData.append('delete_teacher', true);
                formData.append('teacher_id', teacherId);
                loadingSpinner.style.display = 'block';
                fetch('manage_teachers.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('mainContent').innerHTML = data;
                    attachListeners(); // Reattach listeners to updated content
                })
                .catch(error => console.error('Error:', error));
                
            });
        });
        // Add Admin Button
        const addAdminButton = document.querySelector('.add-admin');
    if (addAdminButton) {
        addAdminButton.addEventListener('click', function () {
            const form = addAdminButton.closest('form');
            const formData = new FormData(form);
            formData.append('add_admin', true);

            if (loadingSpinner) loadingSpinner.style.display = 'block'; // Show spinner
            fetch('manage_admins.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.text())
                .then(data => {
                    document.getElementById('mainContent').innerHTML = data;
                    attachListeners(); // Reattach listeners after content reload
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    if (loadingSpinner) loadingSpinner.style.display = 'none'; // Hide spinner
                });
        });
    }

    // Remove Admin Button
    const removeAdminButtons = document.querySelectorAll('.remove-admin');
    removeAdminButtons.forEach(button => {
        button.addEventListener('click', function () {
            const adminId = button.getAttribute('data-admin-id');
            const formData = new FormData();
            formData.append('remove_admin', true);
            formData.append('admin_id', adminId);

            if (confirm('Are you sure you want to remove this admin?')) {
                if (loadingSpinner) loadingSpinner.style.display = 'block'; // Show spinner
                fetch('manage_admins.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('mainContent').innerHTML = data;
                        attachListeners(); // Reattach listeners after content reload
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => {
                        if (loadingSpinner) loadingSpinner.style.display = 'none'; // Hide spinner
                    });
            }
        });
    });
        
    }

        // Load default page
        window.onload = () => {
            loadContent('manage_teachers.php');
        };
    </script>
</body>
</html>
