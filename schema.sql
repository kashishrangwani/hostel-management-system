-- ============================================================
-- Hostel Management System - Database Schema (PDO / MySQL)
-- Import via phpMyAdmin, or: mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS hostel_management;
USE hostel_management;

-- ------------------------------------------------------------
-- Users: students and admin both log in through this table.
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Student profile, one row per student user.
-- ------------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    roll_no VARCHAR(30) NOT NULL UNIQUE,
    course VARCHAR(100),
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    dob DATE DEFAULT NULL,
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(15),
    address VARCHAR(255),
    status ENUM('Active', 'Inactive', 'Graduated', 'Left') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Rooms: block/floor grouping. Beds are the actual allocatable unit.
-- ------------------------------------------------------------
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    block VARCHAR(20) NOT NULL,
    floor VARCHAR(10) NOT NULL,
    status ENUM('Active', 'Maintenance') NOT NULL DEFAULT 'Active'
);

-- ------------------------------------------------------------
-- Beds: one row per physical bed. student_id is NULL when free.
-- A student can only occupy one bed (enforced by UNIQUE).
-- ------------------------------------------------------------
CREATE TABLE beds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    bed_label VARCHAR(20) NOT NULL,
    student_id INT DEFAULT NULL UNIQUE,
    allocated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_room_bed (room_id, bed_label),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- A simple audit trail of allocate/change/vacate actions,
-- so the admin's "reason for change" actually gets recorded.
-- ------------------------------------------------------------
CREATE TABLE allocation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    action ENUM('Allocated', 'Changed', 'Vacated') NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    bed_label VARCHAR(20) NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Fees: a total-due amount per student, plus a payment log.
-- Paid = SUM(fee_payments.amount); Due = total_fee - Paid.
-- ------------------------------------------------------------
CREATE TABLE fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    total_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('Cash', 'UPI', 'Bank Transfer', 'Card') NOT NULL DEFAULT 'Cash',
    transaction_id VARCHAR(50),
    paid_on DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Complaints, with a status timeline (complaint_updates).
-- ------------------------------------------------------------
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM('Maintenance', 'Water', 'Electricity', 'Cleanliness', 'Internet') NOT NULL DEFAULT 'Maintenance',
    description TEXT NOT NULL,
    status ENUM('New', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE complaint_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Leave requests. Status (Upcoming/On Leave/Returned) is computed
-- from the dates at display time; `cancelled` is the only manual flag.
-- ------------------------------------------------------------
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    leave_type ENUM('Personal', 'Medical', 'Family', 'Emergency', 'Other') NOT NULL DEFAULT 'Personal',
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    reason TEXT NOT NULL,
    cancelled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Notices. A future publish_date means "Scheduled" until that date.
-- ------------------------------------------------------------
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category ENUM('General', 'Fees', 'Hostel Rules', 'Mess', 'Maintenance', 'Events', 'Emergency') NOT NULL DEFAULT 'General',
    audience VARCHAR(50) NOT NULL DEFAULT 'All Students',
    description TEXT NOT NULL,
    publish_date DATE NOT NULL,
    posted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- Seed data
-- ============================================================

-- Admin login -> admin@hostel.com / admin123
INSERT INTO users (name, email, password, role) VALUES
('Hostel Admin', 'admin@hostel.com', '$2y$10$T9NgwQ0uISfYihSguxj0nu3IyXJiSYeeJHeh0ELot.amOGhHvMOky', 'admin');

-- Sample rooms
INSERT INTO rooms (room_number, block, floor, status) VALUES
('A-101', 'A', '1', 'Active'),
('A-102', 'A', '1', 'Active'),
('A-201', 'A', '2', 'Active'),
('B-105', 'B', '1', 'Active'),
('C-301', 'C', '3', 'Maintenance');

-- Beds: A-101 (4), A-102 (4), A-201 (3), B-105 (2), C-301 (2, under maintenance)
INSERT INTO beds (room_id, bed_label) VALUES
(1, 'Bed 1'), (1, 'Bed 2'), (1, 'Bed 3'), (1, 'Bed 4'),
(2, 'Bed 1'), (2, 'Bed 2'), (2, 'Bed 3'), (2, 'Bed 4'),
(3, 'Bed 1'), (3, 'Bed 2'), (3, 'Bed 3'),
(4, 'Bed 1'), (4, 'Bed 2'),
(5, 'Bed 1'), (5, 'Bed 2');

-- Two sample students (password for both: student123)
INSERT INTO users (name, email, password, role, phone) VALUES
('Mason Reed', 'mason@hostel.com', '$2y$10$wZRCJ.d6c0SJaz68C7NquesVZ39So7ASuFhNNEKx7py462gAHOu32', 'student', '9876543210'),
('Caleb Frost', 'caleb@hostel.com', '$2y$10$wZRCJ.d6c0SJaz68C7NquesVZ39So7ASuFhNNEKx7py462gAHOu32', 'student', '9876543211');

INSERT INTO students (user_id, roll_no, course, gender, status) VALUES
(2, 'ST101', 'BCA - 2nd Year', 'Male', 'Active'),
(3, 'ST102', 'MCA - 1st Year', 'Male', 'Active');

-- Allocate both students to A-101
UPDATE beds SET student_id = 1, allocated_at = NOW() WHERE id = 1;
UPDATE beds SET student_id = 2, allocated_at = NOW() WHERE id = 2;

-- Fee accounts + one payment each
INSERT INTO fee_accounts (student_id, total_fee) VALUES (1, 50000.00), (2, 50000.00);
INSERT INTO fee_payments (student_id, amount, method, transaction_id, paid_on) VALUES
(1, 50000.00, 'UPI', 'UPI9876543210', '2026-08-15'),
(2, 25000.00, 'Bank Transfer', 'HDFC11223344', '2026-09-01');

-- Sample complaint with a timeline
INSERT INTO complaints (student_id, title, category, description, status) VALUES
(1, 'Ceiling fan not working properly', 'Electricity',
 'The ceiling fan in the room is making a loud grinding noise and running very slowly. It has been happening for two days.',
 'In Progress');
INSERT INTO complaint_updates (complaint_id, status, remarks) VALUES
(1, 'New', 'Complaint submitted by student.'),
(1, 'In Progress', 'Maintenance staff dispatched to check the fan motor.');

-- Sample leave request
INSERT INTO leave_requests (student_id, leave_type, from_date, to_date, reason) VALUES
(2, 'Family', '2026-09-25', '2026-09-27', 'Visiting family for a hometown festival.');

-- Sample notices
INSERT INTO notices (title, category, audience, description, publish_date, posted_by) VALUES
('Fee Payment Deadline', 'Fees', 'All Students',
 'All hostel fees for the upcoming semester must be paid in full before 5th October 2026. Late payments will incur a daily penalty fee.',
 '2026-09-01', 1),
('Water Supply Interruption', 'Maintenance', 'Block A',
 'Water supply in Block A will be interrupted for scheduled maintenance work from 10 AM to 5 PM.',
 '2026-09-18', 1);
