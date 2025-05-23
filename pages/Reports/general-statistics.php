<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once('../../database/db_connect.php');

// Enable caching
$cacheFile = sys_get_temp_dir() . '/stats_cache.json';
$cacheExpiry = 300; // 5 minutes

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheExpiry) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    extract($cachedData);
} else {
    // Optimize queries by combining them
    $stats_query = "
        WITH experience_ranges AS (
            SELECT 
                CASE 
                    WHEN experience < 5 THEN '0-5'
                    WHEN experience < 10 THEN '5-10'
                    WHEN experience < 15 THEN '10-15'
                    WHEN experience < 20 THEN '15-20'
                    WHEN experience < 25 THEN '20-25'
                    ELSE '25+'
                END as range_label,
                COUNT(*) as count
            FROM rankinglist
            WHERE experience IS NOT NULL
            GROUP BY range_label
        ),
        points_ranges AS (
            SELECT 
                FLOOR(points) as range_start,
                COUNT(*) as count
            FROM rankinglist
            WHERE points IS NOT NULL
            GROUP BY FLOOR(points)
        )
        SELECT 
            (SELECT COUNT(DISTINCT fullName) FROM rankinglist) as total_candidates,
            AVG(titleGrade) as avg_grade,
            AVG(experience) as avg_experience,
            AVG(points) as avg_points
        FROM rankinglist";

    $result = $mysqli->query($stats_query);
    $overall = $result->fetch_assoc();

    // Simplified category stats query
    $category_stats = $mysqli->query("
        SELECT 
            c.fields,
            c.type, 
            c.season,
            c.year,
            COUNT(DISTINCT r.fullName) as candidate_count,
            ROUND(AVG(r.points), 1) as avg_points,
            ROUND(MAX(r.points), 1) as max_points,
            ROUND(MIN(r.points), 1) as min_points
        FROM categories c
        LEFT JOIN rankinglist r ON c.categoryID = r.categoryID
        GROUP BY c.categoryID
        ORDER BY c.year DESC, c.season DESC
    ")->fetch_all(MYSQLI_ASSOC);

    // Add field type stats
    $field_type_stats = $mysqli->query("
        SELECT 
            c.fields,
            c.type,
            COUNT(DISTINCT r.fullName) as candidate_count
        FROM categories c
        LEFT JOIN rankinglist r ON c.categoryID = r.categoryID
        GROUP BY c.fields, c.type
        ORDER BY c.fields, c.type
    ")->fetch_all(MYSQLI_ASSOC);

    // Cache the results
    $cacheData = [
        'overall' => $overall,
        'category_stats' => $category_stats,
        'field_type_stats' => $field_type_stats
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>General Statistics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <!-- Defer chart.js loading -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px 15px 50px 15px; max-width: 100%; }
        @media (max-width: 990px) { .content-wrapper { margin-left: 0; padding: 15px 5px 50px 5px; } }
        .card-body > canvas { width: 100% !important; height: 320px !important; }
        .pagination-info { color: #6c757d; font-size: 0.95rem; }
        .table-container { overflow-x: auto; }
        .sticky-header th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .search-box { max-width: 350px; margin-bottom: 1rem; }
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

        <div class="page-header mb-4">
            <h1 class="page-title">General Statistics</h1>
            <p class="text-muted">Overview and analysis of all ranking data</p>
        </div>
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h6>Total Candidates</h6><h3><?= round($overall['total_candidates']) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>Average Grade</h6><h3><?= number_format($overall['avg_grade'], 2) ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>Average Experience</h6><h3><?= number_format($overall['avg_experience'], 1) ?> years</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h6>Average Points</h6><h3><?= number_format($overall['avg_points'], 1) ?></h3></div></div></div>
        </div>
        <!-- Charts -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header"><h5 class="mb-0">Points Distribution</h5></div>
                <div class="card-body"><canvas id="pointsChart"></canvas></div></div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header"><h5 class="mb-0">Experience Distribution</h5></div>
                <div class="card-body"><canvas id="experienceChart"></canvas></div></div>
            </div>
            <div class="col-md-12 mb-4">
                <div class="card"><div class="card-header"><h5 class="mb-0">Candidates by Field and Type</h5></div>
                <div class="card-body">
                    <canvas id="fieldTypeChart" height="300"></canvas>
                </div></div>
            </div>
        </div>
        <!-- Category Table -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Category Statistics</h5></div>
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchTable" class="form-control" placeholder="Search categories...">
                </div>
                <div class="table-container">
                    <table class="table table-hover">
                        <thead class="sticky-header">
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Season/Year</th>
                                <th>Candidates</th>
                                <th>Avg Points</th>
                                <th>Max Points</th>
                                <th>Min Points</th>
                            </tr>
                        </thead>
                        <tbody id="categoryStatsBody">
                            <?php foreach ($category_stats as $stat): ?>
                            <tr class="field-row" style="cursor:pointer"
                                data-field="<?= htmlspecialchars($stat['fields']) ?>"
                                data-type="<?= htmlspecialchars($stat['type']) ?>"
                                data-season="<?= htmlspecialchars($stat['season']) ?>"
                                data-year="<?= $stat['year'] ?>">
                                <td><?= htmlspecialchars($stat['fields']) ?></td>
                                <td><?= htmlspecialchars($stat['type']) ?></td>
                                <td><?= htmlspecialchars($stat['season'] . ' ' . $stat['year']) ?></td>
                                <td><?= $stat['candidate_count'] ?></td>
                                <td><?= number_format($stat['avg_points'], 1) ?></td>
                                <td><?= number_format($stat['max_points'], 1) ?></td>
                                <td><?= number_format($stat['min_points'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="pagination-info">
                            Showing <span id="pageStart">1</span> to <span id="pageEnd">10</span> of <span id="totalItems">0</span> entries
                        </div>
                        <ul class="pagination mb-0">
                            <li class="page-item" id="previousPage"><button class="page-link" aria-label="Previous"><span aria-hidden="true">&laquo;</span></button></li>
                            <li class="page-item" id="nextPage"><button class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span></button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal for Category Details -->
        <div class="modal fade" id="fieldDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Field Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6"><div class="card"><div class="card-body"><h6 class="card-subtitle mb-2 text-muted">Points Distribution</h6><canvas id="modalPointsChart"></canvas></div></div></div>
                        <div class="col-md-6"><div class="card"><div class="card-body"><h6 class="card-subtitle mb-2 text-muted">Experience Distribution</h6><canvas id="modalExperienceChart"></canvas></div></div></div>
                    </div>
                    <div class="row"><div class="col-12">
                        <table class="table table-bordered">
                            <thead><tr><th>Metric</th><th>Value</th></tr></thead>
                            <tbody id="fieldStats"></tbody>
                        </table>
                    </div></div>
                </div>
            </div></div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lazy load charts when they become visible
    const observerOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    };

    const chartObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const chartId = entry.target.id;
                initializeChart(chartId);
                chartObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe chart canvases
    document.querySelectorAll('canvas').forEach(canvas => {
        chartObserver.observe(canvas);
    });

    function initializeChart(chartId) {
        // Fetch chart data via AJAX when needed
        fetch(`get_chart_data.php?chart=${chartId}`)
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById(chartId).getContext('2d');
                switch(chartId) {
                    case 'pointsChart':
                        new Chart(ctx, {
                            type: 'line',
                            data: data.chartData,
                            options: {
                                responsive: true,
                                animation: false, // Disable animations for faster rendering
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { maxTicksLimit: 5 }
                                    }
                                }
                            }
                        });
                        break;
                    case 'experienceChart':
                        new Chart(ctx, {
                            type: 'bar',
                            data: data.chartData,
                            options: {
                                responsive: true,
                                animation: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `Candidates: ${context.raw}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Candidates'
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            callback: function(value) {
                                                const label = this.getLabelForValue(value);
                                                return label.length > 30 ? label.substr(0, 27) + '...' : label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        break;
                    case 'fieldTypeChart':
                        new Chart(ctx, {
                            type: 'bar',
                            data: data.chartData,
                            options: {
                                responsive: true,
                                animation: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `Candidates: ${context.raw}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Number of Candidates'
                                        }
                                    },
                                    y: {
                                        ticks: {
                                            callback: function(value) {
                                                const label = this.getLabelForValue(value);
                                                return label.length > 30 ? label.substr(0, 27) + '...' : label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        break;
                }
            });
    }

    // Pagination + Search
    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [];
    const tableRows = Array.from(document.querySelectorAll('.field-row'));
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
        filteredRows = tableRows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
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
    // Modal logic
    let modalPointsChart = null, modalExperienceChart = null;
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
            const field = this.dataset.field, type = this.dataset.type, season = this.dataset.season, year = this.dataset.year;
            const modal = new bootstrap.Modal(document.getElementById('fieldDetailsModal'));
            modal.show();
            document.querySelector('.modal-title').textContent = `${field} (${type}) - ${season} ${year}`;
            fetch(`get_field_stats.php?field=${encodeURIComponent(field)}&type=${encodeURIComponent(type)}&season=${encodeURIComponent(season)}&year=${encodeURIComponent(year)}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('fieldStats').innerHTML =
                        Object.entries(data.stats).map(([k, v]) => `<tr><td>${k}</td><td>${v}</td></tr>`).join('');
                    if (modalPointsChart) modalPointsChart.destroy();
                    if (modalExperienceChart) modalExperienceChart.destroy();
                    modalPointsChart = new Chart(document.getElementById('modalPointsChart'), {
                        type: 'bar', // Changed from 'pie' to 'bar'
                        data: {
                            labels: data.pointsDistribution.labels,
                            datasets: [{
                                label: 'Number of Candidates',
                                data: data.pointsDistribution.data,
                                backgroundColor: data.pointsDistribution.colors
                            }]
                        },
                        options: {
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: { display: true, text: 'Number of Candidates' }
                                },
                                x: {
                                    title: { display: true, text: 'Points Range' }
                                }
                            }
                        }
                    });
                    modalExperienceChart = new Chart(document.getElementById('modalExperienceChart'), {
                        type: 'bar',
                        data: {
                            labels: data.experienceDistribution.labels,
                            datasets: [{ label: 'Number of Candidates', data: data.experienceDistribution.data, backgroundColor: '#36b9cc' }]
                        },
                        options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                    });
                })
                .catch(() => {
                    document.getElementById('fieldStats').innerHTML = '<tr><td colspan="2" class="text-danger">Error loading statistics</td></tr>';
                });
        });
    });
});
</script>
</body>
</html>