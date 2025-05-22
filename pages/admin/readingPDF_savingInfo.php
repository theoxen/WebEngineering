<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(500);

require_once __DIR__ . '/../../database/db_connect.php';

$pageTitle = "Process PDF Data";
$message = "";
$messageClass = "";

if (!isset($_POST['process_category']) && !isset($_POST['process_all'])) {
    // Get distinct years and seasons for filters
    $yearsQuery = "SELECT DISTINCT year FROM categories ORDER BY year DESC";
    $yearsResult = $mysqli->query($yearsQuery);
    
    $seasonsQuery = "SELECT DISTINCT season FROM categories ORDER BY season";
    $seasonsResult = $mysqli->query($seasonsQuery);
    
    // Build query with optional filters
    $categoriesListQuery = "SELECT categoryID, fields, type, season, year FROM categories WHERE 1=1";
    
    // Apply filters if provided
    $filterYear = isset($_GET['filter_year']) && !empty($_GET['filter_year']) ? $_GET['filter_year'] : null;
    $filterSeason = isset($_GET['filter_season']) && !empty($_GET['filter_season']) ? $_GET['filter_season'] : null;
    
    $filterParams = [];
    if ($filterYear) {
        $categoriesListQuery .= " AND year = ?";
        $filterParams[] = $filterYear;
    }
    
    if ($filterSeason) {
        $categoriesListQuery .= " AND season = ?";
        $filterParams[] = $filterSeason;
    }
    
    // Changed the ORDER BY clause to prioritize categoryID
    $categoriesListQuery .= " ORDER BY categoryID ASC";
    
    // Prepare and execute the query with potential filters
    $stmt = $mysqli->prepare($categoriesListQuery);
    
    if (!empty($filterParams)) {
        $types = str_repeat('s', count($filterParams));
        $stmt->bind_param($types, ...$filterParams);
    }
    
    $stmt->execute();
    $categoriesListResult = $stmt->get_result();
} else {
    // Process categories (either specific one or all)
    $categoriesQuery = "SELECT categoryID, pdf_content FROM categories WHERE 1=1";
    $queryParams = [];
    $paramTypes = "";

    // Apply filters if present in POST (coming from the form)
    if (isset($_POST['filter_year']) && !empty($_POST['filter_year'])) {
        $categoriesQuery .= " AND year = ?";
        $queryParams[] = $_POST['filter_year'];
        $paramTypes .= "s";
    }

    if (isset($_POST['filter_season']) && !empty($_POST['filter_season'])) {
        $categoriesQuery .= " AND season = ?";
        $queryParams[] = $_POST['filter_season'];
        $paramTypes .= "s";
    }

    // Add category ID filter if processing a specific category
    if (isset($_POST['category_id']) && !empty($_POST['category_id']) && !isset($_POST['process_all'])) {
        $categoryID = (int) $_POST['category_id'];
        $categoriesQuery .= " AND categoryID = ?";
        $queryParams[] = $categoryID;
        $paramTypes .= "i";
        $message = "Processing Category ID: $categoryID";
    } else {
        $message = "Processing " . (isset($_POST['filter_year']) || isset($_POST['filter_season']) ? "Filtered" : "All") . " Categories";
    }

    // Prepare and execute the query
    $stmt = $mysqli->prepare($categoriesQuery);

    if (!empty($queryParams)) {
        $stmt->bind_param($paramTypes, ...$queryParams);
    }

    $stmt->execute();
    $categoriesResult = $stmt->get_result();
    $processedCount = 0;
    $failedCount = 0;

    // Process results
    if ($categoriesResult->num_rows > 0) {
        while ($category = $categoriesResult->fetch_assoc()) {
            $categoryID = $category['categoryID'];
            $pdfContent = $category['pdf_content'];

            // Check if the categoryID already has records in the rankinglist table
            $rankinglistQuery = "SELECT COUNT(*) as count FROM rankinglist WHERE categoryID = ?";
            $stmt = $mysqli->prepare($rankinglistQuery);
            $stmt->bind_param("i", $categoryID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                // Delete all rows in rankinglist with this categoryID
                $deleteQuery = "DELETE FROM rankinglist WHERE categoryID = ?";
                $deleteStmt = $mysqli->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $categoryID);
                $deleteStmt->execute();
            }
            
            // Process the text content for this categoryID
            if (empty($pdfContent)) {
                $failedCount++;
                continue;
            }
            
            $text = $pdfContent;
            $lines = explode("\n", $text);

            if (empty($lines)) {
                $failedCount++;
                continue;
            }

            $rankinglist = [];
            foreach ($lines as $line) {
                // Only process lines starting with a number and having more than 30 characters
                if (mb_strlen($line, 'UTF-8') > 30 && preg_match('/^\d+/', $line)) {
                    $parsedData = parseLine($line);
                    if (!empty($parsedData)) {
                        $rankinglist[] = $parsedData;
                    }
                }
            }

            if (empty($rankinglist)) {
                $failedCount++;
                continue;
            }

            $insertQuery = "INSERT INTO rankinglist (ranking, fullName, appNum, points, titleDate, titleGrade, extraQualifications, experience, army, registrationDate, birthdayDate, notes, categoryID) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $mysqli->prepare($insertQuery);

            if (!$insertStmt) {
                $failedCount++;
                continue;
            }

            foreach ($rankinglist as $applicant) {
                // Convert dates from DD/MM/YYYY to YYYY-MM-DD
                $applicant['titleDate'] = !empty($applicant['titleDate']) ? 
                    DateTime::createFromFormat('d/m/Y', $applicant['titleDate'])->format('Y-m-d') : null;

                $applicant['registrationDate'] = !empty($applicant['registrationDate']) ? 
                    DateTime::createFromFormat('d/m/Y', $applicant['registrationDate'])->format('Y-m-d') : null;

                $applicant['birthdayDate'] = !empty($applicant['birthdayDate']) ? 
                    DateTime::createFromFormat('d/m/Y', $applicant['birthdayDate'])->format('Y-m-d') : null;

                $insertStmt->bind_param(
                    "issdssddssssi",
                    $applicant['ranking'],
                    $applicant['fullName'],
                    $applicant['appNum'],
                    $applicant['points'],
                    $applicant['titleDate'],
                    $applicant['titleGrade'],
                    $applicant['extraQualifications'],
                    $applicant['experience'],
                    $applicant['army'],
                    $applicant['registrationDate'],
                    $applicant['birthdayDate'],
                    $applicant['notes'],
                    $categoryID
                );

                if ($insertStmt->execute()) {
                    $processedCount++;
                } else {
                    $failedCount++;
                }
            }

            $insertStmt->close();
        }

        $messageClass = "success";
        $message .= "<br>Successfully processed $processedCount records. Failed: $failedCount";
    } else {
        $messageClass = "warning";
        $message = "No categories found matching your criteria.";
    }
    $mysqli->close();

    // Redirect back to the form with a message
    $_SESSION['message'] = $message;
    $_SESSION['messageClass'] = $messageClass;
    header("Location: readingPDF_savingInfo.php" . 
           ($filterYear ? "?filter_year=$filterYear" : "") . 
           ($filterSeason ? ($filterYear ? "&" : "?") . "filter_season=$filterSeason" : ""));
    exit();
}

// Function to normalize input before parsing
function normalizeInput($line) {
    // Remove extra whitespace
    $line = trim($line);

    // Replace multiple spaces with a single space
    $line = preg_replace('/\s+/', ' ', $line);

    // Convert Greek characters to uppercase (if needed)
    $line = mb_strtoupper($line, 'UTF-8');

    // Remove any unwanted characters (e.g., special symbols)
    $line = preg_replace('/[^A-Za-zΑ-Ωά-ώ0-9\s\-\/,\.]/u', '', $line);

    return $line;
}

// Function to parse an applicant line into structured data
function parseLine($line) {
    // Parsing logic remains the same
    $result = [];
    $cursor = 0;

    // Parse ranking
    preg_match('/^\d+/', $line, $matches);
    $result['ranking'] = isset($matches[0]) ? (int)$matches[0] : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse fullName (Greek letters and spaces)
    preg_match('/[\p{Greek}\s\-]+/u', substr($line, $cursor), $matches);
    $result['fullName'] = isset($matches[0]) ? trim($matches[0]) : '';
    $cursor += strlen($matches[0] ?? '');

    // Parse appNum
    preg_match('/(\d{1}+)/', substr($line, $cursor), $matches);
    $result['appNum'] = isset($matches[1]) ? (int)$matches[1] : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse points
    preg_match('/\s*(\d{1,2},\d{2})/', substr($line, $cursor), $matches);
    $result['points'] = isset($matches[1]) ? (float)str_replace(',', '.', $matches[1]) : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse titleDate
    preg_match('/(\d{2}\/\d{2}\/\d{4})/', substr($line, $cursor), $matches);
    $result['titleDate'] = $matches[1] ?? null;
    $cursor += strlen($matches[0] ?? '');

    // Parse titleGrade
    preg_match('/(\d{1})/', substr($line, $cursor), $matches);
    $result['titleGrade'] = isset($matches[1]) ? (int)$matches[1] : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse extraQualifications
    preg_match('/(\d+)/', substr($line, $cursor), $matches);
    $result['extraQualifications'] = isset($matches[1]) ? (int)$matches[1] : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse experience
    preg_match('/(\d{1,2},\d)/', substr($line, $cursor), $matches);
    $result['experience'] = isset($matches[1]) ? (float)str_replace(',', '.', $matches[1]) : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse army
    preg_match('/(\d{1},\d{2})/', substr($line, $cursor), $matches);
    $result['army'] = isset($matches[1]) ? (float)str_replace(',', '.', $matches[1]) : null;
    $cursor += strlen($matches[0] ?? '');

    // Parse registrationDate
    preg_match('/(\d{2}\/\d{2}\/\d{4})/', substr($line, $cursor), $matches);
    $result['registrationDate'] = $matches[1] ?? null;
    $cursor += strlen($matches[0] ?? '');

    // Parse birthdayDate
    preg_match('/(\d{2}\/\d{2}\/\d{4})/', substr($line, $cursor), $matches);
    $result['birthdayDate'] = $matches[1] ?? null;
    $cursor += strlen($matches[0] ?? '');

    // Parse notes
    preg_match('/[\p{Greek}\.]+/u', substr($line, $cursor), $matches);
    $result['notes'] = isset($matches[0]) ? trim($matches[0]) : '';

    return $result;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #5a5c69;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 700;
            padding: 1rem 1.25rem;
        }
        
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .process-section {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 1.25rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .btn-action {
            min-width: 120px;
        }
        
        .category-select {
            max-height: 300px;
            overflow-y: auto;
        }
        
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                padding-top: 60px;
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include sidebar with proper base URL for admin pages
    $baseUrl = "../../pages/";
    include_once('../../components/sidebar/sidebar.php'); 
    ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="page-title"><i class="fas fa-file-pdf me-2"></i><?php echo $pageTitle; ?></h1>
                <a href="dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['messageClass']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                unset($_SESSION['message']);
                unset($_SESSION['messageClass']);
                ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-filter me-2"></i>Filter Categories
                        </div>
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <div class="col-md-5">
                                    <label for="filter_year" class="form-label">Filter by Year:</label>
                                    <select name="filter_year" id="filter_year" class="form-select">
                                        <option value="">All Years</option>
                                        <?php while ($year = $yearsResult->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($year['year']) ?>" <?= ($filterYear == $year['year']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($year['year']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="filter_season" class="form-label">Filter by Season:</label>
                                    <select name="filter_season" id="filter_season" class="form-select">
                                        <option value="">All Seasons</option>
                                        <?php while ($season = $seasonsResult->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($season['season']) ?>" <?= ($filterSeason == $season['season']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($season['season']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i> Apply Filters
                                    </button>
                                </div>
                                <?php if ($filterYear || $filterSeason): ?>
                                <div class="col-12">
                                    <a href="readingPDF_savingInfo.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Clear All Filters
                                    </a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-cogs me-2"></i>Process PDF Data
                        </div>
                        <div class="card-body">
                            <form method="post" class="row g-3">
                                <div class="col-12">
                                    <label for="category_id" class="form-label">Select Category to Process:</label>
                                    <select name="category_id" id="category_id" class="form-select category-select" required>
                                        <option value="">-- Select Category --</option>
                                        <?php while ($cat = $categoriesListResult->fetch_assoc()): ?>
                                            <option value="<?= $cat['categoryID'] ?>">
                                                ID: <?= $cat['categoryID'] ?> - <?= htmlspecialchars($cat['fields']) ?> (<?= htmlspecialchars($cat['type']) ?>) - 
                                                <?= htmlspecialchars($cat['season']) ?> <?= htmlspecialchars($cat['year']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">Select a single category to process its data.</div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="alert alert-info d-flex align-items-center" role="alert">
                                        <i class="fas fa-info-circle fa-lg me-3"></i>
                                        <div>
                                            Processing will extract applicant data from PDF content and save it to the database.
                                            Any existing data for the selected category(ies) will be deleted first.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <button type="submit" name="process_category" class="btn btn-success btn-action w-100">
                                        <i class="fas fa-file me-1"></i> Process Selected Category
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="process_all" class="btn btn-warning btn-action w-100">
                                        <i class="fas fa-file-import me-1"></i> Process All Categories
                                    </button>
                                </div>
                                
                                <!-- Pass filters to POST if applicable -->
                                <?php if ($filterYear): ?>
                                    <input type="hidden" name="filter_year" value="<?= htmlspecialchars($filterYear) ?>">
                                <?php endif; ?>
                                <?php if ($filterSeason): ?>
                                    <input type="hidden" name="filter_season" value="<?= htmlspecialchars($filterSeason) ?>">
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>