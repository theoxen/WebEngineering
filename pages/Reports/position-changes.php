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
$search = $_GET['search'] ?? '';

// AJAX handler for paginated data
if (isset($_GET['ajax'])) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 25;
    $offset = ($page - 1) * $limit;
    
    // Get the latest two semesters for comparison
    $stmt = $mysqli->prepare(
        "SELECT categoryID, year, season 
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
        echo json_encode(['error' => 'Not enough data for comparison']);
        exit;
    }
    
    $current = $semesters[0];
    $previous = $semesters[1];
    
    // Get paginated rankings with search
    $searchClause = $search ? "AND r1.fullName LIKE CONCAT('%', ?, '%')" : "";
    $sql = "SELECT 
                r1.fullName,
                r1.ranking as current_rank,
                r1.points as current_points,
                r1.experience as current_exp,
                r2.ranking as prev_rank,
                r2.points as prev_points,
                r2.experience as prev_exp,
                (SELECT COUNT(*) FROM rankinglist WHERE categoryID = r1.categoryID) as total_count
            FROM rankinglist r1
            LEFT JOIN rankinglist r2 
                ON r1.fullName = r2.fullName 
                AND r2.categoryID = ?
            WHERE r1.categoryID = ? $searchClause
            ORDER BY r1.ranking
            LIMIT ? OFFSET ?";
    
    $types = "ii" . ($search ? "s" : "") . "ii";
    $params = [$previous['categoryID'], $current['categoryID']];
    if ($search) $params[] = $search;
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [
        'rows' => [],
        'total' => 0,
        'current_semester' => $current,
        'previous_semester' => $previous
    ];
    
    while ($row = $result->fetch_assoc()) {
        $data['total'] = $row['total_count'];
        unset($row['total_count']);
        $data['rows'][] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Initial page load - only get basic data for statistics
$stmt = $mysqli->prepare(
    "SELECT categoryID, year, season 
     FROM categories 
     WHERE fields = ? AND type = ? 
     ORDER BY year DESC, FIELD(season, 'Summer', 'Winter') DESC 
     LIMIT 2"
);
$stmt->bind_param("ss", $field, $type);
$stmt->execute();
$semesters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (count($semesters) < 2) {
    $error = "Not enough data for comparison - need at least two semesters.";
    $current = $previous = ['year' => null, 'season' => null];
} else {
    $current = $semesters[0];
    $previous = $semesters[1];
}
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
        @media (max-width: 767.98px) { .content-wrapper { margin-left: 0; } }
        .stats-card { border-left: 4px solid; }
        .improved { color: #1cc88a; }
        .declined { color: #e74a3b; }
        .same { color: #858796; }
        .missing { color: #f6c23e; }
        .loading { opacity: 0.5; pointer-events: none; }
        .sticky-header th { position: sticky; top: 0; background: white; z-index: 1; }
        .search-box { position: relative; }
        .search-box .spinner-border { 
            position: absolute; 
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }
        .table-responsive { min-height: 400px; }
    </style>
</head>
<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="mb-3">
                <a href="select-report.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <div class="page-header mb-4">
                <h1>Position Changes Report</h1>
                <?php if (isset($current['year'])): ?>
                <p class="text-muted">
                    Comparing <?= htmlspecialchars($current['season'] . ' ' . $current['year']) ?> 
                    to <?= htmlspecialchars($previous['season'] . ' ' . $previous['year']) ?>
                </p>
                <div class="badge bg-primary">
                    Field: <?= htmlspecialchars($field) ?> | Type: <?= htmlspecialchars($type) ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="search-box mb-3">
                            <input type="text" id="searchInput" class="form-control" 
                                   placeholder="Search candidates...">
                            <div class="spinner-border text-primary spinner-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
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
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="7" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="pagination-info">
                                Showing <span id="pageStart">0</span> to <span id="pageEnd">0</span> 
                                of <span id="totalItems">0</span> entries
                            </div>
                            <ul class="pagination mb-0">
                                <li class="page-item disabled" id="prevPage">
                                    <button class="page-link">&laquo;</button>
                                </li>
                                <li class="page-item disabled" id="nextPage">
                                    <button class="page-link">&raquo;</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const pagination = {
            page: 1,
            limit: 25,
            total: 0
        };

        const elements = {
            search: document.getElementById('searchInput'),
            table: document.getElementById('tableBody'),
            spinner: document.querySelector('.spinner-border'),
            prevBtn: document.getElementById('prevPage'),
            nextBtn: document.getElementById('nextPage'),
            pageStart: document.getElementById('pageStart'),
            pageEnd: document.getElementById('pageEnd'),
            totalItems: document.getElementById('totalItems')
        };

        let loading = false;
        let searchTimeout;

        async function loadData() {
            if (loading) return;
            loading = true;
            
            elements.spinner.style.display = 'block';
            elements.table.classList.add('loading');

            try {
                const params = new URLSearchParams(window.location.search);
                params.append('ajax', '1');
                params.append('page', pagination.page);
                params.append('search', elements.search.value);

                const response = await fetch(`${window.location.pathname}?${params}`);
                const data = await response.json();

                if (data.error) {
                    elements.table.innerHTML = `
                        <tr><td colspan="7" class="text-center text-danger">
                            ${data.error}
                        </td></tr>`;
                    return;
                }

                pagination.total = data.total;
                updateTable(data.rows);
                updatePagination();

            } catch (error) {
                console.error('Error:', error);
                elements.table.innerHTML = `
                    <tr><td colspan="7" class="text-center text-danger">
                        Error loading data. Please try again.
                    </td></tr>`;
            } finally {
                elements.spinner.style.display = 'none';
                elements.table.classList.remove('loading');
                loading = false;
            }
        }

        function updateTable(rows) {
            elements.table.innerHTML = rows.map(row => {
                const rankDiff = row.current_rank && row.prev_rank ? 
                    row.prev_rank - row.current_rank : 'N/A';
                const pointsDiff = row.current_points && row.prev_points ? 
                    row.current_points - row.prev_points : 'N/A';

                let changeClass, changeIcon;
                if (rankDiff === 'N/A') {
                    changeClass = 'missing';
                    changeIcon = '×';
                } else {
                    changeClass = rankDiff > 0 ? 'improved' : (rankDiff < 0 ? 'declined' : 'same');
                    changeIcon = rankDiff > 0 ? '↑' : (rankDiff < 0 ? '↓' : '→');
                }

                return `
                    <tr>
                        <td>${escapeHtml(row.fullName)}</td>
                        <td>${row.current_rank || 'N/A'}</td>
                        <td>${row.prev_rank || 'N/A'}</td>
                        <td>
                            <span class="${changeClass}">
                                ${rankDiff !== 'N/A' ? `${changeIcon} ${Math.abs(rankDiff)}` : '×'}
                            </span>
                        </td>
                        <td>${row.current_points ? Number(row.current_points).toFixed(1) : 'N/A'}</td>
                        <td>${row.prev_points ? Number(row.prev_points).toFixed(1) : 'N/A'}</td>
                        <td>
                            <span class="${changeClass}">
                                ${pointsDiff !== 'N/A' ? 
                                    (pointsDiff > 0 ? '+' : '') + Number(pointsDiff).toFixed(1) : 
                                    'N/A'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updatePagination() {
            const start = (pagination.page - 1) * pagination.limit + 1;
            const end = Math.min(start + pagination.limit - 1, pagination.total);
            
            elements.pageStart.textContent = start;
            elements.pageEnd.textContent = end;
            elements.totalItems.textContent = pagination.total;
            
            elements.prevBtn.classList.toggle('disabled', pagination.page === 1);
            elements.nextBtn.classList.toggle('disabled', end >= pagination.total);
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Event Listeners
        elements.prevBtn.addEventListener('click', () => {
            if (pagination.page > 1) {
                pagination.page--;
                loadData();
            }
        });

        elements.nextBtn.addEventListener('click', () => {
            if ((pagination.page * pagination.limit) < pagination.total) {
                pagination.page++;
                loadData();
            }
        });

        elements.search.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                pagination.page = 1;
                loadData();
            }, 300);
        });

        // Initial load
        loadData();
    </script>
</body>
</html>