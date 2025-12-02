<?php
require 'config.php'; // Ensure this file contains the PDO setup

try {
    // Query to find the highest fingerprint_id in both tables
    $sql = "
        SELECT MAX(max_id) AS max_id
        FROM (
            SELECT MAX(fingerprint_id) AS max_id FROM teachers
            UNION ALL
            SELECT MAX(fingerprint_id) AS max_id FROM fingerprints
        ) AS combined
    ";

    $stmt = $pdo->query($sql); // Use PDO for querying
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $next_id = ($result['max_id'] ?? 0) + 1; // Start from 1 if no IDs exist
        echo $next_id;
    } else {
        http_response_code(500);
        echo "Error calculating next ID.";
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
?>
