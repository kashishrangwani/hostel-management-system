<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

// Update a complaint's status + add a timeline remark.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complaint'])) {
    $complaintId = (int) $_POST['complaint_id'];
    $status = $_POST['status'];
    $remarks = trim($_POST['remarks']);

    $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?")->execute([$status, $complaintId]);
    $pdo->prepare("INSERT INTO complaint_updates (complaint_id, status, remarks) VALUES (?, ?, ?)")
        ->execute([$complaintId, $status, $remarks]);
}

$totalComplaints = $pdo->query("SELECT COUNT(*) AS c FROM complaints")->fetch()['c'];
$newCount = $pdo->query("SELECT COUNT(*) AS c FROM complaints WHERE status = 'New'")->fetch()['c'];
$inProgressCount = $pdo->query("SELECT COUNT(*) AS c FROM complaints WHERE status = 'In Progress'")->fetch()['c'];
$resolvedCount = $pdo->query("SELECT COUNT(*) AS c FROM complaints WHERE status IN ('Resolved','Closed')")->fetch()['c'];

$categoryData = $pdo->query("SELECT category, COUNT(*) AS c FROM complaints GROUP BY category")->fetchAll();

$complaints = $pdo->query("
    SELECT c.id, c.title, c.category, c.description, c.status, c.created_at,
           u.name, s.roll_no, r.room_number
    FROM complaints c
    JOIN students s ON c.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY c.created_at DESC
")->fetchAll();

// All timeline updates for every complaint, grouped client-side by complaint id.
$allUpdates = $pdo->query("SELECT complaint_id, status, remarks, created_at FROM complaint_updates ORDER BY created_at ASC")->fetchAll();

$pageTitle = "Complaints";
$activeNav = "complaints";
$pageCss = "complaints.css";
$useChartJs = true;
include "includes/header_admin.php";
include "views/complaints.html";
