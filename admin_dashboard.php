<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

$totalStudents = $pdo->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
$totalRooms    = $pdo->query("SELECT COUNT(*) AS c FROM rooms")->fetch()['c'];
$totalBeds     = $pdo->query("SELECT COUNT(*) AS c FROM beds")->fetch()['c'];
$occupiedBeds  = $pdo->query("SELECT COUNT(*) AS c FROM beds WHERE student_id IS NOT NULL")->fetch()['c'];
$occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;

$pendingFees = $pdo->query("
    SELECT COALESCE(SUM(fa.total_fee),0) - COALESCE((SELECT SUM(amount) FROM fee_payments),0) AS due
    FROM fee_accounts fa
")->fetch()['due'];
$pendingLeaves = $pdo->query("SELECT COUNT(*) AS c FROM leave_requests WHERE cancelled = 0 AND to_date >= CURDATE()")->fetch()['c'];
$openComplaints = $pdo->query("SELECT COUNT(*) AS c FROM complaints WHERE status NOT IN ('Resolved','Closed')")->fetch()['c'];
$withoutRoom = $pdo->query("
    SELECT COUNT(*) AS c FROM students s WHERE s.id NOT IN (SELECT student_id FROM beds WHERE student_id IS NOT NULL)
")->fetch()['c'];

// Chart data: occupancy, students by course, fees status.
$courseData = $pdo->query("SELECT course, COUNT(*) AS c FROM students GROUP BY course")->fetchAll();
$paidTotal = $pdo->query("SELECT COALESCE(SUM(amount),0) AS t FROM fee_payments")->fetch()['t'];
$totalFeeSum = $pdo->query("SELECT COALESCE(SUM(total_fee),0) AS t FROM fee_accounts")->fetch()['t'];
$dueTotal = max(0, $totalFeeSum - $paidTotal);

// Recent allocations, recent activity (complaints + leave), active notices.
$recentAllocations = $pdo->query("
    SELECT u.name, r.room_number, b.bed_label, b.allocated_at
    FROM beds b
    JOIN students s ON b.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.allocated_at IS NOT NULL
    ORDER BY b.allocated_at DESC LIMIT 3
")->fetchAll();

$recentComplaints = $pdo->query("
    SELECT c.title, c.status, c.created_at, r.room_number, 'Complaint' AS kind
    FROM complaints c
    JOIN students s ON c.student_id = s.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY c.created_at DESC LIMIT 2
")->fetchAll();

$recentLeaves = $pdo->query("
    SELECT l.leave_type AS title, l.cancelled, l.from_date, l.to_date, l.created_at, r.room_number, 'Leave' AS kind
    FROM leave_requests l
    JOIN students s ON l.student_id = s.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY l.created_at DESC LIMIT 2
")->fetchAll();

$activeNotices = $pdo->query("
    SELECT title, audience, publish_date FROM notices
    WHERE publish_date <= CURDATE()
    ORDER BY publish_date DESC LIMIT 3
")->fetchAll();

$pageTitle = "Admin Dashboard";
$activeNav = "dashboard";
$pageCss = "admin_dashboard.css";
$useChartJs = true;
include "includes/header_admin.php";
include "views/admin_dashboard.html";
