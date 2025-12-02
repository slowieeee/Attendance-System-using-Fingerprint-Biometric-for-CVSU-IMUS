<?php
require 'config.php';
require 'vendor/autoload.php'; // Include PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $teacher_id = $_POST['teacher_id'] ?? null;

    if (!$teacher_id) {
        $action_success = false;
        $action_message = "Invalid teacher ID.";
        $popup_needed = true; // Set popup flag for error
        include 'manage_sections.php'; // Include manage_sections.php with error message
        exit;
    }

    // Check if the uploaded file is valid
    $allowed_types = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowed_types)) {
        $action_success = false;
        $action_message = "Invalid file type. Please upload an Excel file.";
        $popup_needed = true; // Set popup flag for error
        include 'manage_sections.php'; // Include manage_sections.php with error message
        exit;
    }

    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Extract section details from the file name
        $filename = $file['name'];
        preg_match('/^Class List - (\d+)\s+([A-Za-z0-9\s]+)\s+([A-Za-z0-9-]+)\.xlsx$/', $filename, $matches);

        if (isset($matches[1], $matches[2], $matches[3])) {
            $section_code = $matches[1];
            $subject = $matches[2];
            $section_name = $matches[3];
        } else {
            $action_success = false;
            $action_message = "Invalid Excel format. Please ensure the file name matches Class List - section_code subject section_name.xlsx.";
            $popup_needed = true; // Set popup flag for error
            include 'manage_sections.php'; // Include manage_sections.php with error message
            exit;
        }

        // Insert or update the section in the database
        $stmt = $pdo->prepare("
            INSERT INTO sections (teacher_id, section_code, subject, section_name)
            VALUES (:teacher_id, :section_code, :subject, :section_name)
            ON DUPLICATE KEY UPDATE
            subject = VALUES(subject), section_name = VALUES(section_name)
        ");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'section_code' => $section_code,
            'subject' => $subject,
            'section_name' => $section_name,
        ]);

        // Loop through the rows and add/update students in the database
        foreach ($data as $index => $row) {
            // Skip the first two rows (header rows)
            if ($index < 2) continue;

            $student_id = $row[5] ?? null; // Assuming "Student Number" is in the 6th column
            $student_name = $row[4] ?? null; // Assuming "Full Name" is in the 5th column

            if (empty($student_id) || empty($student_name)) {
                continue; // Skip invalid data
            }

            $stmt = $pdo->prepare("
                INSERT INTO students (student_id, name, section, section_code)
                VALUES (:student_id, :name, :section, :section_code)
                ON DUPLICATE KEY UPDATE
                name = VALUES(name), section = VALUES(section), section_code = VALUES(section_code)
            ");
            $stmt->execute([
                'student_id' => $student_id,
                'name' => $student_name,
                'section' => $section_name,
                'section_code' => $section_code,
            ]);
        }

        // Set success message
        $action_success = true;
        $action_message = "Section added successfully!";
        $popup_needed = true; // Set popup flag for success
        include 'manage_sections.php'; // Include manage_sections.php with success message
        exit;
    } catch (Exception $e) {
        $action_success = false;
        $action_message = "Error processing the file: " . $e->getMessage();
        $popup_needed = true; // Set popup flag for error
        include 'manage_sections.php'; // Include manage_sections.php with error message
        exit;
    }
}
