<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Admin'); ?> | Hostel Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (!empty($useChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <link rel="stylesheet" href="css/<?php echo h($pageCss); ?>">
</head>
<body>

    <div class="dashboard-wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar<?php echo ($activeNav ?? '') === 'reports' ? ' no-print' : ''; ?>">
            <div class="sidebar-header">
                <h2><i class="fa-solid fa-building-user"></i> Hostel Admin</h2>
            </div>

            <nav class="nav-links">
                <?php
                $navItems = [
                    'dashboard'   => ['admin_dashboard.php', 'fa-house', 'Dashboard'],
                    'students'    => ['students.php', 'fa-user-graduate', 'Students'],
                    'allocation'  => ['allocation.php', 'fa-key', 'Room Allocation'],
                    'fees'        => ['fees.php', 'fa-file-invoice-dollar', 'Fees'],
                    'complaints'  => ['complaints.php', 'fa-circle-exclamation', 'Complaints'],
                    'leave'       => ['leave.php', 'fa-calendar-minus', 'Leave'],
                    'notices'     => ['notices.php', 'fa-bullhorn', 'Notices'],
                    'reports'     => ['reports.php', 'fa-chart-pie', 'Reports'],
                ];
                foreach ($navItems as $key => [$href, $icon, $label]):
                    $active = ($activeNav ?? '') === $key ? ' class="active"' : '';
                ?>
                <a href="<?php echo $href; ?>"<?php echo $active; ?>><i class="fa-solid <?php echo $icon; ?>"></i> <span><?php echo $label; ?></span></a>
                <?php endforeach; ?>
            </nav>

            <div class="logout-wrapper">
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>
