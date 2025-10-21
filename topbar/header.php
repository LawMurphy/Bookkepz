<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login');
    exit;
}

$currentPath = $_SERVER['PHP_SELF'];
if (strpos($currentPath, '/admin/') !== false) {
    $basePath = '../';
} elseif (strpos($currentPath, '/staff/group1/') !== false || strpos($currentPath, '/staff/group2/') !== false) {
    $basePath = '../../';
} else {
    $basePath = '../';
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$staff_group = $_SESSION['staff_group'] ?? '';

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$first = $user['first_name'] ?? $_SESSION['first_name'] ?? '';
$last = $user['last_name'] ?? $_SESSION['last_name'] ?? '';
$initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$pageTitle = "Bookkepz";
if ($role === 'admin') {
    $pageTitle = "Admin | Bookkepz";
} elseif ($role === 'staff') {
    $pageTitle = ($staff_group === 'group1') ? "Group 1 Dashboard | Bookkepz" : "Group 2 Dashboard | Bookkepz";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/admin.css">
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/navbar.css">
  <link rel="icon" type="image/png" href="../assets/img/bookkepz_logo.png">
</head>
<body>

<header class="topbar">
  <div class="topbar-left">
    <a href="<?= $basePath ?><?= ($role === 'admin') ? 'admin/dashboard' : (($staff_group === 'group1') ? 'staff/group1/dashboard' : 'staff/group2/dashboard') ?>" class="logo-link">
      <img src="<?= $basePath ?>assets/img/bookkepz_logo.png" class="logo" alt="Bookkepz Logo">
    </a>
    <h2 class="brand-name">Bookkepz</h2>
  </div>

  <nav class="nav-links">
    <ul>
      <li>
        <a href="<?= $basePath ?><?= ($role === 'admin') ? 'admin/dashboard' : (($staff_group === 'group1') ? 'staff/group1/dashboard' : 'staff/group2/dashboard') ?>"
           class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      </li>

      <?php if ($role === 'admin'): ?>
        
        <li>
          <a href="<?= $basePath ?>invoices/invoice">Invoice</a>
        </li>

        <li class="has-dropdown">
          <a href="#">Reports</a>
          <ul class="dropdown-menu">
            <li><a href="<?= $basePath ?>admin/reports">Monthly Reports</a></li>
            <li><a href="<?= $basePath ?>admin/financial_summary">Financial Summary</a></li>
            <li><a href="<?= $basePath ?>admin/audit">Audit Logs</a></li>
          </ul>
        </li>

        <li class="has-dropdown">
          <a href="#">Payroll</a>
          <ul class="dropdown-menu">
            <li><a href="<?= $basePath ?>admin/payroll">Employee Payroll</a></li>
            <li><a href="<?= $basePath ?>admin/benefits">Benefits</a></li>
            <li><a href="<?= $basePath ?>admin/deductions">Deductions</a></li>
          </ul>
        </li>

        <li class="has-dropdown">
          <a href="#">Contact</a>
          <ul class="dropdown-menu">
            <li><a href="<?= $basePath ?>contact_form">Contact Form</a></li>
            <li><a href="<?= $basePath ?>support">Support</a></li>
            <li><a href="<?= $basePath ?>contact/aboutus">About Us</a></li>
          </ul>
        </li>

      <?php elseif ($role === 'staff' && $staff_group === 'group1'): ?>
        
        <li class="has-dropdown">
          <a href="#">Payroll</a>
          <ul class="dropdown-menu">
            <li><a href="<?= $basePath ?>staff/group1/payroll_records">Payroll Records</a></li>
            <li><a href="<?= $basePath ?>staff/group1/benefits">Benefits</a></li>
            <li><a href="<?= $basePath ?>staff/group1/deductions">Deductions</a></li>
          </ul>
        </li>
        <li><a href="<?= $basePath ?>staff/group1/contact">Contact</a></li>

      <?php elseif ($role === 'staff' && $staff_group === 'group2'): ?>
        
        <li class="has-dropdown">
          <a href="#">Reports</a>
          <ul class="dropdown-menu">
            <li><a href="<?= $basePath ?>staff/group2/reports">Generate Reports</a></li>
            <li><a href="<?= $basePath ?>staff/group2/report_history">Report History</a></li>
          </ul>
        </li>
        <li><a href="<?= $basePath ?>staff/group2/contact">Contact</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="topbar-right">
    <div class="datetime">
      <span id="current-date"></span>
      <span id="current-time"></span>
    </div>

    <div class="profile-wrapper">
      <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
      <div class="profile-dropdown">
        <?php if ($role === 'admin'): ?>
          <a href="<?= $basePath ?>admin/manage_users">Manage Users</a>
        <?php endif; ?>
        <a href="#">Settings</a>
        <a href="<?= $basePath ?>logout">Logout</a>
      </div>
    </div>
  </div>
</header>

<script>
function updateDate() {
  const now = new Date();
  document.getElementById("current-date").textContent =
    now.toLocaleDateString("en-US", {
      weekday: "long",
      month: "long",
      day: "numeric",
      year: "numeric"
    });
}

function updateTime() {
  const now = new Date();
  document.getElementById("current-time").textContent =
    now.toLocaleTimeString("en-US", { hour12: true });
}


updateDate();
updateTime();
setInterval(updateTime, 1000);
</script>


<main class="main-content">
