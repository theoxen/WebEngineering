<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(500);

require_once __DIR__ . '/../../database/db_connect.php';

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
        <title>Process PDF Data by Category</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h1>Process PDF Data by Category</h1>
            
            <!-- Filter Form -->
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="filter_year" class="form-label">Filter by Year:</label>
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
                    <label for="filter_season" class="form-label">Filter by Season:</label>
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
                    <button type="submit" class="btn btn-secondary">Apply Filters</button>
                    <?php if ($filterYear || $filterSeason): ?>
                        <a href="?" class="btn btn-outline-secondary ms-2">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Process Form -->
            <form method="post" class="mt-4">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Select Category to Process:</label>
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
                <button type="submit" name="process_category" class="btn btn-primary">Process Selected Category</button>
                <button type="submit" name="process_all" class="btn btn-warning ms-2">Process All Categories</button>
                
                <!-- Pass filters to POST if applicable -->
                <?php if ($filterYear): ?>
                    <input type="hidden" name="filter_year" value="<?= htmlspecialchars($filterYear) ?>">
                <?php endif; ?>
                <?php if ($filterSeason): ?>
                    <input type="hidden" name="filter_season" value="<?= htmlspecialchars($filterSeason) ?>">
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

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
    echo "<div style='margin: 20px;'><h2>Processing Category ID: $categoryID</h2>";
} else {
    echo "<div style='margin: 20px;'><h2>Processing ";
    echo (isset($_POST['filter_year']) || isset($_POST['filter_season'])) ? "Filtered" : "All";
    echo " Categories</h2>";
}

// Prepare and execute the query
$stmt = $mysqli->prepare($categoriesQuery);

if (!empty($queryParams)) {
    $stmt->bind_param($paramTypes, ...$queryParams);
}

$stmt->execute();
$categoriesResult = $stmt->get_result();

// Rest of your original code follows
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
            echo "<p>Cleared existing data for category ID $categoryID.</p>";
        }
        
        // Process the text content for this categoryID
        if (empty($pdfContent)) {
            echo "<p>No text content found for category ID $categoryID.</p>";
            continue;
        }
        
        echo "<p>Processing category ID $categoryID...</p>";
        
        $text = $pdfContent;
        //echo "Extracted text for categoryID $categoryID: " . substr($text, 0, 500); // Log first 500 characters

        $lines = explode("\n", $text);
       // echo "Extracted lines: " . json_encode($lines);

        if (empty($lines)) {
          //  echo "No lines extracted from the text content for categoryID $categoryID.";
            continue;
        }

        $rankinglist = [];
        foreach ($lines as $line) {
            // Only process lines starting with a number and having more than 30 characters
            if (mb_strlen($line, 'UTF-8') > 30 && preg_match('/^\d+/', $line)) {
                echo "Read line: " . htmlspecialchars($line) . "<br>";
                $parsedData = parseLine($line);
                if (!empty($parsedData)) {
                    $rankinglist[] = $parsedData;
                }
            }
        }

        // Debug: Log the entire ranking list
      //  echo "Ranking List: " . json_encode($rankinglist);

        if (empty($rankinglist)) {
           // echo "No valid data extracted from the text content for categoryID $categoryID.";
            continue;
        }

        $insertQuery = "INSERT INTO rankinglist (ranking, fullName, appNum, points, titleDate, titleGrade, extraQualifications, experience, army, registrationDate, birthdayDate, notes, categoryID) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $mysqli->prepare($insertQuery);

        if (!$insertStmt) {
            //echo "Failed to prepare insert statement: " . $mysqli->error;
            continue;
        }

        foreach ($rankinglist as $applicant) {
            // Debug: Log the applicant data
          //  echo "Applicant Data: " . json_encode($applicant);

            // Convert dates from DD/MM/YYYY to YYYY-MM-DD
            $applicant['titleDate'] = !empty($applicant['titleDate']) ? 
                DateTime::createFromFormat('d/m/Y', $applicant['titleDate'])->format('Y-m-d') : null;

            $applicant['registrationDate'] = !empty($applicant['registrationDate']) ? 
                DateTime::createFromFormat('d/m/Y', $applicant['registrationDate'])->format('Y-m-d') : null;

            $applicant['birthdayDate'] = !empty($applicant['birthdayDate']) ? 
                DateTime::createFromFormat('d/m/Y', $applicant['birthdayDate'])->format('Y-m-d') : null;

            // Debug: Log the formatted data
           // echo "Formatted Applicant Data: " . json_encode($applicant);

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
               // echo "Failed to execute insert statement for categoryID $categoryID: <br>" . $insertStmt->error;
            } else {
               // echo "Successfully inserted data for categoryID $categoryID.<br>";
            }
        }

        $insertStmt->close();
        //echo "Data processing completed.<br>";
    }
}

$mysqli->close();

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