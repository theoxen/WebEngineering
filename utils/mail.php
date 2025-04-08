<?php
// Email functions using SendGrid instead of PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// Your SendGrid API key - replace with your actual API key from SendGrid
define('SENDGRID_API_KEY', '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');

function sendVerificationEmail($to, $username, $token)
{
    // Gets the current hostname and protocol
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    // For localhost development, append the project folder to the base URL
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $verificationLink = $baseUrl . '/pages/verify-email.php?token=' . $token;

    // Email content - keeping the same as in your original code
    $htmlContent = "
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

    // Plain text alternative
    $plainContent = "Hello $username,\n\n";
    $plainContent .= "Thank you for registering! Please verify your email address by clicking the link below:\n\n";
    $plainContent .= "$verificationLink\n\n";
    $plainContent .= "If you didn't create this account, you can ignore this email.";

    try {
        // Create a new SendGrid instance
        $sendgrid = new \SendGrid(SENDGRID_API_KEY);
        
        // Create email
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("theodosisx874@gmail.com", "WebEngineering");
        $email->setSubject("Verify Your Email Address");
        $email->addTo($to, $username);
        $email->addContent("text/plain", $plainContent);
        $email->addContent("text/html", $htmlContent);
        
        // THIS IS THE FIX - use the serialized version of the mail object
        $response = $sendgrid->client->mail()->send()->post($email->jsonSerialize());
        
        // Return true if the API call was successful (status code 2xx)
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    } catch (Exception $e) {
        error_log("Email could not be sent. SendGrid Error: " . $e->getMessage());
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

    // Email content - keeping the same as in your original code
    $htmlContent = "
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
    </html>";

    // Plain text alternative
    $plainContent = "Hello $username,\n\n";
    $plainContent .= "We received a request to reset your password. Click the link below to create a new password:\n\n";
    $plainContent .= "$resetLink\n\n";
    $plainContent .= "This link will expire in 1 hour for security reasons.\n\n";
    $plainContent .= "If you didn't request this password reset, you can safely ignore this email - your account is secure.";

    try {
        // Create a new SendGrid instance
        $sendgrid = new \SendGrid(SENDGRID_API_KEY);
        
        // Create email
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("theodosisx874@gmail.com", "WebEngineering");
        $email->setSubject("Reset Your Password");
        $email->addTo($to, $username);
        $email->addContent("text/plain", $plainContent);
        $email->addContent("text/html", $htmlContent);
        
        // Apply the same fix here
        $response = $sendgrid->client->mail()->send()->post($email->jsonSerialize());
        
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    } catch (Exception $e) {
        error_log("Email could not be sent. SendGrid Error: " . $e->getMessage());
        return false;
    }
}
?>