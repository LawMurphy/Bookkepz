<?php
include 'config.php';
date_default_timezone_set('Asia/Manila'); 

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';

    if (empty($token) || empty($new_password)) {
        header("Location: reset_password.php?status=missing");
        exit;
    }

    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND token_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expire = NULL WHERE id = ?");
        $update->execute([$hashed, $user['id']]);

        if ($update->errorCode() === "00000") {
            
            header("Location: reset_password.php?status=success");
            exit;
        } else {
            header("Location: reset_password.php?status=error");
            exit;
        }
    } else {
        header("Location: reset_password.php?status=invalid");
        exit;
    }
}


if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $message = "✅ Password updated successfully. <a href='login' class='back-btn'>Go to Login</a>";
            break;
        case 'invalid':
            $message = "❌ Invalid or expired token.";
            break;
        case 'missing':
            $message = "⚠️ Missing token or password.";
            break;
        case 'error':
            $message = "⚠️ Something went wrong while updating password.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | Bookkepz</title>
<link rel="stylesheet" href="assets/css/fpass.css">
<link rel="icon" type="img/png" href="assets/img/bookkepz_logo.png">
</head>
<body>
<div class="reset-container">
  <div class="reset-box">
    <h2>Set New Password</h2>

    <?php if (!empty($message)): ?>
      <div class="message"><?= $message ?></div>
    <?php elseif (!empty($token)): ?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label>New Password</label>
        <input type="password" name="password" placeholder="Enter new password" required>
        <button type="submit">Update Password</button>
      </form>
    <?php else: ?>
      <div class="message error">❌ No token provided.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
