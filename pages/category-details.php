<?php
// filepath: c:\xampp\htdocs\WebEngineering\category-details.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get category ID from URL
$categoryID = isset($_GET['id']) ? intval($_GET['id']) : null;

// Validate parameter
if (!$categoryID) {
    header("Location: homepage.php");
    exit;
}

// Connect to the database
require_once "../database/db_connect.php";

// Query to get category details
$stmt = $mysqli->prepare("SELECT * FROM categories WHERE categoryID = ?");
$stmt->bind_param("i", $categoryID);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

// If category not found, redirect
if (!$category) {
    header("Location: homepage.php");
    exit;
}

$pageTitle = $category['type'] . " - " . $category['season'] . " " . $category['year'];
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
    
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .pdf-container {
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    
    <div class="container mt-4">
        <div class="page-header">
            <h1 class="text-center"><?php echo htmlspecialchars($category['type']); ?></h1>
            <p class="text-center">
                <?php echo htmlspecialchars($category['season'] . " " . $category['year']); ?> - 
                <?php echo htmlspecialchars($category['fields'] ?? ''); ?>
            </p>
        </div>
        
        <?php if (!empty($category['file_path'])): ?>
        <div class="pdf-container">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Προβολή Αρχείου PDF</h5>
                    <iframe src="<?php echo htmlspecialchars($category['file_path']); ?>" 
                            width="100%" height="600" style="border: none;">
                    </iframe>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo htmlspecialchars($category['file_path']); ?>" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-download me-2"></i> Λήψη PDF
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Δεν υπάρχει διαθέσιμο αρχείο για αυτή την κατηγορία.
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4 mb-5">
            <a href="season-categories.php?year=<?php echo $category['year']; ?>&season=<?php echo urlencode($category['season']); ?>" 
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Επιστροφή στις κατηγορίες
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>