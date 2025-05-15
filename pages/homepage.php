<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../database/db_connect.php';
// Check if we're returning from applicant-details.php with preserved search parameters
if (isset($_GET['return']) && $_GET['return'] == 'search' && !isset($_POST['searchApplicants']) && isset($_SESSION['last_search'])) {
    // Restore the previous search from session
    $_POST = $_SESSION['last_search'];
    $_POST['searchApplicants'] = true; // Force search execution
}

// Save search parameters to session when searching or when category filter changes
if (isset($_POST['searchApplicants']) || isset($_POST['categoryFilter'])) {
    $_SESSION['last_search'] = $_POST;
    
    // If only the category filter was changed (without clicking search)
    // add the searchApplicants key to ensure the search is executed when returning
    if (!isset($_POST['searchApplicants'])) {
        $_SESSION['last_search']['searchApplicants'] = true;
    }
}


$pageTitle = "Κατάλογοι Διοριστέων";

$currentYear = date("Y");
$currentMonth = date("n"); // Numeric representation of the month (1-12)

$monthNames = [
    2 => "Φεβρουάριος",
    6 => "Ιούνιος"
];

// Starting year
$startYear = 2016;
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

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap"
        rel="stylesheet">


    

    <style>
        /* Additional homepage styles */
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .table-responsive {
            width: 100%;
            max-width: 1200px; /* Increase this value as needed */
            margin: 0 auto;
        }

        .page-header {
            padding: 2rem 0;
            margin-bottom: 3rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .page-title {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }

        .page-description {
            color: #6c757d;
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .catalogs-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .year-section {
            margin-bottom: 1rem;
        }

        /* Vertical card styling */
        .catalog-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            background: white;
            display: flex;
            flex-direction: row;
            width: 100%;
        }

        .catalog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
        }

        .card-header-custom {
            background-color: #4e73df;
            color: white;
            padding: 1.5rem 1rem;
            /* Reduced horizontal padding */
            width: 200px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .card-header-custom::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: -20px;
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .card-title {
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
            margin-top: 1rem;
            /* Add these properties to fix the overflow */
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            width: 100%;
            hyphens: auto;
        }

        .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        .card-info {
            display: flex;
            justify-content: space-around;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #f1f5fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #4e73df;
        }

        .year-label,
        .month-label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.2rem;
            color: #2c3e50;
        }

        .card-icon {
            font-size: 3rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .btn-view {
            background-color: #4e73df;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            border: none;
        }

        .btn-view:hover {
            background-color: #2e59d9;
            transform: translateX(5px);
        }

        .year-divider {
            width: 100%;
            margin-bottom: 1.5rem;
            position: relative;
            text-align: center;
        }

        .year-divider h3 {
            background: #f8f9fc;
            display: inline-block;
            padding: 0.5rem 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .year-divider::after {
            content: '';
            position: absolute;
            width: 100%;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            left: 0;
            top: 50%;
            z-index: 0;
        }

        .text-end {
            align-self: center;
        }

        
        /* Search and tracking styles */
        .search-tracking-section .card {
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .action-buttons {
            white-space: nowrap;
            width: 1%;
            text-align: center;
        }

        .action-buttons .btn {
            display: inline-flex;
            margin: 0 2px;
        }
        
        .search-tracking-section .card-header {
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .search-tracking-section .table th {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .table-fixed-height {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .table-fixed-height thead {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 1;
        }
        
        /* Custom scrollbar styling */
        .table-fixed-height::-webkit-scrollbar {
            width: 8px;
        }
        
        .table-fixed-height::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-fixed-height::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .search-tracking-section .card-body {
            display: flex;
            flex-direction: column;
        }
        
        .search-tracking-section form {
            width: 100%;
        }
        
        .search-results {
            width: 100%;
        }

        .table-fixed-height::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        

        @media (max-width: 955px) {

            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }

            .card-title {
                font-size: 1.1rem;
                margin-top: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .catalog-card {
                flex-direction: column;
            }

            .card-header-custom {
                width: 100%;
                padding: 1rem;
            }

            .card-info {
                flex-direction: column;
            }

            .info-item {
                margin-bottom: 1rem;
                margin-right: 0;
            }

            .card-body {
                flex-direction: column;
            }

            .text-end {
                align-self: flex-end;
            }

            .action-buttons {
                min-width: 90px;
            }

        }
    </style>
</head>

<body>
    <?php
    // Include sidebar
    include_once('../components/sidebar/sidebar.php');

    // // Database connection
    // $servername = "localhost";
    // $username = "root"; 
    // $password = ""; 
    // $dbname = "cei326omada1";
    
    // $conn = new mysqli($servername, $username, $password, $dbname);
    
    // if ($conn->connect_error) {
    //     die("Connection failed: " . $conn->connect_error);
    // }

    if (isset($_SESSION['userId']) || isset($_SESSION['user_id'])) {
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : $_SESSION['user_id'];
        
        // Reset tracked applicants array
        $_SESSION['tracked_applicants'] = [];
        
        // Get all tracked applicants from the database using the tracking table structure
        $sql = "SHOW TABLES LIKE 'categories'";
        $result = $mysqli->query($sql);

        if ($result && $result->num_rows > 0) {
            // Categories table exists
            $sql = "SELECT t.*, r.*, c.fields, t.trackingID, t.isOwnCandidate 
                    FROM trackings t
                    JOIN rankinglist r ON (r.fullName = t.candidateFullName 
                                    AND r.birthdayDate = t.candidateBirthdayDate)
                    LEFT JOIN categories c ON r.categoryID = c.categoryID
                    WHERE t.userID = ?";
        } else {
            // No categories table, just query rankinglist
            $sql = "SELECT t.*, r.*, 'Unknown' as fields 
                    FROM trackings t
                    JOIN rankinglist r ON (r.fullName = t.candidateFullName 
                                       AND r.birthdayDate = t.candidateBirthdayDate
                                       AND r.appNum = t.appNum)
                    WHERE t.userID = ?";
        }

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Make sure fields exists
                if (!isset($row['fields'])) {
                    $row['fields'] = 'N/A';
                }
                $_SESSION['tracked_applicants'][] = $row;
            }
        }
    }
    
    // Handle search functionality
    $searchResults = [];
if (isset($_POST['searchApplicants'])) {
    $searchTerm = isset($_POST['searchTerm']) ? $mysqli->real_escape_string($_POST['searchTerm']) : '';
    
    // Get date filter values
    $birthdayFrom = isset($_POST['birthdayFrom']) && !empty($_POST['birthdayFrom']) ? $mysqli->real_escape_string($_POST['birthdayFrom']) : '';
    $birthdayTo = isset($_POST['birthdayTo']) && !empty($_POST['birthdayTo']) ? $mysqli->real_escape_string($_POST['birthdayTo']) : '';
    $registrationFrom = isset($_POST['registrationFrom']) && !empty($_POST['registrationFrom']) ? $mysqli->real_escape_string($_POST['registrationFrom']) : '';
    $registrationTo = isset($_POST['registrationTo']) && !empty($_POST['registrationTo']) ? $mysqli->real_escape_string($_POST['registrationTo']) : '';
    
    // Get category filter
    $categoryFilter = isset($_POST['categoryFilter']) && !empty($_POST['categoryFilter']) ? $mysqli->real_escape_string($_POST['categoryFilter']) : '';
    
    // Check if categories table exists
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $mysqli->query($sql);
    $categoriesExist = ($result && $result->num_rows > 0);
    
    if ($categoriesExist) {
        // Base SQL with categories
        $sql = "SELECT r.*, c.fields 
               FROM rankinglist r 
               JOIN categories c ON r.categoryID = c.categoryID WHERE 1=1";
               
        // Add category filter if selected
        if (!empty($categoryFilter)) {
            $sql .= " AND r.categoryID = '$categoryFilter'";
        }
        
        // Add search conditions - now including category name in the search
        if (!empty($searchTerm)) {
            $sql .= " AND (r.fullName LIKE '%$searchTerm%' OR r.appNum LIKE '%$searchTerm%' OR c.fields LIKE '%$searchTerm%')";
        }
        
        // Add birthday date filter
        if (!empty($birthdayFrom)) {
            $sql .= " AND r.birthdayDate >= '$birthdayFrom'";
        }
        if (!empty($birthdayTo)) {
            $sql .= " AND r.birthdayDate <= '$birthdayTo'";
        }
        
        // Add registration date filter
        if (!empty($registrationFrom)) {
            $sql .= " AND r.registrationDate >= '$registrationFrom'";
        }
        if (!empty($registrationTo)) {
            $sql .= " AND r.registrationDate <= '$registrationTo'";
        }
        
        // Get limit from URL parameter (default to 50)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        // Validate the limit to only allow 25, 50, or 100
        if (!in_array($limit, [25, 50, 100])) {
            $limit = 50;
        }

        // Add the LIMIT clause to your SQL query
        $sql .= " ORDER BY r.ranking ASC LIMIT $limit";
    } else {
        // Base SQL without categories
        $sql = "SELECT r.*, 'Unknown' as fields 
               FROM rankinglist r WHERE 1=1";
               
        // Add search conditions - without category search since table doesn't exist
        if (!empty($searchTerm)) {
            $sql .= " AND (r.fullName LIKE '%$searchTerm%' OR r.appNum LIKE '%$searchTerm%')";
        }
        
        // Add birthday date filter
        if (!empty($birthdayFrom)) {
            $sql .= " AND r.birthdayDate >= '$birthdayFrom'";
        }
        if (!empty($birthdayTo)) {
            $sql .= " AND r.birthdayDate <= '$birthdayTo'";
        }
        
        // Add registration date filter
        if (!empty($registrationFrom)) {
            $sql .= " AND r.registrationDate >= '$registrationFrom'";
        }
        if (!empty($registrationTo)) {
            $sql .= " AND r.registrationDate <= '$registrationTo'";
        }
        
        // Get limit from URL parameter (default to 50)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        // Validate the limit to only allow 25, 50, or 100
        if (!in_array($limit, [25, 50, 100])) {
            $limit = 50;
        }

        // Add the LIMIT clause to your SQL query
        $sql .= " ORDER BY r.ranking ASC LIMIT $limit";
    }
    
    // Execute query
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $searchResults[] = $row;
        }
    }
}
    
    // Get tracked applicants list from session
    if (!isset($_SESSION['tracked_applicants'])) {
        $_SESSION['tracked_applicants'] = [];
    }
    $trackedApplicants = $_SESSION['tracked_applicants'];
    ?>


        <div class="main-content">

    <div class="container content-wrapper">
        <div class="page-header">
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            <p class="page-description">Πρόσβαση στους καταλόγους διοριστέων εκπαιδευτικών ανά περίοδο και έτος. Επιλέξτε τον κατάλογο που επιθυμείτε.</p>
        </div>
        
        <!-- Applicant Search and Tracking Section -->
        <div class="search-tracking-section mb-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-center"><i class="fas fa-search me-2"></i> Αναζήτηση Υποψηφίων</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="searchForm">
                        <!-- Search input at top, full width -->
                        <div class="mb-4">
                            <input type="text" class="form-control form-control-lg" name="searchTerm" 
                                placeholder="Αναζήτηση με ονοματεπώνυμο ή αριθμό αίτησης..." 
                                value="<?php echo isset($_POST['searchTerm']) ? htmlspecialchars($_POST['searchTerm']) : ''; ?>">
                        </div>
                        
                        <!-- Date filters -->
                        <div class="row mb-4">
                            <!-- Birthday Date Range -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ημερομηνία Γέννησης:</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            <input type="date" class="form-control" name="birthdayFrom" 
                                                value="<?php echo isset($_POST['birthdayFrom']) ? $_POST['birthdayFrom'] : ''; ?>">
                                        </div>
                                        <small class="text-muted">Από</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            <input type="date" class="form-control" name="birthdayTo" 
                                                value="<?php echo isset($_POST['birthdayTo']) ? $_POST['birthdayTo'] : ''; ?>">
                                        </div>
                                        <small class="text-muted">Έως</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Registration Date Range -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ημερομηνία Εγγραφής Στους Καταλόγους:</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            <input type="date" class="form-control" name="registrationFrom" 
                                                value="<?php echo isset($_POST['registrationFrom']) ? $_POST['registrationFrom'] : ''; ?>">
                                        </div>
                                        <small class="text-muted">Από</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            <input type="date" class="form-control" name="registrationTo" 
                                                value="<?php echo isset($_POST['registrationTo']) ? $_POST['registrationTo'] : ''; ?>">
                                        </div>
                                        <small class="text-muted">Έως</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category Dropdown - New Section -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Κατηγορία Υποψηφίων:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                <select class="form-select" name="categoryFilter" id="categoryFilter">
                                    <option value="">-- Επιλέξτε Κατηγορία --</option>
                                    
                                    <?php
                                    // Check if categories table exists and fetch categories
                                    $sql = "SHOW TABLES LIKE 'categories'";
                                    $result = $mysqli->query($sql);
                                    
                                    if ($result && $result->num_rows > 0) {
                                        // Categories table exists, fetch all categories
                                        $sql = "SELECT categoryID, fields FROM categories ORDER BY fields";
                                        $categoryResult = $mysqli->query($sql);
                                        
                                        if ($categoryResult && $categoryResult->num_rows > 0) {
                                            while ($category = $categoryResult->fetch_assoc()) {
                                                $selected = (isset($_POST['categoryFilter']) && $_POST['categoryFilter'] == $category['categoryID']) ? 'selected' : '';
                                                echo "<option value='{$category['categoryID']}' {$selected}>" . 
                                                    htmlspecialchars($category['fields']) . "</option>";
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <small class="text-muted">Επιλέξτε μια κατηγορία για να εμφανιστούν οι αντίστοιχοι υποψήφιοι</small>
                        </div>
                        
                      

                        <div class="text-center mb-4 d-flex justify-content-center gap-3">
                            <button type="submit" name="searchApplicants" class="btn btn-primary px-5">
                                <i class="fas fa-search me-2"></i> Αναζήτηση
                            </button>
                            <button type="button" id="clearFilters" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-eraser me-2"></i> Καθαρισμός Φίλτρων
                            </button>
                        </div>
                    </form>
                
                    <div class="search-results mt-4">
                    <h5 class="text-center mb-3">
                        <?php 
                        $isSearch = isset($_POST['searchApplicants']);
                        $hasSearchTerm = !empty($_POST['searchTerm']);
                        $hasBirthdayFilter = !empty($_POST['birthdayFrom']) || !empty($_POST['birthdayTo']);
                        $hasRegDateFilter = !empty($_POST['registrationFrom']) || !empty($_POST['registrationTo']);
                        $hasCategoryFilter = !empty($_POST['categoryFilter']);
                        
                        if ($isSearch && ($hasSearchTerm || $hasBirthdayFilter || $hasRegDateFilter || $hasCategoryFilter)) {
                            echo 'Αποτελέσματα Αναζήτησης';
                        } else {
                            echo 'Λίστα Υποψηφίων';
                        }
                        ?>
                    </h5>                        
                        <?php
                        // Get applicants to display (either search results or filtered by category)
                        $displayApplicants = [];

                        // Check if we have a category filter or search parameters
                        $categoryFilter = isset($_POST['categoryFilter']) && !empty($_POST['categoryFilter']) ? 
                                        $mysqli->real_escape_string($_POST['categoryFilter']) : '';
                        $hasSearchTerm = !empty($_POST['searchTerm']);
                        $hasBirthdayFilter = !empty($_POST['birthdayFrom']) || !empty($_POST['birthdayTo']);
                        $hasRegDateFilter = !empty($_POST['registrationFrom']) || !empty($_POST['registrationTo']);

                        // Only fetch results if any filter is applied (category, search term, or date filters)
                        if (!empty($categoryFilter) || $hasSearchTerm || $hasBirthdayFilter || $hasRegDateFilter) {
                            // Build the SQL query based on all applied filters
                            $sql = "SHOW TABLES LIKE 'categories'";
                            $result = $mysqli->query($sql);
                            $categoriesExist = ($result && $result->num_rows > 0);
                            
                            if ($categoriesExist) {
                                // Base SQL with categories
                                if ($categoryFilter == 'all') {
                                    // Special case - show all applicants
                                    $sql = "SELECT r.*, c.fields 
                                          FROM rankinglist r 
                                          LEFT JOIN categories c ON r.categoryID = c.categoryID WHERE 1=1";
                                } else {
                                    // Normal filtering by specific category
                                    $sql = "SELECT r.*, c.fields 
                                          FROM rankinglist r 
                                          JOIN categories c ON r.categoryID = c.categoryID WHERE 1=1";
                                          
                                    // Add category filter if selected (and not "all")
                                    if (!empty($categoryFilter)) {
                                        $sql .= " AND r.categoryID = '$categoryFilter'";
                                    }
                                }
                                
                                // Add search conditions
                                if (!empty($_POST['searchTerm'])) {
                                    $searchTerm = $mysqli->real_escape_string($_POST['searchTerm']);
                                    $sql .= " AND (r.fullName LIKE '%$searchTerm%' OR r.appNum LIKE '%$searchTerm%' OR c.fields LIKE '%$searchTerm%')";
                                }
                                
                                // Add birthday date filter
                                if (!empty($_POST['birthdayFrom'])) {
                                    $birthdayFrom = $mysqli->real_escape_string($_POST['birthdayFrom']);
                                    $sql .= " AND r.birthdayDate >= '$birthdayFrom'";
                                }
                                if (!empty($_POST['birthdayTo'])) {
                                    $birthdayTo = $mysqli->real_escape_string($_POST['birthdayTo']);
                                    $sql .= " AND r.birthdayDate <= '$birthdayTo'";
                                }
                                
                                // Add registration date filter
                                if (!empty($_POST['registrationFrom'])) {
                                    $registrationFrom = $mysqli->real_escape_string($_POST['registrationFrom']);
                                    $sql .= " AND r.registrationDate >= '$registrationFrom'";
                                }
                                if (!empty($_POST['registrationTo'])) {
                                    $registrationTo = $mysqli->real_escape_string($_POST['registrationTo']);
                                    $sql .= " AND r.registrationDate <= '$registrationTo'";
                                }
                                
                                // Get limit from URL parameter (default to 50)
                                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

                                // Validate the limit to only allow 25, 50, or 100
                                if (!in_array($limit, [25, 50, 100])) {
                                    $limit = 50;
                                }

                                // Add the LIMIT clause to your SQL query
                                $sql .= " ORDER BY r.ranking ASC LIMIT $limit";
                            } else {
                                // Base SQL without categories
                                $sql = "SELECT r.*, 'Unknown' as fields 
                                    FROM rankinglist r WHERE 1=1";
                                    
                                // Add search conditions
                                if (!empty($_POST['searchTerm'])) {
                                    $searchTerm = $mysqli->real_escape_string($_POST['searchTerm']);
                                    $sql .= " AND (r.fullName LIKE '%$searchTerm%' OR r.appNum LIKE '%$searchTerm%')";
                                }
                                
                                // Add birthday date filter
                                if (!empty($_POST['birthdayFrom'])) {
                                    $birthdayFrom = $mysqli->real_escape_string($_POST['birthdayFrom']);
                                    $sql .= " AND r.birthdayDate >= '$birthdayFrom'";
                                }
                                if (!empty($_POST['birthdayTo'])) {
                                    $birthdayTo = $mysqli->real_escape_string($_POST['birthdayTo']);
                                    $sql .= " AND r.birthdayDate <= '$birthdayTo'";
                                }
                                
                                // Add registration date filter
                                if (!empty($_POST['registrationFrom'])) {
                                    $registrationFrom = $mysqli->real_escape_string($_POST['registrationFrom']);
                                    $sql .= " AND r.registrationDate >= '$registrationFrom'";
                                }
                                if (!empty($_POST['registrationTo'])) {
                                    $registrationTo = $mysqli->real_escape_string($_POST['registrationTo']);
                                    $sql .= " AND r.registrationDate <= '$registrationTo'";
                                }
                                
                                // Get limit from URL parameter (default to 50)
                                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

                                // Validate the limit to only allow 25, 50, or 100
                                if (!in_array($limit, [25, 50, 100])) {
                                    $limit = 50;
                                }

                                // Add the LIMIT clause to your SQL query
                                $sql .= " ORDER BY r.ranking ASC LIMIT $limit";
                            }
                            
                            // Execute query
                            $result = $mysqli->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $displayApplicants[] = $row;
                                }
                            }
                        }
                        
                        if (!empty($displayApplicants)): 
                        ?>
                        <form method="POST" action="../track-applicants.php" id="trackForm">
                            <div class="table-responsive table-fixed-height">
                                <div class="mb-3 d-flex align-items-center">
                                    <span class="me-2">Show entries:</span>
                                    <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="25">25</option>
                                        <option value="50" selected>50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="selectAll" class="form-check-input"> Επιλογή</th>
                                            <th>Κατάταξη</th>
                                            <th>Ονοματεπώνυμο</th>
                                            <th>Αρ. Αίτησης</th>
                                            <th>Μόρια</th>
                                            <th>Κατηγορία</th>
                                            <th>Ημ. Εγγραφής</th>
                                            <th>Ημ. Πτυχίου</th>
                                            <th>Ενέργειες</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($displayApplicants as $applicant): 
                                        // Check if this applicant is already being tracked
                                        $isTracked = false;
                                        foreach ($trackedApplicants as $tracked) {
                                            if ($tracked['id'] == $applicant['id']) {
                                                $isTracked = true;
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr<?php echo $isTracked ? ' class="table-light"' : ''; ?>>
                                            <td>
                                                <input type="checkbox" name="track_applicants[]" value="<?php echo $applicant['id']; ?>" 
                                                    class="form-check-input applicant-check" <?php echo $isTracked ? 'checked' : ''; ?>>
                                            </td>
                                            <td><?php echo $applicant['ranking']; ?></td>
                                            <td><?php echo htmlspecialchars($applicant['fullName']); ?></td>
                                            <td><?php echo $applicant['appNum']; ?></td>
                                            <td><?php echo number_format($applicant['points'], 2); ?></td>
                                            <td><?php echo isset($applicant['fields']) ? htmlspecialchars($applicant['fields']) : 'N/A'; ?></td>
                                            <td><?php echo isset($applicant['registrationDate']) ? date('d/m/Y', strtotime($applicant['registrationDate'])) : 'N/A'; ?></td>
                                            <td><?php echo isset($applicant['titleDate']) ? date('d/m/Y', strtotime($applicant['titleDate'])) : 'N/A'; ?></td>
                                            <td class="action-buttons">
                                            
                                            
                                            <a href="applicant-details.php?id=<?php echo $applicant['id']; ?>&return=search" class="btn btn-sm btn-info">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-success" name="track_selected">
                                    <i class="fas fa-user-check me-2"></i> Παρακολούθηση Επιλεγμένων
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <?php if (empty($categoryFilter) && !$hasSearchTerm && !$hasBirthdayFilter && !$hasRegDateFilter): ?>
                                <i class="fas fa-info-circle me-2"></i> Παρακαλώ εφαρμόστε κάποιο φίλτρο αναζήτησης ή επιλέξτε μια κατηγορία για να εμφανιστούν αποτελέσματα.
                            <?php else: ?>
                                <i class="fas fa-info-circle me-2"></i> Δεν βρέθηκαν υποψήφιοι με τα επιλεγμένα κριτήρια.
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tracked Applicants Section -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user-check me-2"></i> Υπό Παρακολούθηση Υποψήφιοι</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($trackedApplicants)): ?>
                        <div class="table-responsive table-fixed-height">
    <table class="table table-hover">
        <thead class="table-light">
            <tr>
                <th>Δικός μου</th>
                <th>Κατάταξη</th>
                <th>Ονοματεπώνυμο</th>
                <th>Αρ. Αίτησης</th>
                <th>Μόρια</th>
                <th>Κατηγορία</th>
                <th>Ημ. Εγγραφής</th>
                <th>Ημ. Πτυχίου</th>
                <th>Ενέργειες</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trackedApplicants as $applicant): 
                $isOwn = isset($applicant['isOwnCandidate']) && $applicant['isOwnCandidate'] == 1;
            ?>
                <tr class="<?php echo $isOwn ? 'table-success' : ''; ?>">
                    <td>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input own-candidate-check" 
                                   data-id="<?php echo $applicant['trackingID']; ?>"
                                   <?php echo $isOwn ? 'checked' : ''; ?>>
                        </div>
                    </td>
                    <td><?php echo $applicant['ranking']; ?></td>
                    <td><?php echo htmlspecialchars($applicant['fullName']); ?></td>
                    <td><?php echo $applicant['appNum']; ?></td>
                    <td><?php echo number_format($applicant['points'], 2); ?></td>
                    <td><?php echo isset($applicant['fields']) ? htmlspecialchars($applicant['fields']) : 'N/A'; ?></td>
                    <td><?php echo isset($applicant['registrationDate']) ? date('d/m/Y', strtotime($applicant['registrationDate'])) : 'N/A'; ?></td>
                    <td><?php echo isset($applicant['titleDate']) ? date('d/m/Y', strtotime($applicant['titleDate'])) : 'N/A'; ?></td>
                    <td class="action-buttons">
                        <button type="button" class="btn btn-sm btn-danger untrack-btn" data-id="<?php echo $applicant['id']; ?>">
                            <i class="fas fa-user-minus"></i>
                        </button>
                        <a href="applicant-details.php?id=<?php echo $applicant['id']; ?>&return=search" class="btn btn-sm btn-info">
                            <i class="fas fa-info-circle"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Δεν έχετε προσθέσει ακόμη υποψηφίους για παρακολούθηση.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="catalogs-container">
        <?php
        // Loop through each year from current year down to the starting year
        for ($year = $currentYear; $year >= $startYear; $year--) {
            // Start a new section for this year
            echo '<div class="year-section">';
            
            // Display year divider
            echo '<div class="year-divider text-center"><h3>' . $year . '</h3></div>';
            
            // Begin cards for this year
            echo '<div class="year-cards">';
            
            // Check which months exist for this year
            $hasFebruary = ($year < $currentYear || ($year == $currentYear && $currentMonth >= 2));
            $hasJune = ($year < $currentYear || ($year == $currentYear && $currentMonth >= 6));
            
            // February catalog if available
            if ($hasFebruary) {
                $monthNum = 2;
                $monthName = $monthNames[$monthNum];

               // $cardUrl = "year-season-details.php?year=" . $year . "&month=" . $monthNum;

                $cardUrl = "season-categories.php?year=" . $year . "&season=Φεβρουάριος";

                echo '<a href="' . $cardUrl . '" class="card-link">
                    <div class="catalog-card">
                        <div class="card-header-custom" style="background-color: #4e73df;">
                            <i class="fas fa-snowflake card-icon"></i>
                            <h5 class="card-title">
                                ' . $monthName . '
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="card-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="year-label">Έτος</span>
                                        <div class="info-value">' . $year . '</div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-list-alt"></i>
                                    </div>
                                    <div>
                                        <span class="month-label">Κατάλογος</span>
                                        <div class="info-value">Διοριστέων Εκπαιδευτικών</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button class="btn-view">
                                    Προβολή <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </a>';

            }
            
            // June catalog if available
            if ($hasJune) {
                $monthNum = 6;
                $monthName = $monthNames[$monthNum];
                $cardUrl = "season-categories.php?year=" . $year . "&season=Ιούνιος";               
                echo '<a href="' . $cardUrl . '" class="card-link">
                    <div class="catalog-card">
                        <div class="card-header-custom" style="background-color: #f6c23e;">
                            <i class="fas fa-sun card-icon"></i>
                            <h5 class="card-title">
                                ' . $monthName . '
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="card-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="year-label">Έτος</span>
                                        <div class="info-value">' . $year . '</div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-list-alt"></i>
                                    </div>
                                    <div>
                                        <span class="month-label">Κατάλογος</span>
                                        <div class="info-value">Διοριστέων Εκπαιδευτικών</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button class="btn-view">
                                    Προβολή <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </a>';
                    }

                    // Close cards and section for this year
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery for AJAX functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
    // Select all checkbox functionality
    $('#selectAll').change(function() {
        $('.applicant-check').prop('checked', $(this).prop('checked'));
    });

    $('#clearFilters').click(function() {
    // Clear the text input
    $('input[name="searchTerm"]').val('');
    
    // Clear date fields
    $('input[name="birthdayFrom"]').val('');
    $('input[name="birthdayTo"]').val('');
    $('input[name="registrationFrom"]').val('');
    $('input[name="registrationTo"]').val('');
    
    // Reset category dropdown to default
    $('#categoryFilter').val('');
    
    // Submit the form to refresh results
    $('#searchForm').submit();
    });
    
    // Individual track button
    $('.track-single').click(function(e) {
        e.preventDefault();
        const applicantID = $(this).data('id');
        const $button = $(this);
        const $row = $button.closest('tr');
        
        $.ajax({
            url: '../track-applicants.php',
            method: 'POST',
            dataType: 'json',
            data: {
                track_single: true,
                applicantID: applicantID
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Update the button appearance directly
                    $button.removeClass('btn-primary').addClass('btn-danger');
                    $button.removeClass('track-single').addClass('untrack-btn');
                    $button.html('<i class="fas fa-user-minus"></i>');
                    $row.addClass('table-light');
                    
                    // Check the checkbox
                    $row.find('.applicant-check').prop('checked', true);
                    
                    // Use AJAX to update tracked section without page reload
                    $.post('get-tracked-section.php', {}, function(data) {
                        $('.card-header.bg-success').closest('.card').find('.card-body').html(data);
                        // Rebind event handlers for newly added elements
                        bindTrackedEvents();
                    });
                }
            },
            error: function() {
                alert('Προέκυψε σφάλμα κατά την προσθήκη του υποψηφίου');
            }
        });
    });
    
    // Untrack button functionality
    $(document).on('click', '.untrack-btn', function(e) {
        e.preventDefault();
        const applicantID = $(this).data('id');
        const $button = $(this);
        const $row = $button.closest('tr');
        const isInTrackedSection = $row.closest('.card').find('.card-header').hasClass('bg-success');
        
        $.ajax({
            url: '../track-applicants.php',
            method: 'POST',
            dataType: 'json',
            data: {
                untrack: true,
                applicantID: applicantID
            },
            success: function(response) {
                if (response.status === 'success') {
                    if (isInTrackedSection) {
                        // If in tracked section, remove the row
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // If no more tracked rows, show empty message
                            if ($('.card-header.bg-success').closest('.card').find('tbody tr').length === 0) {
                                $('.card-header.bg-success').closest('.card').find('.card-body').html(
                                    '<div class="alert alert-info">' +
                                    '<i class="fas fa-info-circle me-2"></i> Δεν έχετε προσθέσει ακόμη υποψηφίους για παρακολούθηση.' +
                                    '</div>'
                                );
                            }
                        });
                        
                        // Update search results display if the row exists there
                        const $searchRow = $('.search-results .applicant-check[value="' + applicantID + '"]').closest('tr');
                        if ($searchRow.length) {
                            $searchRow.removeClass('table-light');
                            $searchRow.find('.applicant-check').prop('checked', false);
                            $searchRow.find('.untrack-btn')
                                .removeClass('btn-danger').addClass('btn-primary')
                                .removeClass('untrack-btn').addClass('track-single')
                                .html('<i class="fas fa-user-plus"></i>');
                        }
                    } else {
                        // If in search results, update button appearance
                        $button.removeClass('btn-danger').addClass('btn-primary');
                        $button.removeClass('untrack-btn').addClass('track-single');
                        $button.html('<i class="fas fa-user-plus"></i>');
                        $row.removeClass('table-light');
                        
                        // Uncheck the checkbox
                        $row.find('.applicant-check').prop('checked', false);
                        
                        // Update tracked section
                        $.post('get-tracked-section.php', {}, function(data) {
                            $('.card-header.bg-success').closest('.card').find('.card-body').html(data);
                            // Rebind event handlers for newly added elements
                            bindTrackedEvents();
                        });
                    }
                }
            },
            error: function() {
                alert('Προέκυψε σφάλμα κατά την αφαίρεση του υποψηφίου');
            }
        });
    });

    function bindTrackedEvents() {
        // Handle own candidate checkbox change
        $('.own-candidate-check').change(function() {
            const trackingID = $(this).data('id');
            const isChecked = $(this).prop('checked');
            
            // If checking a candidate
            if (isChecked) {
                // Disable other checkboxes and use tooltips
                $('.own-candidate-check:not(:checked)').prop('disabled', true);
                $('.own-candidate-check:not(:checked)').attr('title', 'Αποεπιλέξτε τον υπάρχοντα υποψήφιο πρώτα');
                
                // Initialize Bootstrap tooltips for newly disabled checkboxes
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('.own-candidate-check:not(:checked)'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
                
                // Add info message if it doesn't exist
                if ($('#own-candidate-info').length === 0) {
            $('.card-header.bg-success').closest('.card').find('.card-body').prepend(
                '<div id="own-candidate-info" class="alert alert-info mb-3">' +
                '<i class="fas fa-info-circle me-2"></i> ' +
                'Μόνο ένας υποψήφιος μπορεί να επισημανθεί ως "Δικός μου". Για να επιλέξετε διαφορετικό υποψήφιο, αποεπιλέξτε πρώτα τον τρέχοντα.' +
                '</div>'
            );
        }
            } else {
                // If unchecking, re-enable all checkboxes and remove tooltips
                $('.own-candidate-check').prop('disabled', false);
                $('.own-candidate-check').removeAttr('title');
                
                // Dispose tooltips
                $('.own-candidate-check').each(function() {
                    var tooltip = bootstrap.Tooltip.getInstance(this);
                    if (tooltip) {
                        tooltip.dispose();
                    }
                });
                
                // Remove info message
                $('#own-candidate-info').remove();
            }
            
            // Toggle the table-success class
            $(this).closest('tr').toggleClass('table-success', isChecked);
            
            // Send AJAX request to update database
            $.ajax({
                url: '../toggle-own-candidate.php',
                method: 'POST',
                data: {
                    trackingID: trackingID,
                    isOwn: isChecked
                },
                success: function(response) {
                    if (response.status !== 'success') {
                        alert('Προέκυψε σφάλμα κατά την ενημέρωση');
                    }
                },
                error: function() {
                    alert('Προέκυψε σφάλμα επικοινωνίας με τον διακομιστή');
                }
            });
        });
    }

    // Initialize own candidate checkboxes - disable other checkboxes if one is already checked
    if ($('.own-candidate-check:checked').length > 0) {
        // Just disable the checkboxes and use tooltips instead of text messages
        $('.own-candidate-check:not(:checked)').prop('disabled', true);
        $('.own-candidate-check:not(:checked)').attr('title', 'Αποεπιλέξτε τον υπάρχοντα υποψήφιο πρώτα');
        
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('.own-candidate-check:not(:checked)'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Initial binding of tracked events
    bindTrackedEvents();
    
    // Show success message if redirected with success parameter
    if (window.location.search.includes('tracked=success')) {
        $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
          '<i class="fas fa-check-circle me-2"></i> Οι επιλεγμένοι υποψήφιοι προστέθηκαν στην παρακολούθηση.' +
          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
          '</div>').insertAfter('.page-header').delay(3000).fadeOut();
    }
    
    // Auto-submit form when category dropdown changes
    $('#categoryFilter').change(function() {
        $('#searchForm').submit();
    });
});

$('#trackForm').on('submit', function(e) {
    e.preventDefault();
    
    // Check if any applicants are selected
    if ($('.applicant-check:checked').length === 0) {
        // No applicants selected - show alert
        alert('Παρακαλώ επιλέξτε τουλάχιστον έναν υποψήφιο');
        return false;
    }
    
    // Applicants are selected - proceed with form submission
    this.submit();
});
    </script>
</body>

</html>