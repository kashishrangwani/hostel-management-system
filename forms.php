<?php
require "includes/functions.php";
require "includes/db.php";
requireStudent();

$userId = $_SESSION['user_id'];
$studentId = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$studentId->execute([$userId]);
$studentId = $studentId->fetch()['id'];

$complaintMessage = "";
$leaveMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $title = trim($_POST['complaint_title']);
    $category = $_POST['complaint_category'];
    $desc = trim($_POST['complaint_desc']);

    if ($title !== "" && $desc !== "") {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO complaints (student_id, title, category, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$studentId, $title, $category, $desc]);
        $complaintId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, status, remarks) VALUES (?, 'New', 'Complaint submitted by student.')");
        $stmt->execute([$complaintId]);
        $pdo->commit();

        $complaintMessage = "Complaint submitted successfully. Ticket #$complaintId created.";
    } else {
        $complaintMessage = "Please fill in all complaint fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $from = $_POST['leave_from'];
    $to = $_POST['leave_to'];
    $type = $_POST['leave_type'];
    $reason = trim($_POST['leave_reason']);

    if ($from && $to && $reason !== "" && $to >= $from) {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, leave_type, from_date, to_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $type, $from, $to, $reason]);
        $leaveMessage = "Leave application submitted successfully.";
    } else {
        $leaveMessage = "Please check your dates and reason.";
    }
}

$pageTitle = "Forms";
$activeNav = "forms";
include "includes/header_student.php";
include "views/forms.html";
include "includes/footer_student.php";
