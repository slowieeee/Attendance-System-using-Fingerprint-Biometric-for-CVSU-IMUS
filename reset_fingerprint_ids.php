<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "attendance_system";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update the teachers and students tables to reset fingerprint IDs and templates
$sqlTeachers = "UPDATE teachers SET fingerprint_id = NULL, fingerprint_template = NULL";
$sqlStudents = "UPDATE students SET fingerprint_id = NULL, fingerprint_template = NULL";

if ($conn->query($sqlTeachers) === TRUE && $conn->query($sqlStudents) === TRUE) {
    http_response_code(200);
    echo "success";
} else {
    http_response_code(500);
    echo "Failed to reset database.";
}

$conn->close();
?>
