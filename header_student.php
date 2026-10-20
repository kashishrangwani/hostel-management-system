<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>Hostel Management System</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- Navigation Bar -->
  <header class="header" id="mainHeader">
    <div class="container navbar">
      <a href="index.php" class="logo" id="siteLogo">
        <div class="logo-badge">H</div>
        <div class="logo-text">Hostel<span>Hub</span></div>
      </a>

      <button class="hamburger" id="hamburgerBtn" aria-label="Toggle navigation menu">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link <?php echo ($activeNav ?? '') === 'home' ? 'active' : ''; ?>">Home</a>
        <?php if (isLoggedIn() && !isAdmin()): ?>
          <a href="dashboard.php" class="nav-link <?php echo ($activeNav ?? '') === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
          <a href="forms.php" class="nav-link <?php echo ($activeNav ?? '') === 'forms' ? 'active' : ''; ?>">Forms</a>
          <a href="table.php" class="nav-link <?php echo ($activeNav ?? '') === 'table' ? 'active' : ''; ?>">Complaints</a>
          <div class="nav-auth-buttons">
            <a href="logout.php" class="btn btn-secondary btn-sm logout-btn">Logout</a>
          </div>
        <?php else: ?>
          <a href="index.php#contactSection" class="nav-link">Contact</a>
          <div class="nav-auth-buttons">
            <a href="login.php" class="btn btn-outline btn-sm">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
          </div>
        <?php endif; ?>
      </nav>
    </div>
  </header>
