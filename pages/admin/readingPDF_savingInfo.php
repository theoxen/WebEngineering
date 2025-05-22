<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(500);

require_once __DIR__ . '/../../database/db_connect.php';

$totalCategories = 0;
$successCount = 0;

if (!isset($_POST['process_category'])) {
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Process PDF Data - Admin Dashboard</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        
        <!-- Custom CSS for sidebar -->
        <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
        
        <style>
            :root {
                --primary-color: #4e73df;
                --secondary-color: #1cc88a;
                --warning-color: #f6c23e;
                --danger-color: #e74a3b;
                --info-color: #36b9cc;
                --dark-color: #5a5c69;
                --light-color: #f8f9fc;
            }
            
            body {
                background-color: #f8f9fc;
                font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            }
            
            .main-content {
                transition: all 0.3s;
                padding: 1.5rem;
                margin-left: 250px;
                min-height: 100vh;
            }
            
            .page-header {
                margin-bottom: 2rem;
                border-bottom: 1px solid #e3e6f0;
                padding-bottom: 1rem;
            }
            
            .page-title {
                color: #5a5c69;
                font-weight: 700;
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
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
                display: flex;
                align-items: center;
            }
            
            .card-header i {
                margin-right: 0.75rem;
                color: var(--primary-color);
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .form-select:focus, .form-control:focus {
                border-color: #bac8f3;
                box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            }
            
            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }
            
            .btn-primary:hover {
                background-color: #2e59d9;
                border-color: #2653d4;
            }
            
            .btn-warning {
                background-color: var(--warning-color);
                border-color: var(--warning-color);
                color: #fff;
            }
            
            .btn-warning:hover {
                background-color: #dda20a;
                border-color: #d39e00;
                color: #fff;
            }
            
            @media (max-width: 767.98px) {
                .main-content {
                    margin-left: 0;
                    padding-top: 70px;
                }
            }
        </style>
    </head>
    <body>
        <?php include_once('../../components/sidebar/sidebar.php'); ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <div class="page-header d-flex align-items-center">
                    <h1 class="page-title"><i class="fas fa-file-pdf me-2 text-primary"></i>Process PDF Data</h1>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-filter"></i>Filter Options
                            </div>
                            <div class="card-body">
                                <!-- Filter Form -->
                                <form method="get" class="row g-3">
                                    <div class="col-md-4">
                                        <label for="filter_year" class="form-label"><i class="fas fa-calendar me-1"></i> Filter by Year:</label>
                                        <select name="filter_year" id="filter_year" class="form-select">
                                            <option value="">All Years</option>
                                            <?php while ($year = $yearsResult->fetch_assoc()): ?>
                                                <option value="<?= $year['year'] ?>" <?= ($filterYear == $year['year']) ? 'selected' : '' ?>>
                                                    <?= $year['year'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="filter_season" class="form-label"><i class="fas fa-sun me-1"></i> Filter by Season:</label>
                                        <select name="filter_season" id="filter_season" class="form-select">
                                            <option value="">All Seasons</option>
                                            <?php while ($season = $seasonsResult->fetch_assoc()): ?>
                                                <option value="<?= $season['season'] ?>" <?= ($filterSeason == $season['season']) ? 'selected' : '' ?>>
                                                    <?= $season['season'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i> Apply Filters
                                        </button>
                                        <?php if ($filterYear || $filterSeason): ?>
                                            <a href="?" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times me-1"></i> Clear Filters
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-cogs"></i>Process Categories
                            </div>
                            <div class="card-body">
                                <!-- Process Form -->
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label"><i class="fas fa-list me-1"></i> Select Category to Process:</label>
                                        <select name="category_id" id="category_id" class="form-select" required>
                                            <option value="">-- Select Category --</option>
                                            <?php while ($cat = $categoriesListResult->fetch_assoc()): ?>
                                                <option value="<?= $cat['categoryID'] ?>">
                                                    ID: <?= $cat['categoryID'] ?> - <?= htmlspecialchars($cat['fields']) ?> (<?= $cat['type'] ?>) - 
                                                    <?= $cat['season'] ?> <?= $cat['year'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="process_category" class="btn btn-primary">
                                            <i class="fas fa-play me-1"></i> Process Selected Category
                                        </button>
                                        <button type="submit" name="process_all" class="btn btn-warning">
                                            <i class="fas fa-play-circle me-1"></i> Process All Categories
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
    <?php
    exit;
}

// Processing code for when the form is submitted
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing PDF Data - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .main-content {
            transition: all 0.3s;
            padding: 1.5rem;
            margin-left: 250px;
            min-height: 100vh;
        }
        
        .page-header {
            margin-bottom: 2rem;
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 1rem;
        }
        
        .page-title {
            color: #5a5c69;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
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
        
        .card-body {
            padding: 1.25rem;
        }
        
        .log-container {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }
        }
    </style>
</head>
<body>';

// Include sidebar
include_once('../../components/sidebar/sidebar.php');

echo '<div class="main-content">';
echo '<div class="container-fluid">';
echo '<div class="page-header">';
echo '<h1 class="page-title"><i class="fas fa-file-pdf me-2 text-primary"></i>Processing PDF Data</h1>';
echo '</div>';

echo '<div class="card">';
echo '<div class="card-header">';
echo '<i class="fas fa-tasks me-2"></i>Processing Status';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="log-container">';

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
    echo "<div class='alert alert-info'><i class='fas fa-info-circle me-2'></i>Processing Category ID: $categoryID</div>";
} else {
    echo "<div class='alert alert-info'><i class='fas fa-info-circle me-2'></i>Processing ";
    echo (isset($_POST['filter_year']) || isset($_POST['filter_season'])) ? "Filtered" : "All";
    echo " Categories</div>";
}

// Prepare and execute the query
$stmt = $mysqli->prepare($categoriesQuery);

if (!empty($queryParams)) {
    $stmt->bind_param($paramTypes, ...$queryParams);
}

$stmt->execute();
$categoriesResult = $stmt->get_result();

// Process categories
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
            echo "<p><i class='fas fa-trash-alt text-warning me-2'></i>Cleared existing data for category ID $categoryID.</p>";
        }
        
        // Process the text content for this categoryID
        if (empty($pdfContent)) {
            echo "<p><i class='fas fa-exclamation-triangle text-danger me-2'></i>No text content found for category ID $categoryID.</p>";
            continue;
        }
        
        echo "<p><i class='fas fa-cog fa-spin text-primary me-2'></i>Processing category ID $categoryID...</p>";
        
        $text = $pdfContent;
        $lines = explode("\n", $text);

        if (empty($lines)) {
            continue;
        }

        $rankinglist = [];
        foreach ($lines as $line) {
            // Only process lines starting with a number and having more than 30 characters
            if (mb_strlen($line, 'UTF-8') > 30 && preg_match('/^\d+/', $line)) {
                echo "<p class='small text-muted'>Read line: " . htmlspecialchars($line) . "</p>";
                $parsedData = parseLine($line);
                if (!empty($parsedData)) {
                    $rankinglist[] = $parsedData;
                }
            }
        }

        if (empty($rankinglist)) {
            continue;
        }

        $insertQuery = "INSERT INTO rankinglist (ranking, fullName, appNum, points, titleDate, titleGrade, extraQualifications, experience, army, registrationDate, birthdayDate, notes, categoryID) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);

        if (!$insertStmt) {
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

            if (!$insertStmt->execute()) {
                echo "<div class='alert alert-danger mt-2 mb-2'><i class='fas fa-times-circle me-2'></i>Failed to insert record: " . $insertStmt->error . "</div>";
            } else {
                $successCount++;
            }
        }

        $insertStmt->close();
        $totalCategories++;
    }
}

$mysqli->close();

// Close the log container div
echo "</div>"; // .log-container

echo "<div class='alert alert-success mt-4'>
        <h4><i class='fas fa-check-circle me-2'></i> Processing Complete!</h4>
        <p><i class='fas fa-folder me-2'></i> Successfully processed $totalCategories categories.</p>
        <p><i class='fas fa-user-check me-2'></i> Successfully inserted $successCount applicant records into the database.</p>
      </div>";
echo "<div class='mt-3 mb-5'>
        <a href='?";
if (isset($_POST['filter_year'])) echo "filter_year=" . htmlspecialchars($_POST['filter_year']) . "&";
if (isset($_POST['filter_season'])) echo "filter_season=" . htmlspecialchars($_POST['filter_season']);
echo "' class='btn btn-primary'><i class='fas fa-arrow-left me-2'></i> Return to Category Selection</a>
      </div>";

echo '</div>'; // .card-body
echo '</div>'; // .card
echo '</div>'; // .container
echo '</div>'; // .main-content
echo '</body></html>';

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