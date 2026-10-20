# HostelHub — Hostel Management System

A full-stack **Hostel Management System** built with **PHP (PDO)** and **MySQL**.  
It provides separate portals for **students** and **admins**, with real database-backed operations for room allocation, fees, complaints, leave requests, and notices.

Originally rebuilt from a static HTML/CSS frontend into a working PHP + MySQL application.

---

## Features

### Student Portal
- Home / landing page with hostel information
- Secure login & registration (password hashing)
- Personal dashboard (room details, roommates, recent notices)
- Submit maintenance complaints
- Apply for leave / gate pass
- View complaint history & status

### Admin Portal
- Dashboard with live statistics and charts
- Student management (add / edit / delete)
- Room & bed allocation (allocate, change, vacate) with audit log
- Fee management (set total fee, record payments, due calculation)
- Complaint management with status timeline
- Leave request overview (status computed from dates)
- Notices (create, edit, schedule, delete)
- Reports module

### Technical Highlights
- PDO prepared statements (no raw SQL concatenation for user input)
- Password hashing with `password_hash()` / `password_verify()`
- Role-based access control (student vs admin)
- XSS protection via output escaping helper
- Clean separation of PHP logic and HTML views
- Responsive UI with original design preserved

---

## Tech Stack

| Layer        | Technology                          |
|--------------|-------------------------------------|
| Backend      | PHP 8+ (PDO)                        |
| Database     | MySQL / MariaDB                     |
| Frontend     | HTML5, CSS3, Vanilla JavaScript     |
| Charts       | Chart.js (admin dashboard)          |
| Server       | Apache (XAMPP / WAMP / MAMP / LAMP) |

---

## Requirements

- PHP 8.0 or higher (with PDO MySQL extension — enabled by default in XAMPP)
- MySQL 5.7+ / MariaDB 10.3+
- Apache web server
- Modern browser (Chrome, Firefox, Edge, Safari)

---

## Installation & Setup

### 1. Download / Clone
```bash
git clone https://github.com/kashishrangwani/Hostel_management_system.git
```
Or extract the project ZIP into your web root.

### 2. Place in Web Root
Copy the project folder into your server’s document root, for example:

| Environment     | Typical path                              |
|-----------------|-------------------------------------------|
| XAMPP (Windows) | `C:\xampp\htdocs\hms2`                    |
| WAMP            | `C:\wamp64\www\hms2`                      |
| MAMP (Mac)      | `/Applications/MAMP/htdocs/hms2`          |
| Linux           | `/var/www/html/hms2` or `/opt/lampp/htdocs/hms2` |

### 3. Start Services
Start **Apache** and **MySQL** from the XAMPP / WAMP control panel.

### 4. Create the Database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Go to **Import**
3. Select `db/schema.sql`
4. Click **Go**

This creates the database `hostel_management`, all tables, foreign keys, and seed data.

Alternatively from the command line:
```bash
mysql -u root -p < db/schema.sql
```

### 5. Configure Database Credentials (if needed)
By default the app expects XAMPP’s standard settings:

```php
// includes/db.php
$DB_HOST = "localhost";
$DB_NAME = "hostel_management";
$DB_USER = "root";
$DB_PASS = "";          // empty password on default XAMPP
```

Edit these values if your MySQL username or password is different.

### 6. Open the Application
```
http://localhost/hms2/
```

---

## Demo Logins

| Role         | Email                | Password     |
|--------------|----------------------|--------------|
| **Admin**    | `admin@hostel.com`   | `admin123`   |
| **Student**  | `mason@hostel.com`   | `student123` |
| **Student**  | `caleb@hostel.com`   | `student123` |

You can also register a new student account from the Register page.

---

## Project Structure

```
hms2/
├── css/                      # Stylesheets (one per major page + global)
│   ├── style.css             # Student portal
│   ├── admin_dashboard.css
│   ├── students.css
│   ├── allocation.css
│   ├── fees.css
│   ├── complaints.css
│   ├── leave.css
│   ├── notices.css
│   └── reports.css
│
├── js/                       # Client-side scripts
│   ├── script.js             # Shared (password toggle, nav, etc.)
│   ├── admin_dashboard.js
│   ├── students.js
│   ├── allocation.js
│   ├── fees.js
│   ├── complaints.js
│   ├── leave.js
│   ├── notices.js
│   └── reports.js
│
├── includes/                 # Shared PHP
│   ├── db.php                # PDO connection
│   ├── functions.php         # Auth helpers, formatting, status helpers
│   ├── header_student.php
│   ├── footer_student.php
│   ├── header_admin.php
│   └── footer_admin.php
│
├── views/                    # HTML presentation layer
│   ├── index.html
│   ├── login.html
│   ├── register.html
│   ├── dashboard.html
│   ├── forms.html
│   ├── table.html
│   ├── admin_dashboard.html
│   ├── students.html
│   ├── allocation.html
│   ├── fees.html
│   ├── complaints.html
│   ├── leave.html
│   ├── notices.html
│   └── reports.html
│
├── db/
│   └── schema.sql            # Full schema + seed data
│
├── index.php                 # Student home
├── login.php
├── register.php
├── dashboard.php
├── forms.php                 # Complaint + leave submission
├── table.php                 # Student complaint history
├── logout.php
│
├── admin_dashboard.php
├── students.php
├── allocation.php
├── fees.php
├── complaints.php
├── leave.php
├── notices.php
├── reports.php
│
└── README.md
```

### Architecture Note
Each page follows a simple pattern:

1. **Logic file** (`*.php`) — authentication, validation, database queries, form handling  
2. **View file** (`views/*.html`) — original HTML markup + embedded PHP for dynamic output  

This keeps business logic separate from presentation while preserving the original design.

---

## Database Overview

| Table               | Purpose |
|---------------------|---------|
| `users`             | Login accounts (students + admin) |
| `students`          | Student profile linked to a user |
| `rooms`             | Physical rooms (block / floor) |
| `beds`              | Allocatable unit (one student per bed) |
| `allocation_logs`   | Audit trail of allocate / change / vacate |
| `fee_accounts`      | Total fee amount per student |
| `fee_payments`      | Payment history |
| `complaints`        | Maintenance / service complaints |
| `complaint_updates` | Status timeline for each complaint |
| `leave_requests`    | Leave / gate pass applications |
| `notices`           | Announcements (supports scheduled publish) |

**Key design decisions:**
- A student can occupy only **one bed** (`UNIQUE` on `beds.student_id`)
- Each room’s beds are unique (`UNIQUE (room_id, bed_label)`)
- Leave status (Upcoming / On Leave / Returned) is **computed from dates**, not stored
- Fee due is calculated live: `total_fee − SUM(payments)`
- New students automatically receive a `fee_accounts` row

---

## Modules Summary

| Module       | Student side              | Admin side                        |
|--------------|---------------------------|-----------------------------------|
| Auth         | Login, Register, Logout   | Same login (role decides portal)  |
| Dashboard    | Room, roommates, notices  | Stats + Chart.js graphs           |
| Students     | —                         | CRUD + temporary password on add  |
| Allocation   | View own room             | Allocate / Change / Vacate + logs |
| Fees         | —                         | Set fee, record payment, view due |
| Complaints   | Submit + history          | Update status + timeline          |
| Leave        | Apply                     | List + computed status            |
| Notices      | Read published            | Create / Edit / Schedule / Delete |
| Reports      | —                         | Filterable reports + print        |

---

## Security Practices

- **Prepared statements** for all user-supplied data
- **Password hashing** with PHP’s `password_hash()` (bcrypt)
- **Role checks** on every protected page (`requireStudent()` / `requireAdmin()`)
- **Output escaping** via `h()` helper (`htmlspecialchars`)
- **POST-only deletes** for students and notices (with confirmation)
- **Session-based authentication**

> Note: This is an academic / demonstration project. For production you would add CSRF tokens, rate limiting, HTTPS enforcement, and a proper password-reset flow.

---

| Page              | Description              |
|-------------------|--------------------------|
| Student Home      | Landing page             |
| Student Dashboard | Room & notices overview  |
| Admin Dashboard   | Stats and charts         |
| Room Allocation   | Allocate / change / vacate |
| Fees              | Payment tracking         |
| Complaints        | Status management        |

---

## Known Limitations (by design)

- Mess menu on the student dashboard is **static** (no mess-management module in the schema)
- “Export PDF” uses the browser’s **Print → Save as PDF** (no server-side PDF library)
- No email notifications or password-reset flow
- No file uploads (complaint photos, etc.)

These were intentional scope decisions for a college project.

---

## Troubleshooting

| Problem                        | Solution |
|--------------------------------|----------|
| Blank page / 500 error         | Check Apache error log; ensure PHP PDO MySQL extension is enabled |
| Database connection failed     | Verify credentials in `includes/db.php` and that MySQL is running |
| Tables not found               | Re-import `db/schema.sql` (drop old database first if needed) |
| CSS / JS not loading           | Confirm the project folder name matches the URL path |
| Login not working              | Use the seeded accounts above; ensure `schema.sql` was fully imported |

---

## Author

Built as an academic project (MCA) demonstrating a complete PHP + MySQL hostel management system with a modern UI.

---

## License

This project is intended for educational purposes.  
You are free to use and modify it for learning, demos, and college submissions.
