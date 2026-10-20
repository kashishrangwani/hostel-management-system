<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

// Real datasets for each report type. reports.js filters/aggregates
// these client-side so switching filters feels instant (matching the
// original design), but every number in it comes straight from MySQL.

$studentsData = $pdo->query("
    SELECT s.roll_no, u.name, s.course, s.status, r.room_number
    FROM students s JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id LEFT JOIN rooms r ON b.room_id = r.id
")->fetchAll();

$roomsData = $pdo->query("
    SELECT r.room_number, r.block, r.floor, r.status,
           COUNT(b.id) AS capacity,
           SUM(CASE WHEN b.student_id IS NOT NULL THEN 1 ELSE 0 END) AS occupied
    FROM rooms r LEFT JOIN beds b ON b.room_id = r.id
    GROUP BY r.id
")->fetchAll();

$feesData = $pdo->query("
    SELECT u.name, r.room_number, COALESCE(fa.total_fee,0) AS total_fee,
           COALESCE((SELECT SUM(amount) FROM fee_payments WHERE student_id = s.id), 0) AS paid,
           (SELECT MAX(paid_on) FROM fee_payments WHERE student_id = s.id) AS last_paid_on
    FROM students s JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN fee_accounts fa ON fa.student_id = s.id
")->fetchAll();
foreach ($feesData as &$f) {
    $due = $f['total_fee'] - $f['paid'];
    $f['status'] = ($f['total_fee'] > 0 && $due <= 0) ? 'Paid' : (($f['paid'] > 0) ? 'Pending' : 'Overdue');
    $f['due'] = max(0, $due);
}
unset($f);

$leaveData = $pdo->query("
    SELECT u.name, s.roll_no, r.room_number, l.leave_type, l.from_date, l.to_date, l.cancelled
    FROM leave_requests l JOIN students s ON l.student_id = s.id JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id LEFT JOIN rooms r ON b.room_id = r.id
")->fetchAll();
foreach ($leaveData as &$l) {
    $l['status'] = leaveStatus($l['from_date'], $l['to_date'], $l['cancelled']);
}
unset($l);

$complaintsData = $pdo->query("
    SELECT u.name, s.roll_no, c.category, c.status, c.created_at
    FROM complaints c JOIN students s ON c.student_id = s.id JOIN users u ON s.user_id = u.id
")->fetchAll();

$pageTitle = "Reports";
$activeNav = "reports";
$pageCss = "reports.css";
$useChartJs = true;
include "includes/header_admin.php";
include "views/reports.html";
