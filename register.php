<?php
require "includes/functions.php";
require "includes/db.php";

$errors = [];
$name = $email = $phone = $course = ""; $gender = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $course = trim($_POST['course']);
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($name === "") $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (!preg_match('/^\d{10}$/', $phone)) $errors[] = "Enter a valid 10-digit phone number.";
    if ($course === "") $errors[] = "Please select your course & year.";
    if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors[] = "Please select a gender.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'student', ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]);
            $userId = $pdo->lastInsertId();

            // Safer roll number: find the highest existing ST### and increment.
            // Falls back to ST101 if none exist. Locked inside the transaction.
            $row = $pdo->query("
                SELECT MAX(CAST(SUBSTRING(roll_no, 3) AS UNSIGNED)) AS max_n
                FROM students
                WHERE roll_no REGEXP '^ST[0-9]+$'
            ")->fetch();
            $next = ((int) ($row['max_n'] ?? 100)) + 1;
            $rollNo = "ST" . str_pad($next, 3, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO students (user_id, roll_no, course, gender) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $rollNo, $course, $gender]);
            $studentId = $pdo->lastInsertId();

            // Every new student gets a fee account (default total 0; admin can set later).
            $pdo->prepare("INSERT INTO fee_accounts (student_id, total_fee) VALUES (?, 0)")
                ->execute([$studentId]);

            $pdo->commit();

            $_SESSION['registered'] = true;
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}

$pageTitle = "Register";
include "includes/header_student.php";
include "views/register.html";
include "includes/footer_student.php";
