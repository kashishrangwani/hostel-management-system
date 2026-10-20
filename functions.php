<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (isAdmin()) {
        header("Location: admin_dashboard.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Escapes text before printing it into HTML.
function h($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Formats a rupee amount consistently across the app.
function money($amount) {
    return '₹ ' . number_format((float) $amount, 2);
}

// Formats a date consistently across the app (e.g. "29 Aug 2026").
function niceDate($date) {
    return $date ? date("d M Y", strtotime($date)) : '-';
}

// Works out a leave request's effective status from its dates.
function leaveStatus($fromDate, $toDate, $cancelled) {
    if ($cancelled) return 'Cancelled';
    $today = date('Y-m-d');
    if ($today < $fromDate) return 'Upcoming';
    if ($today > $toDate) return 'Returned';
    return 'On Leave';
}

// Works out a notice's effective status from its publish date.
function noticeStatus($publishDate) {
    return $publishDate > date('Y-m-d') ? 'Scheduled' : 'Published';
}

// Turns "In Progress" into "in-progress" for CSS class / filter matching.
function slug($text) {
    return strtolower(str_replace(' ', '-', $text));
}

// The student-portal stylesheet (style.css) only defines 3 badge colors
// (pending / progress / resolved), so map every complaint status onto them.
function studentBadgeClass($status) {
    return match ($status) {
        'In Progress' => 'badge-progress',
        'Resolved', 'Closed' => 'badge-resolved',
        default => 'badge-pending', // New
    };
}
