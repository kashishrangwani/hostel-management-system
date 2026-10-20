<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

// Create a new notice.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notice'])) {
    $stmt = $pdo->prepare("INSERT INTO notices (title, category, audience, description, publish_date, posted_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        trim($_POST['title']), $_POST['category'], $_POST['audience'],
        trim($_POST['description']), $_POST['publish_date'], $_SESSION['user_id']
    ]);
}

// Edit an existing notice.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_notice'])) {
    $stmt = $pdo->prepare("UPDATE notices SET title=?, category=?, audience=?, description=?, publish_date=? WHERE id=?");
    $stmt->execute([
        trim($_POST['title']), $_POST['category'], $_POST['audience'],
        trim($_POST['description']), $_POST['publish_date'], (int) $_POST['notice_id']
    ]);
}

// Remove a notice (POST only).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notice'])) {
    $pdo->prepare("DELETE FROM notices WHERE id = ?")->execute([(int) $_POST['notice_id']]);
    header("Location: notices.php");
    exit();
}

$today = date('Y-m-d');
$totalNotices = $pdo->query("SELECT COUNT(*) AS c FROM notices")->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notices WHERE publish_date <= ?");
$stmt->execute([$today]);
$publishedCount = $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notices WHERE publish_date > ?");
$stmt->execute([$today]);
$scheduledCount = $stmt->fetch()['c'];

$notices = $pdo->query("SELECT * FROM notices ORDER BY publish_date DESC, id DESC")->fetchAll();

$pageTitle = "Notices";
$activeNav = "notices";
$pageCss = "notices.css";
include "includes/header_admin.php";
include "views/notices.html";
