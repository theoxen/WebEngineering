<?php
// Email functions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once __DIR__ . '/../libraries/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/PHPMailer/src/SMTP.php';
function sendVerificationEmail($to, $username, $token)
{
    // Gets the current hostname and protocol
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;


    echo "<h1> $host  </h1>";

    // For localhost development, append the project folder to the base URL
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $verificationLink = $baseUrl . '/pages/verify-email.php?token=' . $token;


    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // SMTP server
        $mail->SMTPAuth = true;  // Enable SMTP authentication
        $mail->Username = 'theodosisx874@gmail.com';  // SMTP username
        $mail->Password = 'ltlw jknw zdfk bysf'; // SMTP password (app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to (587 for TLS)

        // Recipients
        $mail->setFrom('theodosisx874@gmail.com', 'WebEngineering');
        $mail->addAddress($to); // Add recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; }
                .content { padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Verify Your Email Address</h2>
                </div>
                <div class='content'>
                    <p>Hello $username,</p>
                    <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='$verificationLink' class='button'>Verify Email Address</a>
                    </p>
                    <p>If you didn't create this account, you can ignore this email.</p>
                    <p>Alternatively, if the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p>$verificationLink</p>
                </div>
            </div>
        </body>
        </html>";

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendPasswordResetEmail($to, $username, $token)
{
    // Gets the current hostname and protocol
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    // For localhost development, append the project folder to the base URL
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $resetLink = $baseUrl . '/pages/reset-password.php?token=' . $token;


    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // $mail->isSMTP(); // TODO: Put the smtp settings in a config file (?)
        $mail->Host = 'smtp.gmail.com'; // SMTP server
        $mail->SMTPAuth = true;  // Enable SMTP authentication
        $mail->Username = 'theodosisx874@gmail.com';  // SMTP username
        $mail->Password = 'ltlw jknw zdfk bysf'; // SMTP password (app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to (587 for TLS)

        // Recipients
        $mail->setFrom('theodosisx874@gmail.com', 'WebEngineering');
        $mail->addAddress($to); // Add recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        $mail->Body = "
        <html>
        <head>
            <title>Reset Your Password</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; }
                .content { padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white !important; 
                        text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Reset Your Password</h2>
                </div>
                <div class='content'>
                    <p>Hello $username,</p>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <p style='text-align: center;'>
                        <a href='$resetLink' class='button'>Reset Password</a>
                    </p>
                    <p class='warning'>This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, you can safely ignore this email - your account is secure.</p>
                    <p>Alternatively, if the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p>$resetLink</p>
                </div>
            </div>
        </body>
        </html>
        ";

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>