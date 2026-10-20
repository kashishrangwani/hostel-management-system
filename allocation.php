<?php
require "includes/functions.php";
require "includes/db.php";
requireAdmin();

$message = "";

// Allocate a student to a free bed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allocate'])) {
    $studentId = (int) $_POST['student_id'];
    $bedId = (int) $_POST['bed_id'];
    $date = $_POST['alloc_date'] ?: date('Y-m-d');

    // Guard: student must not already occupy a bed.
    $already = $pdo->prepare("SELECT id FROM beds WHERE student_id = ?");
    $already->execute([$studentId]);
    if ($already->fetch()) {
        $message = "That student is already allocated to a bed. Vacate or change room first.";
    } else {
        $bed = $pdo->prepare("SELECT b.student_id, b.bed_label, r.room_number FROM beds b JOIN rooms r ON b.room_id = r.id WHERE b.id = ?");
        $bed->execute([$bedId]);
        $bed = $bed->fetch();

        if (!$bed || $bed['student_id'] !== null) {
            $message = "That bed is no longer available. Please choose another.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE beds SET student_id = ?, allocated_at = ? WHERE id = ? AND student_id IS NULL");
                $stmt->execute([$studentId, $date . ' 00:00:00', $bedId]);
                if ($stmt->rowCount() === 0) {
                    $message = "That bed was taken by someone else. Please choose another.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO allocation_logs (student_id, action, room_number, bed_label) VALUES (?, 'Allocated', ?, ?)");
                    $stmt->execute([$studentId, $bed['room_number'], $bed['bed_label']]);
                    $message = "Room allocated successfully.";
                }
            } catch (PDOException $e) {
                // UNIQUE on beds.student_id can still fire under race conditions.
                $message = "Could not allocate: student may already have a bed.";
            }
        }
    }
}

// Move a student from their current bed to a different free bed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_room'])) {
    $oldBedId = (int) $_POST['old_bed_id'];
    $newBedId = (int) $_POST['new_bed_id'];
    $reason = trim($_POST['reason']);

    $old = $pdo->prepare("SELECT student_id FROM beds WHERE id = ?");
    $old->execute([$oldBedId]);
    $studentId = $old->fetch()['student_id'] ?? null;

    $new = $pdo->prepare("SELECT b.student_id, b.bed_label, r.room_number FROM beds b JOIN rooms r ON b.room_id = r.id WHERE b.id = ?");
    $new->execute([$newBedId]);
    $newBed = $new->fetch();

    if (!$studentId) {
        $message = "Could not find that student's current bed.";
    } elseif (!$newBed || $newBed['student_id'] !== null) {
        $message = "The selected new bed is no longer available.";
    } else {
        $pdo->prepare("UPDATE beds SET student_id = NULL, allocated_at = NULL WHERE id = ?")->execute([$oldBedId]);
        $pdo->prepare("UPDATE beds SET student_id = ?, allocated_at = NOW() WHERE id = ?")->execute([$studentId, $newBedId]);

        $stmt = $pdo->prepare("INSERT INTO allocation_logs (student_id, action, room_number, bed_label, reason) VALUES (?, 'Changed', ?, ?, ?)");
        $stmt->execute([$studentId, $newBed['room_number'], $newBed['bed_label'], $reason]);

        $message = "Student moved to the new room.";
    }
}

// Vacate a student's current bed.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vacate'])) {
    $bedId = (int) $_POST['bed_id'];
    $reason = trim($_POST['reason']);

    $bed = $pdo->prepare("SELECT b.student_id, b.bed_label, r.room_number FROM beds b JOIN rooms r ON b.room_id = r.id WHERE b.id = ?");
    $bed->execute([$bedId]);
    $bed = $bed->fetch();

    if ($bed && $bed['student_id']) {
        $pdo->prepare("UPDATE beds SET student_id = NULL, allocated_at = NULL WHERE id = ?")->execute([$bedId]);
        $stmt = $pdo->prepare("INSERT INTO allocation_logs (student_id, action, room_number, bed_label, reason) VALUES (?, 'Vacated', ?, ?, ?)");
        $stmt->execute([$bed['student_id'], $bed['room_number'], $bed['bed_label'], $reason]);
        $message = "Room vacated.";
    }
}

// Students not currently holding a bed (candidates for the allocate form).
$unassignedStudents = $pdo->query("
    SELECT s.id, s.roll_no, u.name
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id NOT IN (SELECT student_id FROM beds WHERE student_id IS NOT NULL)
    ORDER BY u.name
")->fetchAll();

// All rooms with capacity/occupied counts, for the Available Rooms table.
$rooms = $pdo->query("
    SELECT r.id, r.room_number, r.block, r.floor, r.status,
           COUNT(b.id) AS capacity,
           SUM(CASE WHEN b.student_id IS NOT NULL THEN 1 ELSE 0 END) AS occupied
    FROM rooms r
    LEFT JOIN beds b ON b.room_id = r.id
    GROUP BY r.id
    ORDER BY r.block, r.room_number
")->fetchAll();

// Free beds only, grouped for the cascading Block -> Floor -> Room -> Bed selects.
$freeBeds = $pdo->query("
    SELECT b.id, b.bed_label, r.room_number, r.block, r.floor
    FROM beds b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.student_id IS NULL AND r.status = 'Active'
    ORDER BY r.block, r.floor, r.room_number, b.bed_label
")->fetchAll();

// Current allocations (occupied beds) with student info.
$allocations = $pdo->query("
    SELECT b.id AS bed_id, b.bed_label, r.room_number, s.id AS student_id, s.roll_no, u.name
    FROM beds b
    JOIN rooms r ON b.room_id = r.id
    JOIN students s ON b.student_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY r.room_number, b.bed_label
")->fetchAll();

$pageTitle = "Room Allocation";
$activeNav = "allocation";
$pageCss = "allocation.css";
include "includes/header_admin.php";
include "views/allocation.html";
