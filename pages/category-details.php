<?php
include_once('../database/db_connect.php');

// Get category ID from URL query parameter
$categoryID = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($categoryID <= 0) {
    header("Location: homepage.php");
    exit();
}

// Get page and limit parameters for pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
if ($page < 1) $page = 1;
if ($limit < 10) $limit = 10;
if ($limit > 100) $limit = 100;

$offset = ($page - 1) * $limit;

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get category details
$stmt = $mysqli->prepare("SELECT * FROM categories WHERE categoryID = ?");
$stmt->bind_param("i", $categoryID);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if (!$category) {
    header("Location: homepage.php");
    exit();
}

// Count total candidates for pagination
$countSql = "SELECT COUNT(*) as total FROM rankinglist WHERE categoryID = ?";
$params = [$categoryID];
$types = "i";

// Add search condition if provided
if (!empty($search)) {
    $countSql .= " AND (fullName LIKE ? OR appNum LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

$stmt = $mysqli->prepare($countSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalCandidates = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalCandidates / $limit);

// Get candidate data with pagination
$sql = "SELECT * FROM rankinglist WHERE categoryID = ?";
if (!empty($search)) {
    $sql .= " AND (fullName LIKE ? OR appNum LIKE ?)";
}
$sql .= " ORDER BY ranking LIMIT ?, ?";

$params[] = $offset;
$params[] = $limit;
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
    
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">
    
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }
        
        .content-wrapper {
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            text-align: center;
        }
        
        .pdf-container {
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include_once('../components/sidebar/sidebar.php'); ?>
    
    <div class="main-content">
        <div class="container content-wrapper">
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
            <!-- PDF viewer section if needed -->
            <?php endif; ?>
            
            <!-- Search form -->
            <div class="mb-3">
                <form method="get" action="" class="d-flex">
                    <input type="hidden" name="id" value="<?php echo $categoryID; ?>">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search by name or application number" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="?id=<?php echo $categoryID; ?>&limit=<?php echo $limit; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Entries per page dropdown -->
            <div class="mb-3">
                <label for="entriesPerPage" class="form-label">Entries per page:</label>
                <select id="entriesPerPage" class="form-select form-select-sm w-auto">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>

            <?php if ($totalPages > 0): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-muted">Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCandidates); ?> of <?php echo $totalCandidates; ?> entries</span>
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=1&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        
                        <!-- Previous page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <!-- Page numbers with consistent display pattern -->
                        <?php 
                        // Always show 5 pages total if possible
                        $totalPagesToShow = 5;
                        
                        // First page is always visible
                        ?>
                        <li class="page-item <?php echo 1 == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=1&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                1
                            </a>
                        </li>
                        
                        <?php
                        // Calculate the range of pages to show
                        $startPage = max(2, $page - 1);
                        $endPage = min($totalPages - 1, $page + 1);
                        
                        // Show ellipsis after page 1 if needed
                        if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        // Show middle pages
                        for ($i = $startPage; $i <= $endPage; $i++): 
                            if ($i > 1 && $i < $totalPages): // Skip first and last page as they're always shown separately
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                        
                        <?php 
                        // Show ellipsis before last page if needed
                        if ($endPage < $totalPages - 1 && $totalPages > 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        // Last page is always visible if there's more than one page
                        if ($totalPages > 1): ?>
                            <li class="page-item <?php echo $totalPages == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <?php echo $totalPages; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        
                        <!-- Last page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
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
                            <th>App Number</th>
                            <th>Birthday</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No candidates found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate['ranking']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['fullName']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['appNum']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['birthdayDate']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['registrationDate']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 0): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <span class="text-muted">Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCandidates); ?> of <?php echo $totalCandidates; ?> entries</span>
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <!-- First page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=1&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        
                        <!-- Previous page -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <!-- Page numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        
                        <!-- Last page -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?id=<?php echo $categoryID; ?>&page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
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
            }
        });
    </script>
</body>
</html>