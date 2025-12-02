<?php
// Include database connection
include('config.php');  // Include your config.php file to ensure $pdo is set

// Check if $pdo is set
if (!$pdo) {
    die("Database connection failed. PDO object is not set.");
}

// Check if student_id is passed
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    try {
        // Prepare SQL query to check if the fingerprint exists
        $sql = "SELECT * FROM fingerprints WHERE student_id = :student_id";
        $stmt = $pdo->prepare($sql);  // Use the PDO connection ($pdo) for preparation

        // Bind the student ID parameter
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Fingerprint exists, respond accordingly
            echo "Fingerprint found";
        } else {
            // No matching fingerprint found
            echo "Fingerprint not found";
        }
    } catch (PDOException $e) {
        // Handle errors
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No student ID provided.";
}
?>
