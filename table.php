<?php
require "includes/functions.php";
require "includes/db.php";
requireStudent();

$userId = $_SESSION['user_id'];
$studentId = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$studentId->execute([$userId]);
$studentId = $studentId->fetch()['id'];

$stmt = $pdo->prepare("
    SELECT title, category, status, created_at
    FROM complaints
    WHERE student_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$studentId]);
$complaints = $stmt->fetchAll();

$pageTitle = "Complaints";
$activeNav = "table";
include "includes/header_student.php";
include "views/table.html";
include "includes/footer_student.php";
