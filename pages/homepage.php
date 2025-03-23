<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Page title
$pageTitle = "Home - WebEngineering";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">
    
    <!-- Custom site CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Additional homepage styles */
        .content-wrapper {
            padding-top: 20px;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding-top: 60px; /* Space for the mobile toggle button */
            }
        }
    </style>
</head>
<body>
    <?php
    // Include sidebar
    include_once('../components/sidebar/sidebar.php');
    ?>
    <h1>HOMEPAGE</h1>
   
</body>
</html>