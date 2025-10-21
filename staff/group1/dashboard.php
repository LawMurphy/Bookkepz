<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff' || $_SESSION['staff_group'] !== 'group1') {
    header('Location: ../../login');
    exit;
}

include('../../topbar/header.php');

$first = $_SESSION['first_name'] ?? 'Staff';
$last  = $_SESSION['last_name'] ?? '';
$initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

$welcome_message = $welcome_message ?? 'You have access to Group 1 functions only.';
?>

<main class="main-content">
  <h1>Welcome, <?= htmlspecialchars($first) ?> (Group 1)</h1>
  <p><?= htmlspecialchars($welcome_message) ?></p>
</main>

<script src="../../assets/js/dashboard.js"></script>

<?php include('../../topbar/footer.php'); ?>
