<?php
session_start();
require_once '../config.php';

// 🔒 Restrict access to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../login');
  exit;
}

// Fetch admin info
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$first = $user['first_name'] ?? 'A';
$last  = $user['last_name'] ?? '';
$initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE (staff_group IS NULL OR staff_group = '') AND role = 'staff'")->fetchColumn();
$group1 = $pdo->query("SELECT COUNT(*) FROM users WHERE staff_group = 'group1'")->fetchColumn();
$group2 = $pdo->query("SELECT COUNT(*) FROM users WHERE staff_group = 'group2'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Bookkepz</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/manage_users.css">
<link rel="icon" type="img/png" href="../assets/img/bookkepz_logo.png">
</head>
<body>

<?php include '../topbar/header.php'; ?>

<!-- 🔹 Notification Popup -->
<div id="popup" class="popup"></div>

<main class="main-content">
  <h1>Manage Users</h1>

  <section class="stats">
    <div class="card"><h3>Total Users</h3><p><?= $totalUsers ?></p></div>
    <div class="card"><h3>Pending Staff</h3><p><?= $pending ?></p></div>
    <div class="card"><h3>Staff 1 Members</h3><p><?= $group1 ?></p></div>
    <div class="card"><h3>Staff 2 Members</h3><p><?= $group2 ?></p></div>
  </section>

  <section class="user-table">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Group</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <?php
          $status = (empty($u['staff_group']) && $u['role'] === 'staff') ? 'Pending' : 'Active';
          $rowClass = ($status === 'Pending') ? 'pending' : '';
        ?>
        <tr class="<?= $rowClass ?>">
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= $u['role'] ?: '—' ?></td>
          <td><?= $u['staff_group'] ?: '—' ?></td>
          <td><?= $status ?></td>
          <td>
            <?php if ($u['role'] === 'staff'): ?>
            <form method="POST" action="update_user.php" class="user-form" data-current-group="<?= $u['staff_group'] ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

              <select name="group" required>
                <option value="">Select Group</option>
                <option value="group1" <?= $u['staff_group'] === 'group1' ? 'selected' : '' ?>>Group 1</option>
                <option value="group2" <?= $u['staff_group'] === 'group2' ? 'selected' : '' ?>>Group 2</option>
              </select>

              <button type="submit" class="action-btn update-btn"
                <?= empty($u['staff_group']) ? '' : '' ?>>Update</button>

              <button type="button"
                class="action-btn remove-btn"
                data-id="<?= $u['id'] ?>"
                <?= empty($u['staff_group']) ? 'disabled' : '' ?>>Remove Group</button>
            </form>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<script src="../assets/js/manage_users.js"></script>
<?php include '../topbar/footer.php'; ?>
</body>
</html>

