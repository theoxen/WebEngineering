<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once('../../database/db_connect.php');

// Get tracked candidates and stats
$query = "SELECT 
    t.candidateFullName,
    t.isOwnCandidate,
    r_current.ranking as current_rank,
    r_previous.ranking as previous_rank,
    r_current.points as current_points,
    r_previous.points as previous_points,
    r_current.experience,
    c_current.fields,
    c_current.type,
    c_current.season,
    c_current.year
  FROM trackings t
  LEFT JOIN (
      SELECT r1.*, c1.year, c1.season
      FROM rankinglist r1
      JOIN categories c1 ON r1.categoryID = c1.categoryID
      WHERE CONCAT(c1.year, c1.season) = (
          SELECT MAX(CONCAT(c2.year, c2.season))
          FROM rankinglist r2
          JOIN categories c2 ON r2.categoryID = c2.categoryID
          WHERE r2.fullName = r1.fullName
      )
  ) r_current ON t.candidateFullName = r_current.fullName
  LEFT JOIN categories c_current ON r_current.categoryID = c_current.categoryID
  LEFT JOIN (
      SELECT r1.*, c1.year, c1.season
      FROM rankinglist r1
      JOIN categories c1 ON r1.categoryID = c1.categoryID
      WHERE CONCAT(c1.year, c1.season) = (
          SELECT MAX(CONCAT(c2.year, c2.season))
          FROM rankinglist r2
          JOIN categories c2 ON r2.categoryID = c2.categoryID
          WHERE r2.fullName = r1.fullName
          AND CONCAT(c2.year, c2.season) < (
              SELECT MAX(CONCAT(c3.year, c3.season))
              FROM rankinglist r3
              JOIN categories c3 ON r3.categoryID = c3.categoryID
              WHERE r3.fullName = r1.fullName
          )
      )
  ) r_previous ON t.candidateFullName = r_previous.fullName
  WHERE t.userID = ?
  ORDER BY c_current.year DESC, c_current.season DESC, r_current.ranking ASC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tracked_candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$stats_query = "SELECT 
    COUNT(DISTINCT t.candidateFullName) as total_tracked,
    SUM(CASE WHEN t.isOwnCandidate = 1 THEN 1 ELSE 0 END) as own_candidates,
    COUNT(DISTINCT c.fields) as unique_fields,
    ROUND(AVG(r.points), 1) as avg_points,
    ROUND(AVG(r.experience), 1) as avg_experience
FROM trackings t
LEFT JOIN rankinglist r ON t.candidateFullName = r.fullName
LEFT JOIN categories c ON r.categoryID = c.categoryID
WHERE t.userID = ?
GROUP BY t.userID";
$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
if (!$stats) {
    $stats = [
        'total_tracked' => 0,
        'own_candidates' => 0,
        'unique_fields' => 0,
        'avg_points' => 0,
        'avg_experience' => 0
    ];
}

// Get seasonal progression data for charts
$trends_query = "SELECT 
    t.candidateFullName,
    r.points,
    r.ranking,
    c.season,
    c.year,
    t.isOwnCandidate
FROM trackings t
JOIN rankinglist r ON t.candidateFullName = r.fullName
JOIN categories c ON r.categoryID = c.categoryID
WHERE t.userID = ?
ORDER BY c.year ASC, 
    CASE c.season 
        WHEN 'Winter' THEN 1 
        WHEN 'Summer' THEN 2 
    END ASC";
$stmt = $mysqli->prepare($trends_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$trends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare chart data
$chart_data = [];
foreach ($trends as $trend) {
    $period = $trend['season'] . ' ' . $trend['year'];
    if (!isset($chart_data[$trend['candidateFullName']])) {
        $chart_data[$trend['candidateFullName']] = [
            'points' => [],
            'ranking' => [],
            'periods' => []
        ];
    }
    $chart_data[$trend['candidateFullName']]['points'][] = $trend['points'];
    $chart_data[$trend['candidateFullName']]['ranking'][] = $trend['ranking'];
    $chart_data[$trend['candidateFullName']]['periods'][] = $period;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <style>
        .content-wrapper { padding: 20px; margin-left: 240px; }
        @media (max-width: 768px) { .content-wrapper { margin-left: 0; padding: 15px; } }
        .table { font-size: 0.9rem; margin-bottom: 0; }
        .table th { font-weight: 600; border-top: none; }
        .table td { vertical-align: middle; }
        .badge { font-weight: 500; padding: 0.5em 0.75em; }
        .own-candidate { background-color: rgba(28, 200, 138, 0.1); }
        .tracked-candidate { background-color: rgba(78, 115, 223, 0.1); }
        .ranking-change { font-size: 0.8rem; font-weight: 500; }
        .search-box { max-width: 300px; margin-bottom: 1rem; }
        .table-container { overflow-x: auto; }
        .sticky-header th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .pagination-info { color: #6c757d; font-size: 0.875rem; }
        .page-link { color: #4e73df; border-radius: 0.2rem; margin: 0 2px; }
        .page-link:hover { color: #224abe; background-color: #eaecf4; }
        .page-item.disabled .page-link { color: #858796; }
        .page-item.active .page-link { background-color: #4e73df; border-color: #4e73df; }
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
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

        <!-- Existing page header -->
        <div class="page-header mb-4">
            <h1 class="page-title">My Tracking Report</h1>
            <p class="text-muted">Monitoring <?= $stats['total_tracked'] ?> candidates across <?= $stats['unique_fields'] ?> fields</p>
        </div>
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total Tracked</h6>
                            <h3 class="mb-0"><?= $stats['total_tracked'] ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Own Candidates</h6>
                            <h3 class="mb-0"><?= $stats['own_candidates'] ?></h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Fields Tracked</h6>
                            <h3 class="mb-0"><?= $stats['unique_fields'] ?></h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Performance Graphs -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Points Trend</h5></div>
                    <div class="card-body"><canvas id="pointsTrendChart" height="300"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Ranking Changes</h5></div>
                    <div class="card-body"><canvas id="rankingChart" height="300"></canvas></div>
                </div>
            </div>
        </div>
        <!-- Tracked Candidates Table -->
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Tracked Candidates</h5></div>
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchTable" class="form-control" placeholder="Search candidates...">
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead class="sticky-header">
                            <tr>
                                <th>Name</th>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Season/Year</th>
                                <th>Ranking</th>
                                <th>Points</th>
                                <th>Experience</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tracked_candidates as $candidate): ?>
                            <tr class="<?= $candidate['isOwnCandidate'] ? 'own-candidate' : 'tracked-candidate'; ?>"
                                data-name="<?= htmlspecialchars($candidate['candidateFullName']) ?>"
                                data-field="<?= htmlspecialchars($candidate['fields']) ?>"
                                data-type="<?= htmlspecialchars($candidate['type']) ?>">
                                <td><?= htmlspecialchars($candidate['candidateFullName']) ?></td>
                                <td><?= htmlspecialchars($candidate['fields'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($candidate['type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(($candidate['season'] ?? 'N/A') . ' ' . ($candidate['year'] ?? '')) ?></td>
                                <td>
                                    <?php 
                                    $current_rank = $candidate['current_rank'] ?? 'N/A';
                                    $previous_rank = $candidate['previous_rank'] ?? 'N/A';
                                    $rank_diff = ($current_rank !== 'N/A' && $previous_rank !== 'N/A') ? $previous_rank - $current_rank : null;
                                    if ($rank_diff !== null) {
                                        $icon = $rank_diff > 0 ? '↑' : ($rank_diff < 0 ? '↓' : '→');
                                        $color = $rank_diff > 0 ? 'text-success' : ($rank_diff < 0 ? 'text-danger' : 'text-muted');
                                        echo $current_rank . ' <span class="' . $color . '">' . $icon . ' ' . ($rank_diff != 0 ? abs($rank_diff) : '') . '</span>';
                                    } else {
                                        echo $current_rank;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $current_points = $candidate['current_points'] ?? 'N/A';
                                    $previous_points = $candidate['previous_points'] ?? 'N/A';
                                    if ($current_points !== 'N/A') {
                                        $points_diff = ($previous_points !== 'N/A') ? $current_points - $previous_points : null;
                                        echo number_format($current_points, 1);
                                        if ($points_diff !== null) {
                                            $color = $points_diff >= 0 ? 'text-success' : 'text-danger';
                                            echo ' <span class="' . $color . '">(' . ($points_diff > 0 ? '+' : '') . number_format($points_diff, 1) . ')</span>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?= $candidate['experience'] ? number_format($candidate['experience'], 1) . ' years' : 'N/A' ?></td>
                                <td>
                                    <span class="badge <?= $candidate['isOwnCandidate'] ? 'bg-success' : 'bg-primary' ?>">
                                        <?= $candidate['isOwnCandidate'] ? 'Own Candidate' : 'Tracking' ?>
                                    </span>
                                </td>

                            </tr>
                            <?php endforeach; ?>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart colors
    function generateColors(n) {
        const colors = [];
        for(let i = 0; i < n; i++) {
            colors.push(`hsl(${(i * 360/n) % 360}, 70%, 50%)`);
        }
        return colors;
    }
    const chartData = <?php echo json_encode($chart_data); ?>;
    const periods = [...new Set([].concat(...Object.values(chartData).map(d => d.periods)))];
    const candidates = Object.keys(chartData);
    const colors = generateColors(candidates.length);

    // Points Trend Chart
    new Chart(document.getElementById('pointsTrendChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: candidates.map((name, index) => ({
                label: name,
                data: chartData[name].points.map(p => p || null), // Handle missing points
                borderColor: colors[index],
                backgroundColor: colors[index] + '20', // Add slight transparency
                fill: false,
                tension: 0.1,
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { 
                mode: 'nearest',
                intersect: false,
                axis: 'x'
            },
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { 
                        boxWidth: 12,
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            const points = context.parsed.y;
                            if (points === null) return `${context.dataset.label}: No data`;
                            return `${context.dataset.label}: ${points.toFixed(1)} points`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { 
                        maxRotation: 45,
                        minRotation: 45,
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    min: 0,
                    suggestedMax: 25,
                    title: { 
                        display: true,
                        text: 'Points',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });

    // Ranking Changes Chart
    new Chart(document.getElementById('rankingChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: candidates.map((name, index) => ({
                label: name,
                data: chartData[name].ranking,
                borderColor: colors[index],
                backgroundColor: colors[index],
                fill: false,
                tension: 0.1,
                borderWidth: 2,
                pointRadius: 4
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: Rank ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 45, minRotation: 45 }
                },
                y: {
                    beginAtZero: true,
                    title: { 
                        display: true, 
                        text: 'Ranking' 
                    },
                    ticks: { 
                        stepSize: 1,
                        precision: 0 // Add this to ensure whole numbers
                    }
                }
            }
        }
    });

    // Pagination + Search
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
            const field = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const type = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            return name.includes(searchTerm) || field.includes(searchTerm) || type.includes(searchTerm);
        });
        currentPage = 1;
        updatePagination();
    });
    previousPage.addEventListener('click', function() {
        if (currentPage > 1) { currentPage--; updatePagination(); }
    });
    nextPage.addEventListener('click', function() {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) { currentPage++; updatePagination(); }
    });

});
</script>
</body>
</html>