<?php
require 'config.php';

if (!isset($_POST['fingerprint_id'])) {
    http_response_code(400);
    echo "Missing fingerprint ID";
    exit;
}

$fingerprint_id = $_POST['fingerprint_id'];

// Check if the fingerprint ID exists in the database
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE fingerprint_id = :fingerprint_id");
$stmt->execute(['fingerprint_id' => $fingerprint_id]);
$teacher = $stmt->fetch();

if ($teacher) {
    echo "valid";
} else {
    http_response_code(404);
    echo "invalid";
}
?>
