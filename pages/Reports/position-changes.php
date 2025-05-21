<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once('../../database/db_connect.php');

// Get parameters from select form
$field = $_GET['field'] ?? '';
$type = $_GET['type'] ?? '';

// Find the latest two semesters for this field/type
$stmt = $mysqli->prepare(
    "SELECT year, season, categoryID 
     FROM categories 
     WHERE fields = ? AND type = ?
     ORDER BY year DESC, FIELD(season, 'Summer', 'Winter') DESC 
     LIMIT 2"
);
$stmt->bind_param("ss", $field, $type);
$stmt->execute();
$result = $stmt->get_result();
$semesters = $result->fetch_all(MYSQLI_ASSOC);

if (count($semesters) < 2) {
    $error = "Not enough data for comparison - need at least two semesters.";
    // Set default values
    $current = ['year' => null, 'season' => null, 'categoryID' => null];
    $previous = ['year' => null, 'season' => null, 'categoryID' => null];
} else {
    $current = $semesters[0];
    $previous = $semesters[1];
}

// Get all unique candidate names in either semester
$candidates = [];
if (!empty($current['categoryID']) && !empty($previous['categoryID'])) {
    $sql = "SELECT DISTINCT fullName 
            FROM rankinglist 
            WHERE categoryID IN (?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $current['categoryID'], $previous['categoryID']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row['fullName'];
    }
}

// Build comparison data
$rankings = [];
foreach ($candidates as $name) {
    // Get current semester data
    $stmt = $mysqli->prepare(
        "SELECT ranking, points, experience, titleGrade 
         FROM rankinglist 
         WHERE categoryID = ? AND fullName = ? 
         LIMIT 1"
    );
    $stmt->bind_param("is", $current['categoryID'], $name);
    $stmt->execute();
    $current_data = $stmt->get_result()->fetch_assoc();

    // Get previous semester data
    $stmt->bind_param("is", $previous['categoryID'], $name);
    $stmt->execute();
    $previous_data = $stmt->get_result()->fetch_assoc();

    $rankings[] = [
        'name' => $name,
        'current_rank' => $current_data['ranking'] ?? null,
        'prev_rank' => $previous_data['ranking'] ?? null,
        'current_points' => $current_data['points'] ?? null,
        'prev_points' => $previous_data['points'] ?? null,
        'current_experience' => $current_data['experience'] ?? null,
        'prev_experience' => $previous_data['experience'] ?? null,
        'current_grade' => $current_data['titleGrade'] ?? null,
        'prev_grade' => $previous_data['titleGrade'] ?? null
    ];
}

// Calculate statistics
$stats = [
    'improved' => 0,
    'declined' => 0,
    'same' => 0,
    'missing' => 0,
    'total' => count($rankings),
    'avg_point_change' => 0,
    'max_improvement' => 0,
    'max_decline' => 0
];

$point_changes = [];
foreach ($rankings as $rank) {
    if (is_null($rank['current_rank'])) continue;
    
    if (is_null($rank['prev_rank'])) {
        $stats['missing']++;
    } else {
        $rank_diff = $rank['prev_rank'] - $rank['current_rank'];
        if ($rank_diff > 0) $stats['improved']++;
        elseif ($rank_diff < 0) $stats['declined']++;
        else $stats['same']++;

        if (!is_null($rank['current_points']) && !is_null($rank['prev_points'])) {
            $point_diff = $rank['current_points'] - $rank['prev_points'];
            $point_changes[] = $point_diff;
            $stats['max_improvement'] = max($stats['max_improvement'], $point_diff);
            $stats['max_decline'] = min($stats['max_decline'], $point_diff);
        }
    }
}

$stats['avg_point_change'] = !empty($point_changes) ? 
    array_sum($point_changes) / count($point_changes) : 0;

// Sort rankings by current rank
usort($rankings, function($a, $b) {
    if (is_null($a['current_rank'])) return 1;
    if (is_null($b['current_rank'])) return -1;
    return $a['current_rank'] - $b['current_rank'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Position Changes Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px; }
        @media (max-width: 767.98px) { .content-wrapper { margin-left: 0; padding: 15px; width: 100%; } }
        .stats-card { border-left: 4px solid; margin-bottom: 1rem; }
        .improved { color: #1cc88a; }
        .declined { color: #e74a3b; }
        .same { color: #858796; }
        .missing { color: #f6c23e; }
        .stats-improved { border-left-color: #1cc88a; }
        .stats-declined { border-left-color: #e74a3b; }
        .stats-same { border-left-color: #858796; }
        .stats-missing { border-left-color: #f6c23e; }
        .search-box { margin-bottom: 20px; }
        .sticky-header th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .candidate-row { cursor: pointer; }
        .pagination-info { 
            color: #6c757d; 
            font-size: 0.95rem; 
        }
        .page-link { 
            color: #4e73df; 
            border-radius: 0.2rem; 
            margin: 0 2px; 
        }
        .page-link:hover { 
            color: #224abe; 
            background-color: #eaecf4; 
        }
        .page-item.disabled .page-link { 
            color: #858796; 
        }
        .page-item.active .page-link { 
            background-color: #4e73df; 
            border-color: #4e73df; 
        }
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
        }
        
        .btn-secondary:hover {
            background-color: #717384;
            border-color: #717384;
            color: white;
        }
    </style>
</head>
<body>
<?php include_once('../../components/sidebar/sidebar.php'); ?>
<div class="content-wrapper">
    <div class="container">
        <!-- Add back button -->
        <div class="mb-3">
            <a href="select-report.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
        <div class="page-header mb-4">
            <h1 class="page-title">Position Changes Report</h1>
            <p class="text-muted">
                Comparing <?= htmlspecialchars($current['season'] . ' ' . $current['year']) ?> 
                to <?= htmlspecialchars($previous['season'] . ' ' . $previous['year']) ?>
            </p>
            <div class="badge bg-primary mb-3">
                Field: <?= htmlspecialchars($field) ?> | Type: <?= htmlspecialchars($type) ?>
            </div>
        </div>
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card stats-improved">
                    <div class="card-body">
                        <h5>Improved Positions</h5>
                        <h2><?= $stats['improved'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card stats-declined">
                    <div class="card-body">
                        <h5>Declined Positions</h5>
                        <h2><?= $stats['declined'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card stats-same">
                    <div class="card-body">
                        <h5>Unchanged</h5>
                        <h2><?= $stats['same'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card stats-missing">
                    <div class="card-body">
                        <h5>No Previous Entry</h5>
                        <h2><?= $stats['missing'] ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <!-- Rankings Table -->
        <div class="card">
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchTable" class="form-control" placeholder="Search candidates...">
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead class="sticky-header">
                            <tr>
                                <th>Name</th>
                                <th>Current Rank</th>
                                <th>Previous Rank</th>
                                <th>Change</th>
                                <th>Current Points</th>
                                <th>Previous Points</th>
                                <th>Points Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rankings)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No data found for the selected or previous semester for this field/type.
                                    </td>
                                </tr>
                            <?php else: foreach ($rankings as $rank): 
                                $rank_diff = (!is_null($rank['current_rank']) && !is_null($rank['prev_rank'])) ? ($rank['prev_rank'] - $rank['current_rank']) : 'N/A';
                                $points_diff = (!is_null($rank['current_points']) && !is_null($rank['prev_points'])) ? ($rank['current_points'] - $rank['prev_points']) : 'N/A';
                                if ($rank_diff === 'N/A') {
                                    $class = 'missing';
                                    $icon = '×';
                                } else {
                                    $class = $rank_diff > 0 ? 'improved' : ($rank_diff < 0 ? 'declined' : 'same');
                                    $icon = $rank_diff > 0 ? '↑' : ($rank_diff < 0 ? '↓' : '→');
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($rank['name']) ?></td>
                                <td><?= $rank['current_rank'] ?? 'N/A'; ?></td>
                                <td><?= $rank['prev_rank'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if ($rank_diff !== 'N/A'): ?>
                                        <span class="<?= $class; ?>">
                                            <?= $icon; ?> <?= abs($rank_diff); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="missing">×</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !is_null($rank['current_points']) ? number_format($rank['current_points'], 1) : 'N/A'; ?></td>
                                <td><?= !is_null($rank['prev_points']) ? number_format($rank['prev_points'], 1) : 'N/A'; ?></td>
                                <td>
                                    <?php if ($points_diff !== 'N/A'): ?>
                                        <span class="<?= $class; ?>">
                                            <?= sprintf('%+.1f', $points_diff); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="missing">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="pagination-info">
                        Showing <span id="pageStart">1</span> to <span id="pageEnd">10</span> of <span id="totalItems">0</span> entries
                    </div>
                    <ul class="pagination mb-0">
                        <li class="page-item" id="previousPage">
                            <button class="page-link" aria-label="Previous"><span aria-hidden="true">&laquo;</span></button>
                        </li>
                        <li class="page-item" id="nextPage">
                            <button class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span></button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [];
    const tableRows = Array.from(document.querySelectorAll('tbody tr'));
    const searchInput = document.getElementById('searchTable');
    const pageStart = document.getElementById('pageStart');
    const pageEnd = document.getElementById('pageEnd');
    const totalItems = document.getElementById('totalItems');
    const previousPage = document.getElementById('previousPage');
    const nextPage = document.getElementById('nextPage');

    function updatePagination() {
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        if (currentPage > totalPages) currentPage = 1;
        const start = (currentPage - 1) * rowsPerPage;
        const end = Math.min(start + rowsPerPage, totalRows);
        
        pageStart.textContent = totalRows ? start + 1 : 0;
        pageEnd.textContent = end;
        totalItems.textContent = totalRows;
        
        previousPage.classList.toggle('disabled', currentPage === 1);
        nextPage.classList.toggle('disabled', currentPage === totalPages || totalRows === 0);
        
        tableRows.forEach(row => row.style.display = 'none');
        filteredRows.slice(start, end).forEach(row => row.style.display = '');
    }

    filteredRows = tableRows;
    updatePagination();

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        filteredRows = tableRows.filter(row => {
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const currentRank = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const prevRank = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            return name.includes(searchTerm) || 
                   currentRank.includes(searchTerm) || 
                   prevRank.includes(searchTerm);
        });
        currentPage = 1;
        updatePagination();
    });

    previousPage.addEventListener('click', function() {
        if (currentPage > 1) { 
            currentPage--; 
            updatePagination(); 
        }
    });

    nextPage.addEventListener('click', function() {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) { 
            currentPage++; 
            updatePagination(); 
        }
    });
});
</script>
</body>
</html>