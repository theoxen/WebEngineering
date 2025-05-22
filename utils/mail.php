<?php

require __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail($to, $username, $token)
{
    // Build base URL
    // Determine if the connection is secure (HTTPS) or not (HTTP)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

    // Get the current hostname
    $host = $_SERVER['HTTP_HOST'];

    // Construct the base URL by combining protocol and hostname
    $baseUrl = $protocol . '://' . $host;

    // If running on a local development environment, add the project folder path
    // This handles the difference between development (localhost/WebEngineering) 
    // and production (example.com) environments
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $verificationLink = $baseUrl . '/pages/verify-email.php?token=' . $token;

    try {
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
        return $response;
    } catch (\Exception $e) {
        return false;
    }
}

function sendPasswordResetEmail($to, $username, $token)
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    $resetLink = $baseUrl . '/pages/reset-password.php?token=' . $token;

    try {
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
        return $response;
    } catch (\Exception $e) {
        return false;
    }
}

function sendCatalogNotificationEmail($to, $username, $notificationType, $catalogName, $details = '', $catalogId = null)
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;

    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $baseUrl .= '/WebEngineering';
    }

    // Generate appropriate subject and title based on notification type
    switch ($notificationType) {
        case 'new_catalog':
            $subject = "New Catalog Published: $catalogName";
            $title = "New Catalog Available";
            $message = "A new catalog has been published.";
            break;
        case 'catalog_update':
            $subject = "Catalog Updated: $catalogName";
            $title = "Catalog Update";
            $message = "The catalog has been updated with new information.";
            break;
        case 'position_change':
            $subject = "Position Change in $catalogName";
            $title = "Your Position Has Changed";
            $message = "There has been a change in your position.";
            break;
        default:
            $subject = "Catalog Notification: $catalogName";
            $title = "Catalog Notification";
            $message = "There is new information about your catalog.";
    }

    // Build catalog link
    if (!empty($catalogId)) {
        $catalogLink = $baseUrl . '/pages/category-details.php?id=' . $catalogId;
    } else {
        $catalogLink = $baseUrl . '/pages/homepage.php';
    }

    try {
        $sendgrid = new \SendGrid('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');
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom('theodosisx874@gmail.com', 'WebEngineering');
        $email->setSubject($subject);
        $email->addTo($to);

        $email->addContent("text/html", "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .content { padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .catalog-name { font-weight: bold; color: #4e73df; }
                .details { background-color: #fff; padding: 15px; border-left: 4px solid #4e73df; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='content'>
                    <h2>{$title}</h2>
                    <p>Hello {$username},</p>
                    <p>{$message}</p>
                    
                    <div class='details'>
                        <p>Catalog: <span class='catalog-name'>{$catalogName}</span></p>
                        " . ($details ? "<p>{$details}</p>" : "") . "
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='{$catalogLink}' class='button'>View Catalog</a>
                    </p>
                    
                    <p>If you wish to change your notification preferences, visit your account settings.</p>
                </div>
            </div>
        </body>
        </html>");

        $response = $sendgrid->send($email);
        return $response;
    } catch (\Exception $e) {
        return false;
    }
}
?>