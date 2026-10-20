<?php
require "includes/functions.php";
require "includes/db.php";
requireStudent();

$userId = $_SESSION['user_id'];

// This student's profile + current bed/room (if any).
$stmt = $pdo->prepare("
    SELECT s.id AS student_id, s.roll_no, s.course,
           b.bed_label, r.room_number, r.block, r.floor
    FROM students s
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    WHERE s.user_id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();
$studentId = $student['student_id'];

// Roommate(s): other students sharing the same room.
$roommates = [];
if ($student['room_number']) {
    $stmt = $pdo->prepare("
        SELECT u.name, s.course
        FROM beds b
        JOIN students s ON b.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        WHERE r.room_number = ? AND s.id != ?
    ");
    $stmt->execute([$student['room_number'], $studentId]);
    $roommates = $stmt->fetchAll();
}

// Latest 3 notices relevant to everyone (audience filtering kept simple: show all).
$stmt = $pdo->prepare("
    SELECT title, category, description, publish_date
    FROM notices
    WHERE publish_date <= CURDATE()
    ORDER BY publish_date DESC, id DESC
    LIMIT 3
");
$stmt->execute();
$notices = $stmt->fetchAll();

$pageTitle = "Dashboard";
$activeNav = "dashboard";
include "includes/header_student.php";
include "views/dashboard.html";
include "includes/footer_student.php";
