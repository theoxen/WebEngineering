<?php
require_once 'utils/mail.php';

$result = sendVerificationEmail('theodosisx874@gmail.com', 'TestUser', 'test-token-123');
echo "Email sent: " . ($result ? "Success" : "Failed");
?>