<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');

// Replace the existing query with this enhanced version
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

// Get seasonal progression data
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

// Process data for charts
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <style>
        .own-candidate {
            background-color: rgba(28, 200, 138, 0.1);
        }
        .tracked-candidate {
            background-color: rgba(78, 115, 223, 0.1);
        }
        .ranking-change {
            font-size: 0.875em;
            margin-left: 0.5em;
        }
        .text-success {
            color: #1cc88a !important;
        }
        .text-danger {
            color: #e74a3b !important;
        }
        .text-muted {
            color: #858796 !important;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .table-container {
            max-height: 600px;
            overflow-y: auto;
        }
        .sticky-header th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="page-header mb-4">
                <h1 class="page-title">My Tracking Report</h1>
                <p class="text-muted">Monitoring <?php echo $stats['total_tracked']; ?> candidates across <?php echo $stats['unique_fields']; ?> fields</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>Total Tracked</h6>
                            <h3><?php echo $stats['total_tracked']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Own Candidates</h6>
                            <h3><?php echo $stats['own_candidates']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Fields Tracked</h6>
                            <h3><?php echo $stats['unique_fields']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Graphs -->
            <div class="row mb-4">
                <!-- Points Trend -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Points Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="pointsTrendChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Ranking Changes -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ranking Changes</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="rankingChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracked Candidates Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tracked Candidates</h5>
                </div>
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
                                <tr class="<?php echo $candidate['isOwnCandidate'] ? 'own-candidate' : 'tracked-candidate'; ?>"
    data-name="<?php echo htmlspecialchars($candidate['candidateFullName']); ?>"
    data-field="<?php echo htmlspecialchars($candidate['fields']); ?>"
    data-type="<?php echo htmlspecialchars($candidate['type']); ?>">
    <td>
        <a href="/WebEngineering/pages/rankingList.php?field=<?php echo urlencode($candidate['fields']); ?>&type=<?php echo urlencode($candidate['type']); ?>&season=<?php echo urlencode($candidate['season']); ?>&year=<?php echo urlencode($candidate['year']); ?>#<?php echo urlencode($candidate['candidateFullName']); ?>" 
           class="text-primary text-decoration-none">
            <?php echo htmlspecialchars($candidate['candidateFullName']); ?>
        </a>
    </td>
    <td><?php echo htmlspecialchars($candidate['fields'] ?? 'N/A'); ?></td>
    <td><?php echo htmlspecialchars($candidate['type'] ?? 'N/A'); ?></td>
    <td><?php echo htmlspecialchars(($candidate['season'] ?? 'N/A') . ' ' . ($candidate['year'] ?? '')); ?></td>
    <td>
        <?php 
        $current_rank = $candidate['current_rank'] ?? 'N/A';
        $previous_rank = $candidate['previous_rank'] ?? 'N/A';
        $rank_diff = ($current_rank !== 'N/A' && $previous_rank !== 'N/A') ? 
                    $previous_rank - $current_rank : null;
        
        if ($rank_diff !== null) {
            $icon = $rank_diff > 0 ? '↑' : ($rank_diff < 0 ? '↓' : '→');
            $color = $rank_diff > 0 ? 'text-success' : ($rank_diff < 0 ? 'text-danger' : 'text-muted');
            echo $current_rank . ' <span class="' . $color . '">' . $icon . ' ' . 
                 ($rank_diff != 0 ? abs($rank_diff) : '') . '</span>';
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
            $points_diff = ($previous_points !== 'N/A') ? 
                          $current_points - $previous_points : null;
            echo number_format($current_points, 1);
            if ($points_diff !== null) {
                $color = $points_diff >= 0 ? 'text-success' : 'text-danger';
                echo ' <span class="' . $color . '">(' . 
                     ($points_diff > 0 ? '+' : '') . 
                     number_format($points_diff, 1) . ')</span>';
            }
        } else {
            echo 'N/A';
        }
        ?>
    </td>
    <td><?php echo $candidate['experience'] ? number_format($candidate['experience'], 1) . ' years' : 'N/A'; ?></td>
    <td>
        <span class="badge <?php echo $candidate['isOwnCandidate'] ? 'bg-success' : 'bg-primary'; ?>">
            <?php echo $candidate['isOwnCandidate'] ? 'Own Candidate' : 'Tracking'; ?>
        </span>
    </td>
    <td>
        <a href="/WebEngineering/pages/applicant-details.php?name=<?php echo urlencode($candidate['candidateFullName']); ?>&field=<?php echo urlencode($candidate['fields']); ?>&type=<?php echo urlencode($candidate['type']); ?>" 
           class="btn btn-sm btn-outline-primary">
            <i class="fas fa-info-circle"></i> Details
        </a>
    </td>
</tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate random colors for each candidate
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
                    data: chartData[name].points,
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
                scales: {
                    y: {
                        min: 70,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Points'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(1)} points`;
                            }
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
                scales: {
                    y: {
                        reverse: true,
                        title: {
                            display: true,
                            text: 'Ranking'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: Rank ${context.parsed.y}`;
                            }
                        }
                    }
                }
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchTable');
        const tableRows = document.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const field = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const type = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                const matches = name.includes(searchTerm) || 
                              field.includes(searchTerm) || 
                              type.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
            });
        });

        // Make rows clickable
        tableRows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking a link or button
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || 
                    e.target.closest('a') || e.target.closest('button')) {
                    return;
                }
                
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