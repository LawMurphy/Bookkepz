<?php
session_start();
require_once 'config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='error'>Invalid email format!</div>";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = "<div class='error'>Email already registered!</div>";
        } else {
            
            $insert = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, staff_group, is_active)
                VALUES (?, ?, ?, ?, 'staff', NULL, 0)
            ");
            $insert->execute([$first_name, $last_name, $email, $password_hashed]);

            
            $message = "<div class='msg success'>Account created successfully! Please wait for admin approval.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | Bookkepz</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" type="img/png" href="assets/img/bookkepz_logo.png">
  <style>
    /* ✅ Popup notification styling */
    .popup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.8);
      background: white;
      border: 2px solid #27ae60;
      color: #27ae60;
      padding: 25px 40px;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
      text-align: center;
      z-index: 999;
      opacity: 0;
      transition: all 0.3s ease;
      font-size: 1.1em;
    }

    .popup.show {
      opacity: 1;
      transform: translate(-50%, -50%) scale(1);
    }

    .error {
      color: #e74c3c;
      background: #ffeaea;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 10px;
    }

    .msg.success {
      display: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="landingpage">
      <img src="assets/img/bookkepz_logo.png" class="logo" alt="Bookkepz Logo">
    </a>

    <h1>Register</h1>
    <?= $message ?>
    
    <form method="POST">
      <label>First Name</label>
      <input type="text" name="first_name" required>

      <label>Last Name</label>
      <input type="text" name="last_name" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login">Login</a></p>
  </div>

  <div class="popup" id="popupMessage"></div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const successMsg = document.querySelector(".msg.success");
      const popup = document.getElementById("popupMessage");

      if (successMsg) {
        
        popup.textContent = "Account created successfully! Please wait for admin approval.";
        popup.classList.add("show");

        setTimeout(() => {
          popup.classList.remove("show");
          window.location.href = "login";
        }, 2500);
      }
    });
  </script>
</body>
</html>
