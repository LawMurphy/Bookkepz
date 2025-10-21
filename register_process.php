<?php

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register');
    exit;
}

$first = trim($_POST['first_name'] ?? '');
$last = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password_confirm'] ?? '';

if (!$first || !$last || !$email || !$pw) {
    header('Location: register?msg=' . urlencode('All fields are required.'));
    exit;
}

if ($pw !== $pw2) {
    header('Location: register?msg=' . urlencode('Passwords do not match.'));
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: register?msg=' . urlencode('Email is already registered.'));
    exit;
}

$hash = password_hash($pw, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (first_name, last_name, email, password, role, is_active)
    VALUES (?, ?, ?, ?, 'staff', 1)
");
$stmt->execute([$first, $last, $email, $hash]);

header('Location: login?msg=' . urlencode('Registration successful! You can now log in.'));
exit;
