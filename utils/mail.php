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
        $email->setSubject('Επαλήθευση Διεύθυνσης Email');
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
                    <p>Γεια σας {$username},</p>
                    <p>Ευχαριστούμε για την εγγραφή σας! Παρακαλούμε επαληθεύστε τη διεύθυνση email σας κάνοντας κλικ στο παρακάτω κουμπί:</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationLink}' class='button'>Επαλήθευση Διεύθυνσης Email</a>
                    </p>
                    <p>Αν δεν δημιουργήσατε εσείς αυτόν τον λογαριασμό, μπορείτε να αγνοήσετε αυτό το email.</p>
                    <p>Εναλλακτικά, αντιγράψτε και επικολλήστε αυτόν τον σύνδεσμο στον περιηγητή σας:</p>
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
        $email->setSubject('Επαναφορά Κωδικού Πρόσβασης');
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
                    <h2>Επαναφορά του Κωδικού Πρόσβασής σας</h2>
                    <p>Γεια σας {$username},</p>
                    <p>Λάβαμε ένα αίτημα για επαναφορά του κωδικού πρόσβασής σας. Κάντε κλικ στο παρακάτω κουμπί:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Επαναφορά Κωδικού</a>
                    </p>
                    <p class='warning'>Αυτός ο σύνδεσμος θα λήξει σε 1 ώρα.</p>
                    <p>Αν δεν ζητήσατε εσείς αυτήν την επαναφορά, παρακαλούμε αγνοήστε αυτό το email.</p>
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
            $subject = "Νέος Κατάλογος Δημοσιεύτηκε: $catalogName";
            $title = "Νέος Κατάλογος Διαθέσιμος";
            $message = "Ένας νέος κατάλογος έχει δημοσιευτεί.";
            break;
        case 'catalog_update':
            $subject = "Ενημέρωση Καταλόγου: $catalogName";
            $title = "Ενημέρωση Καταλόγου";
            $message = "Ο κατάλογος έχει ενημερωθεί με νέες πληροφορίες.";
            break;
        case 'position_change':
            $subject = "Αλλαγή Θέσης στον $catalogName";
            $title = "Η Θέση σας Άλλαξε";
            $message = "Υπήρξε αλλαγή στη θέση σας.";
            break;
        default:
            $subject = "Ειδοποίηση Καταλόγου: $catalogName";
            $title = "Ειδοποίηση Καταλόγου";
            $message = "Υπάρχουν νέες πληροφορίες για τον κατάλογό σας.";
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
                    <p>Γεια σας {$username},</p>
                    <p>{$message}</p>
                    
                    <div class='details'>
                        <p>Κατάλογος: <span class='catalog-name'>{$catalogName}</span></p>
                        " . ($details ? "<p>{$details}</p>" : "") . "
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='{$catalogLink}' class='button'>Προβολή Καταλόγου</a>
                    </p>
                    
                    <p>Αν επιθυμείτε να αλλάξετε τις προτιμήσεις ειδοποιήσεών σας, επισκεφθείτε τις ρυθμίσεις του λογαριασμού σας.</p>
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