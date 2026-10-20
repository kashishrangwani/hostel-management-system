<?php
require "includes/functions.php";
require "includes/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: " . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}

$pageTitle = "Login";
include "includes/header_student.php";
include "views/login.html";
include "includes/footer_student.php";
