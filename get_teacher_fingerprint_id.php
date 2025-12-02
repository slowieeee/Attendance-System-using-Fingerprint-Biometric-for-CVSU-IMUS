<?php
require 'config.php';

$reference_id = $_GET['reference_id'] ?? null;

if (!$reference_id) {
    http_response_code(400);
    echo "Missing reference ID.";
    exit;
}

$stmt = $pdo->prepare("SELECT fingerprint_id FROM teachers WHERE enrollment_code = ?");
$stmt->execute([$reference_id]);
$fingerprint_id = $stmt->fetchColumn();

if ($fingerprint_id) {
    http_response_code(200);
    echo $fingerprint_id;
} else {
    http_response_code(404);
    echo "Fingerprint ID not found.";
}
