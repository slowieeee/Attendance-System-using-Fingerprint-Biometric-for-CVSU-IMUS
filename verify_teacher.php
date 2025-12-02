<?php
require 'config.php';

// Check if fingerprint_template is sent
if (!isset($_POST['fingerprint_template'])) {
    http_response_code(400);
    echo "Missing fingerprint_template";
    exit;
}

$fingerprint_template = $_POST['fingerprint_template'];

// Debugging: Log the received template (remove or secure this in production)
file_put_contents('debug_log.txt', "Received Template: $fingerprint_template\n", FILE_APPEND);

// Fetch teacher using the Base64 template
$stmt = $pdo->prepare("SELECT teacher_id FROM teachers WHERE fingerprint_template = :fingerprint_template");
$stmt->execute(['fingerprint_template' => $fingerprint_template]);
$teacher = $stmt->fetch();

if ($teacher) {
    echo "valid"; // Fingerprint matches
} else {
    http_response_code(404);
    echo "not_found";
}
?>
