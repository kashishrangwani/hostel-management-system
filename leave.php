<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

$today = date('Y-m-d');

$totalLeaves = $pdo->query("SELECT COUNT(*) AS c FROM leave_requests")->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE cancelled = 0 AND from_date > ?");
$stmt->execute([$today]);
$upcoming = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE cancelled = 0 AND from_date <= ? AND to_date >= ?");
$stmt->execute([$today, $today]);
$onLeaveToday = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE cancelled = 0 AND to_date < ?");
$stmt->execute([$today]);
$returned = $stmt->fetch()['c'];

$leaves = $pdo->query("
    SELECT l.id, l.leave_type, l.from_date, l.to_date, l.reason, l.cancelled, l.created_at,
           u.name, s.roll_no, s.id AS student_id, r.room_number
    FROM leave_requests l
    JOIN students s ON l.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY l.from_date DESC
")->fetchAll();

// Every leave record for every student, for the "Leave History" section of the modal.
$leaveHistory = $pdo->query("
    SELECT student_id, from_date, to_date, leave_type, cancelled FROM leave_requests ORDER BY from_date DESC
")->fetchAll();

$pageTitle = "Leave";
$activeNav = "leave";
$pageCss = "leave.css";
include "includes/header_admin.php";
include "views/leave.html";
