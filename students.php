<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

$message = "";

// Add a new student.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = trim($_POST['name']);
    $rollNo = trim($_POST['roll_no']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $course = trim($_POST['course']) . ' - ' . trim($_POST['year']);
    $guardianName = trim($_POST['guardian_name']);
    $guardianPhone = trim($_POST['guardian_phone']);
    $address = trim($_POST['address']);

    $exists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $exists->execute([$email]);

    if ($exists->fetch()) {
        $message = "A user with that email already exists.";
    } else {
        $rollCheck = $pdo->prepare("SELECT id FROM students WHERE roll_no = ?");
        $rollCheck->execute([$rollNo]);
        if ($rollCheck->fetch()) {
            $message = "A student with that roll number already exists.";
        } else {
            try {
                $pdo->beginTransaction();
                $tempPassword = substr(md5(uniqid()), 0, 8); // random initial password
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'student', ?)");
                $stmt->execute([$name, $email, password_hash($tempPassword, PASSWORD_DEFAULT), $phone]);
                $userId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("
                    INSERT INTO students (user_id, roll_no, course, gender, dob, guardian_name, guardian_phone, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $rollNo, $course, $gender, $dob ?: null, $guardianName, $guardianPhone, $address]);
                $studentId = $pdo->lastInsertId();

                // Auto-create fee account so Fees page always has a row.
                $pdo->prepare("INSERT INTO fee_accounts (student_id, total_fee) VALUES (?, 0)")
                    ->execute([$studentId]);

                $pdo->commit();
                $message = "Student added. Temporary password: $tempPassword";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Could not add student. Please try again.";
            }
        }
    }
}

// Edit an existing student.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $studentId = (int) $_POST['student_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $course = trim($_POST['course']);

    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $userId = $stmt->fetch()['user_id'];

    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $email, $phone, $userId]);

    $stmt = $pdo->prepare("UPDATE students SET status = ?, course = ? WHERE id = ?");
    $stmt->execute([$status, $course, $studentId]);

    $message = "Student details updated.";
}

// Delete a student (POST only — avoids accidental / CSRF-style GET deletes).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $studentId = (int) $_POST['student_id'];
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    if ($row = $stmt->fetch()) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$row['user_id']]); // cascades
    }
    header("Location: students.php");
    exit();
}

// All students with room/bed, and computed stats used by the view modal.
$students = $pdo->query("
    SELECT s.id, s.roll_no, s.course, s.gender, s.dob, s.guardian_name, s.guardian_phone,
           s.address, s.status, s.created_at, u.name, u.email, u.phone,
           r.room_number, b.bed_label,
           (SELECT COUNT(*) FROM complaints WHERE student_id = s.id) AS complaint_count,
           (SELECT COUNT(*) FROM leave_requests WHERE student_id = s.id) AS leave_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY s.id DESC
")->fetchAll();

$courses = ['BCA', 'MCA', 'BSc IT'];
$years = ['1st Year', '2nd Year', '3rd Year'];

$pageTitle = "Students";
$activeNav = "students";
$pageCss = "students.css";
include "includes/header_admin.php";
include "views/students.html";
