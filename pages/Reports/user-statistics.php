<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include_once('../../database/db_connect.php');

// Date range (default: last 30 days)
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . ' 23:59:59' : date('Y-m-d H:i:s');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . ' 00:00:00' : date('Y-m-d H:i:s', strtotime('-30 days'));
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$days = $date2->diff($date1)->days;

// Stats cards
$query = "SELECT 
    COUNT(DISTINCT r.fullName) as total_candidates,
    COUNT(DISTINCT CASE WHEN r.points >= 90 THEN r.fullName END) as high_performers,
    COUNT(DISTINCT CASE WHEN r.experience >= 5 THEN r.fullName END) as experienced_candidates,
    AVG(r.points) as avg_points
  FROM rankinglist r
  JOIN categories c ON r.categoryID = c.categoryID
  WHERE r.registrationDate BETWEEN ? AND ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Daily registration stats
$query = "SELECT 
    DATE(r.registrationDate) as entry_date,
    COUNT(*) as count
  FROM rankinglist r
  JOIN categories c ON r.categoryID = c.categoryID
  WHERE r.registrationDate BETWEEN ? AND ?
  GROUP BY DATE(r.registrationDate)
  ORDER BY entry_date ASC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Candidate activity
$query = "SELECT 
    r.fullName,
    r.registrationDate,
    r.points,
    r.experience,
    c.fields,
    c.type
  FROM rankinglist r
  JOIN categories c ON r.categoryID = c.categoryID
  WHERE r.registrationDate BETWEEN ? AND ?
  ORDER BY r.registrationDate DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$candidate_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Statistics Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .content-wrapper { margin-left: 250px; padding: 30px 15px 50px 15px; max-width: 100%; }
        @media (max-width: 990px) { .content-wrapper { margin-left: 0; padding: 15px 5px 50px 5px; } }
        .card-body > canvas { width: 100% !important; height: 320px !important; }
        .pagination-info { color: #6c757d; font-size: 0.95rem; }
        .table-container { overflow-x: auto; }
        .sticky-header th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .search-box { max-width: 350px; margin-bottom: 1rem; }
        .candidate-row { cursor: pointer; transition: background-color 0.2s; }
        .candidate-row:hover { background-color: rgba(0,0,0,0.05); }
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
            <h1 class="page-title">Candidate Entry Report</h1>
            <p class="text-muted">Entries from <?= date('M d, Y', strtotime($start_date)); ?> to <?= date('M d, Y', strtotime($end_date)); ?> (<?= $days; ?> days)</p>
        </div>
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h6>Total Candidates</h6><h3><?= $stats['total_candidates'] ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>High Performers (90+ points)</h6><h3><?= $stats['high_performers'] ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>Experienced (5+ years)</h6><h3><?= $stats['experienced_candidates'] ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h6>Average Points</h6><h3><?= number_format($stats['avg_points'], 1) ?></h3></div></div></div>
        </div>
        <!-- Registration Trend Chart -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Daily Candidate Entries</h5></div>
            <div class="card-body"><div style="height: 400px"><canvas id="registrationChart"></canvas></div></div>
        </div>
        <!-- Candidate Entries Table -->
        <div class="card">
            <div class="card-header"><h5 class="card-title">Candidate Entries</h5></div>
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchTable" class="form-control" placeholder="Search candidates...">
                </div>
                <div class="table-container">
                    <table class="table table-hover" id="candidateTable">
                        <thead class="sticky-header">
                            <tr>
                                <th>Name</th>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Entry Date</th>
                                <th>Points</th>
                                <th>Experience</th>
                                <th>Days Listed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidate_activity as $candidate): 
                                $entry_date = new DateTime($candidate['registrationDate']);
                                $days_listed = $entry_date->diff(new DateTime())->days;
                            ?>
                            <tr class="candidate-row" 
                                data-name="<?= htmlspecialchars($candidate['fullName']) ?>"
                                data-field="<?= htmlspecialchars($candidate['fields']) ?>"
                                data-type="<?= htmlspecialchars($candidate['type']) ?>">
                                <td><?= htmlspecialchars($candidate['fullName']) ?></td>
                                <td><?= htmlspecialchars($candidate['fields']) ?></td>
                                <td><?= htmlspecialchars($candidate['type']) ?></td>
                                <td><?= $entry_date->format('Y-m-d H:i') ?></td>
                                <td><?= number_format($candidate['points'], 1) ?></td>
                                <td><?= number_format($candidate['experience'], 1) ?> years</td>
                                <td><?= $days_listed ?> days</td>
                                <td>
                                    <a href="/WebEngineering/pages/applicant-details.php?name=<?= urlencode($candidate['fullName']) ?>&field=<?= urlencode($candidate['fields']) ?>&type=<?= urlencode($candidate['type']) ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination controls -->
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Registration Trend Chart
new Chart(document.getElementById('registrationChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($row) => date('M d, Y', strtotime($row['entry_date'])), $daily_stats)) ?>,
        datasets: [{
            label: 'New Candidates',
            data: <?= json_encode(array_column($daily_stats, 'count')) ?>,
            fill: false,
            borderColor: '#4e73df',
            tension: 0.1,
            pointRadius: 5,
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, precision: 0 },
                grid: { drawBorder: false }
            },
            x: { grid: { display: false } }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(255,255,255,0.8)',
                titleColor: '#4e73df',
                titleFont: { weight: 'bold' },
                bodyColor: '#4e73df',
                bodyFont: { weight: 'normal' },
                borderColor: '#4e73df',
                borderWidth: 1,
                callbacks: {
                    title: ctx => ctx[0].label,
                    label: ctx => `Candidates: ${ctx.raw}`
                }
            }
        }
    }
});

// Pagination + Search
document.addEventListener('DOMContentLoaded', function() {
    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [];
    const tableRows = Array.from(document.querySelectorAll('#candidateTable tbody .candidate-row'));
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
            const field = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const type = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const date = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            const points = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
            return (
                name.includes(searchTerm) ||
                field.includes(searchTerm) ||
                type.includes(searchTerm) ||
                date.includes(searchTerm) ||
                points.includes(searchTerm)
            );
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
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
            const name = this.dataset.name;
            const field = this.dataset.field;
            const type = this.dataset.type;
            window.location.href = `/WebEngineering/pages/applicant-details.php?name=${encodeURIComponent(name)}&field=${encodeURIComponent(field)}&type=${encodeURIComponent(type)}`;
        });
    });
});
</script>
</body>
</html>