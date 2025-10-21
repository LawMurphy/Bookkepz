<?php
include 'config.php';
date_default_timezone_set('Asia/Manila'); 

$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);

        
        $resetLink = "http://localhost/Bookkepz/reset_password.php?token=" . urlencode($token);

        
        $subject = "Password Reset Request - Bookkepz";
        $body = "
        <div style='font-family:Poppins,Arial,sans-serif;color:#333;background:#ffffff;padding:25px;border-radius:10px;max-width:600px;margin:auto;box-shadow:0 4px 15px rgba(0,0,0,0.08);'>
            <h2 style='color:#773F1A;text-align:center;margin-bottom:20px;'>Password Reset Request</h2>
            <p>Hello,</p>
            <p>You requested to reset your password. Click the button below to create a new one:</p>
            <div style='text-align:center;margin:30px 0;'>
                <a href='$resetLink' style='background:#773F1A;color:#fff;padding:12px 25px;border-radius:8px;text-decoration:none;font-weight:bold;'>
                    Reset Password
                </a>
            </div>
            <p>This link will expire in <strong>1 hour</strong>.</p>
            <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
            <p style='font-size:14px;color:#555;'>If you didn’t request this, you can safely ignore this email.</p>
            <p style='text-align:center;color:#999;font-size:13px;margin-top:25px;'>© " . date("Y") . " Bookkepz</p>
        </div>
        ";

        $ch = curl_init("http://localhost/Bookkepz/email.php");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'email' => $email,
                'subject' => $subject,
                'message' => $body
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        header("Location: forgot_password.php?status=sent");
        exit;
    } else {
        header("Location: forgot_password.php?status=notfound");
        exit;
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sent') {
        $message = "✅ A password reset link has been sent to your email.";
    } elseif ($_GET['status'] === 'notfound') {
        $message = "❌ No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Bookkepz</title>
  <link rel="stylesheet" href="assets/css/fpass.css">
  <link rel="icon" type="img/png" href="assets/img/bookkepz_logo.png">
</head>
<body>

<div class="auth-wrapper">
  <div class="auth-card">
    <h2>Forgot Password</h2>

    <?php if ($message): ?>
      <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <label>Email Address</label>
      <input type="email" name="email" required placeholder="Enter your email">
      <button type="submit">Send Reset Link</button>
    </form>

    <a href="login" class="back-btn">← Back to Login</a>
  </div>
</div>

</body>
</html>
