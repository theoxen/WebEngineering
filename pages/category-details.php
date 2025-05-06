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

// Get current page from URL (default to 1)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Get limit from URL parameter (default to 50)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Validate the limit to only allow 25, 50, or 100
if (!in_array($limit, [25, 50, 100])) {
    $limit = 50;
}

// Calculate offset for SQL pagination
$offset = ($page - 1) * $limit;

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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

// Query to get total number of candidates for pagination
$countSql = "SELECT COUNT(*) as total FROM rankinglist WHERE categoryID = ?";
$countParams = [$categoryID];
$countTypes = "i";

// Add search condition if provided
if (!empty($search)) {
    $countSql .= " AND fullName LIKE ?";
    $countParams[] = "%$search%";
    $countTypes .= "s";
}

$countStmt = $mysqli->prepare($countSql);
$countStmt->bind_param($countTypes, ...$countParams);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalCandidates = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalCandidates / $limit);

// If requested page is greater than total pages, redirect to last page
if ($page > $totalPages && $totalPages > 0) {
    header("Location: category-details.php?id=$categoryID&limit=$limit&page=$totalPages" . (!empty($search) ? "&search=".urlencode($search) : ""));
    exit;
}

// Query to get candidates for the category with pagination
$sql = "SELECT * FROM rankinglist WHERE categoryID = ?";
$params = [$categoryID];
$types = "i";

// Add search condition if provided
if (!empty($search)) {
    $sql .= " AND fullName LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql .= " ORDER BY ranking ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$candidates = $result->fetch_all(MYSQLI_ASSOC);

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
        
        <h1 class="my-4"><?php echo htmlspecialchars($category['fields'] ?? ''); ?></h1>
        <p><?php echo htmlspecialchars($category['type'] ?? '') . ' - ' . htmlspecialchars($category['season'] ?? '') . ' ' . htmlspecialchars($category['year'] ?? ''); ?></p>

        <?php if (!empty($category['file_path'])): ?>
        <!-- <div class="pdf-container">
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
        </div> -->
        <?php else: ?>

        <?php endif; ?>
        
        <!-- Search form -->
        <div class="mb-3">
            <form method="get" action="" class="d-flex">
                <input type="hidden" name="id" value="<?php echo $categoryID; ?>">
                <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by name..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="mb-3 d-flex align-items-center">
            <span class="me-2">Show entries:</span>
            <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto;">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
            </select>
        </div>

        <?php if ($totalPages > 0): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="text-muted">Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCandidates); ?> of <?php echo $totalCandidates; ?> entries</span>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <!-- First page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=1" aria-label="First">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Previous page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" aria-label="Previous">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    
                    <!-- Page numbers -->
                    <?php
                    // Show a range of pages centered around the current page
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=1">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                            <a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=' . $i . '">' . $i . '</a>
                        </li>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next page -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    
                    <!-- Last page -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $totalPages; ?>" aria-label="Last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ranking</th>
                        <th>Full Name</th>
                        <th>Application Number</th>
                        <th>Points</th>
                        <th>Title Date</th>
                        <th>Title Grade</th>
                        <th>Extra Qualifications</th>
                        <th>Experience</th>
                        <th>Army</th>
                        <th>Registration Date</th>
                        <th>Birthday Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($candidates)) {
                        foreach ($candidates as $candidate) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($candidate['ranking']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['fullName']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['appNum']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['points']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['titleDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['titleGrade']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['extraQualifications']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['experience']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['army']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['registrationDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['birthdayDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['notes']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' class='text-center'>Δεν υπάρχουν δεδομένα για αυτή την κατηγορία.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 0): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <span class="text-muted">Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCandidates); ?> of <?php echo $totalCandidates; ?> entries</span>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <!-- First page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=1" aria-label="First">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Previous page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" aria-label="Previous">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    
                    <!-- Page numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=1">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                            <a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=' . $i . '">' . $i . '</a>
                        </li>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?id=' . $categoryID . '&limit=' . $limit . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next page -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    
                    <!-- Last page -->
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>&page=<?php echo $totalPages; ?>" aria-label="Last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const entriesSelect = document.getElementById('entriesPerPage');
            if (entriesSelect) {
                entriesSelect.addEventListener('change', function() {
                    // Get current URL and parameters
                    const url = new URL(window.location.href);
                    // Set the limit parameter
                    url.searchParams.set('limit', this.value);
                    // Reset to page 1 when changing entries per page
                    url.searchParams.set('page', 1);
                    // Redirect to the new URL
                    window.location.href = url.toString();
                });
                
                // Set the dropdown to match the current limit parameter
                const urlParams = new URLSearchParams(window.location.search);
                const currentLimit = urlParams.get('limit');
                if (currentLimit && (currentLimit === '25' || currentLimit === '50' || currentLimit === '100')) {
                    entriesSelect.value = currentLimit;
                }
            }
        });
    </script>
</body>
</html>