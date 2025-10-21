<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$email   = $_POST['email'] ?? '';
$subject = $_POST['subject'] ?? 'Bookkepz Notification';
$message = $_POST['message'] ?? '';

if (!$email || !$message) {
    exit('Missing required fields.');
}

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug  = 2; // 0 = off, 2 = verbose output for debugging
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bookkepzofficial@gmail.com';
    $mail->Password   = 'byrs tfuw fgmc fixv'; // App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Optional: fix SSL verification issues on localhost
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ];

    //Recipients
    $mail->setFrom('bookkepzofficial@gmail.com', 'Bookkepz Support');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;

    $mail->send();
    echo "✅ Email sent successfully.";
} catch (Exception $e) {
    echo "❌ Mailer Error: {$mail->ErrorInfo}";
}
?>
