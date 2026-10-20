<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

$message = "";

// Set (or update) a student's total fee amount.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_fee'])) {
    $studentId = (int) $_POST['student_id'];
    $totalFee = (float) $_POST['total_fee'];

    $exists = $pdo->prepare("SELECT id FROM fee_accounts WHERE student_id = ?");
    $exists->execute([$studentId]);
    if ($exists->fetch()) {
        $pdo->prepare("UPDATE fee_accounts SET total_fee = ? WHERE student_id = ?")->execute([$totalFee, $studentId]);
    } else {
        $pdo->prepare("INSERT INTO fee_accounts (student_id, total_fee) VALUES (?, ?)")->execute([$studentId, $totalFee]);
    }
    $message = "Fee amount updated.";
}

// Record a payment.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $studentId = (int) $_POST['student_id'];
    $amount = (float) $_POST['amount'];
    $method = $_POST['method'];
    $txnId = trim($_POST['transaction_id']);
    $paidOn = $_POST['paid_on'] ?: date('Y-m-d');

    if ($amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, amount, method, transaction_id, paid_on) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$studentId, $amount, $method, $txnId, $paidOn]);
        $message = "Payment recorded.";
    }
}

// Every student, with total fee, amount paid, room, for the main table.
$fees = $pdo->query("
    SELECT s.id AS student_id, u.name, r.room_number,
           COALESCE(fa.total_fee, 0) AS total_fee,
           COALESCE((SELECT SUM(amount) FROM fee_payments WHERE student_id = s.id), 0) AS paid
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN beds b ON b.student_id = s.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN fee_accounts fa ON fa.student_id = s.id
    ORDER BY u.name
")->fetchAll();

$fullyPaid = $partiallyPaid = $notPaid = 0;
foreach ($fees as $f) {
    $due = $f['total_fee'] - $f['paid'];
    if ($f['total_fee'] > 0 && $due <= 0) $fullyPaid++;
    elseif ($f['paid'] > 0 && $due > 0) $partiallyPaid++;
    else $notPaid++;
}

// Payment history for every student, grouped client-side by student_id in JS.
$allPayments = $pdo->query("
    SELECT student_id, amount, method, transaction_id, paid_on
    FROM fee_payments
    ORDER BY paid_on DESC
")->fetchAll();

$pageTitle = "Fees";
$activeNav = "fees";
$pageCss = "fees.css";
include "includes/header_admin.php";
include "views/fees.html";
