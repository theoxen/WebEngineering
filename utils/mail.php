<?php
// filepath: c:\xampp\htdocs\WebEngineering\utils\mail.php
// Email functions

// Show all PHP errors in browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log that script has started
@file_put_contents(__DIR__ . '/../debug.log', "=== mail.php started ===\n", FILE_APPEND);

// Check for autoload file
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    file_put_contents(__DIR__ . '/../debug.log', "ERROR: Autoload file not found at: $autoloadPath\n", FILE_APPEND);
    die("ERROR: Composer autoload not found at: $autoloadPath");
}

require $autoloadPath;
file_put_contents(__DIR__ . '/../debug.log', "Autoload loaded successfully\n", FILE_APPEND);

function sendVerificationEmail($to, $username, $token)
{
    file_put_contents(__DIR__ . '/../debug.log', "sendVerificationEmail() called\n", FILE_APPEND);

    // Build base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $verificationLink = $baseUrl . '/pages/verify-email.php?token=' . $token;

    try {
        file_put_contents(__DIR__ . '/../debug.log', "Initializing SendGrid\n", FILE_APPEND);

        $sendgrid = new \SendGrid('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');
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom('theodosisx874@gmail.com', 'WebEngineering');
        $email->setSubject('Verify Your Email Address');
        $email->addTo($to);

        $email->addContent("text/html", "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .content { padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='content'>
                    <p>Hello {$username},</p>
                    <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationLink}' class='button'>Verify Email Address</a>
                    </p>
                    <p>If you didn't create this account, you can ignore this email.</p>
                    <p>Alternatively, copy and paste this link into your browser:</p>
                    <p>{$verificationLink}</p>
                </div>
            </div>
        </body>
        </html>");

        $response = $sendgrid->send($email);
        file_put_contents(__DIR__ . '/../debug.log', "Email sent. Status code: " . $response->statusCode() . "\n", FILE_APPEND);
        return $response;
    } catch (\Exception $e) {
        file_put_contents(__DIR__ . '/../debug.log', "Email Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

function sendPasswordResetEmail($to, $username, $token)
{
    file_put_contents(__DIR__ . '/../debug.log', "sendPasswordResetEmail() called\n", FILE_APPEND);

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $resetLink = $baseUrl . '/pages/reset-password.php?token=' . $token;

    try {
        file_put_contents(__DIR__ . '/../debug.log', "Initializing SendGrid for reset\n", FILE_APPEND);

        $sendgrid = new \SendGrid('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');
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom('theodosisx874@gmail.com', 'WebEngineering');
        $email->setSubject('Reset Your Password');
        $email->addTo($to);

        $email->addContent("text/html", "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .content { padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='content'>
                    <h2>Reset Your Password</h2>
                    <p>Hello {$username},</p>
                    <p>We received a request to reset your password. Click the button below:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Reset Password</a>
                    </p>
                    <p class='warning'>This link will expire in 1 hour.</p>
                    <p>If you didn’t request this, just ignore this email.</p>
                    <p>{$resetLink}</p>
                </div>
            </div>
        </body>
        </html>");

        $response = $sendgrid->send($email);
        file_put_contents(__DIR__ . '/../debug.log', "Reset email sent. Status code: " . $response->statusCode() . "\n", FILE_APPEND);
        return $response;
    } catch (\Exception $e) {
        file_put_contents(__DIR__ . '/../debug.log', "Reset Email Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}
?>
