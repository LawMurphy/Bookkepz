<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header('Location: login?error=' . urlencode('Please provide email & password.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: login?error=' . urlencode('Invalid credentials.'));
    exit;
}

if (!$user['is_active']) {
    header('Location: login?error=' . urlencode('Your account is not active. Wait for admin approval.'));
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['staff_group'] = $user['staff_group'];


if ($user['role'] === 'admin') {
    header('Location: admin/dashboard');
    exit;
}

if ($user['role'] === 'staff') {
    if (empty($user['staff_group'])) {
        header('Location: login?error=' . urlencode('Wait for the admin to give you permission.'));
        exit;
    }

    if ($user['staff_group'] === 'group1') {
        header('Location: staff/group1/dashboard');
        exit;
    } elseif ($user['staff_group'] === 'group2') {
        header('Location: staff/group2/dashboard');
        exit;
    }
}

header('Location: login?error=' . urlencode('Access denied.'));
exit;
?>
