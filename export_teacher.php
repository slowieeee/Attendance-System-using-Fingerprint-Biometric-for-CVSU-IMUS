<?php
require 'vendor/autoload.php'; // Include PhpSpreadsheet library
require 'config.php'; // Use the external config.php for database connection

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$teacher_id = $_GET['teacher_id'] ?? null;

if (!$teacher_id) {
    die("Invalid teacher ID.");
}

try {
    // Fetch teacher and attendance details
    $query = "
        SELECT DISTINCT t.name AS teacher_name, ta.section_code, ta.subject 
        FROM teacher_attendance ta
        JOIN teachers t ON ta.teacher_id = t.teacher_id
        WHERE ta.teacher_id = :teacher_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['teacher_id' => $teacher_id]);
    $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacherData) {
        die("No records found for the specified teacher.");
    }

    $teacherName = $teacherData['teacher_name'];
    $sectionCode = $teacherData['section_code'];
    $subjectName = $teacherData['subject'];

    // Fetch attendance records for the teacher
    $attendanceQuery = "
        SELECT ta.*, t.name AS teacher_name 
        FROM teacher_attendance ta
        JOIN teachers t ON ta.teacher_id = t.teacher_id
        WHERE ta.teacher_id = :teacher_id
    ";
    $stmt = $pdo->prepare($attendanceQuery);
    $stmt->execute(['teacher_id' => $teacher_id]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create Excel spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set sheet headers
    $sheet->setCellValue('A1', 'Attendance ID');
    $sheet->setCellValue('B1', 'Teacher Name');
    $sheet->setCellValue('C1', 'Section Code');
    $sheet->setCellValue('D1', 'Time In');
    $sheet->setCellValue('E1', 'Time Out');
    $sheet->setCellValue('F1', 'Class Date');
    $sheet->setCellValue('G1', 'Status');
    $sheet->setCellValue('H1', 'Subject');

    // Fill data
    $rowNumber = 2;
    foreach ($attendanceRecords as $row) {
        $sheet->setCellValue("A{$rowNumber}", $row['attendance_id']);
        $sheet->setCellValue("B{$rowNumber}", $row['teacher_name']);
        $sheet->setCellValue("C{$rowNumber}", $row['section_code']);
        $sheet->setCellValue("D{$rowNumber}", $row['time_in']);
        $sheet->setCellValue("E{$rowNumber}", $row['time_out'] ?? 'N/A');
        $sheet->setCellValue("F{$rowNumber}", $row['class_date']);
        $sheet->setCellValue("G{$rowNumber}", $row['status']);
        $sheet->setCellValue("H{$rowNumber}", $row['subject']);
        $rowNumber++;
    }

    // Generate dynamic filename
    $sanitizedTeacherName = preg_replace('/[^A-Za-z0-9 _-]/', '', $teacherName);
    $sanitizedSectionCode = preg_replace('/[^A-Za-z0-9 _-]/', '', $sectionCode);
    $sanitizedSubjectName = preg_replace('/[^A-Za-z0-9 _-]/', '', $subjectName);
    $filename = "{$sanitizedTeacherName}_{$sanitizedSectionCode}_{$sanitizedSubjectName}_Attendance_Report.xlsx";

    // Export to Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
