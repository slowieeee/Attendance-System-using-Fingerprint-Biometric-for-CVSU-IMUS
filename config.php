<?php
// Database connection settings
$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root'; // Change if necessary
$password = ''; // Change if necessary

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
