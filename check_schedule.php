<?php
require 'config.php';

if (!isset($_POST['teacher_id'])) {
    http_response_code(400);
    echo "Missing teacher_id";
    exit;
}

$teacher_id = $_POST['teacher_id'];
$current_time = date('H:i:s'); // Current time
$current_day = date('l'); // Current day

$stmt = $pdo->prepare("
    SELECT * FROM schedules
    WHERE teacher_id = :teacher_id AND day = :current_day
      AND :current_time BETWEEN start_time AND end_time
");
$stmt->execute([
    'teacher_id' => $teacher_id,
    'current_day' => $current_day,
    'current_time' => $current_time
]);

$schedule = $stmt->fetch();

if ($schedule) {
    echo "active";
} else {
    http_response_code(404);
    echo "no_schedule";
}
?>
