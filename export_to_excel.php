<?php
require 'config.php';
require 'vendor/autoload.php'; // Include PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$section_code = $_POST['section'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

// Fetch the section name based on the section code
$section_name = 'All_Sections';
if (!empty($section_code)) {
    $section_stmt = $pdo->prepare("
        SELECT section_name 
        FROM sections 
        WHERE section_code = :section_code AND teacher_id = :teacher_id
    ");
    $section_stmt->execute(['section_code' => $section_code, 'teacher_id' => $teacher_id]);
    $section_name = $section_stmt->fetchColumn() ?: $section_name;
}

// Query to fetch attendance records
$query = "
    SELECT 
        students.student_id, 
        students.name AS student_name, 
        attendance.class_date, 
        attendance.time_in, 
        attendance.time_out, 
        attendance.status, 
        sections.section_name
    FROM attendance
    JOIN students ON attendance.student_id = students.student_id
    JOIN sections ON attendance.section_code = sections.section_code
    WHERE attendance.teacher_id = :teacher_id
";

$params = ['teacher_id' => $teacher_id];

if (!empty($section_code)) {
    $query .= " AND attendance.section_code = :section_code";
    $params['section_code'] = $section_code;
}
if (!empty($start_date)) {
    $query .= " AND attendance.class_date >= :start_date";
    $params['start_date'] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND attendance.class_date <= :end_date";
    $params['end_date'] = $end_date;
}

$query .= " ORDER BY attendance.class_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance Records');

// Add headers
$sheet->fromArray(
    ['Student ID', 'Student Name', 'Class Date', 'Time In', 'Time Out', 'Status', 'Section'],
    null,
    'A1'
);

// Add data rows
$row = 2;
foreach ($records as $record) {
    $sheet->fromArray(array_values($record), null, "A$row");
    $row++;
}

// Generate the filename
$dateRange = (!empty($start_date) && !empty($end_date)) 
    ? "from_{$start_date}_to_{$end_date}" 
    : (!empty($start_date) ? "from_{$start_date}" : (!empty($end_date) ? "until_{$end_date}" : "all_dates"));

$filename = "{$section_name}_attendance_{$dateRange}.xlsx";
$filename = str_replace([' ', '/'], '_', $filename); // Sanitize filename

// Debugging the filename
error_log("Generated Filename: $filename");

// Ensure output buffering is cleared
if (ob_get_length()) {
    ob_clean();
}

// Set headers and output the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
