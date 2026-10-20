<?php
// Database connection settings — edit these to match your MySQL setup.
$DB_HOST = "localhost";
$DB_NAME = "hostel_management";
$DB_USER = "root";
$DB_PASS = "";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // fetch rows as associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,              // use real prepared statements
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
