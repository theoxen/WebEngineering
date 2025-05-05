<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');

// 1. Overall Statistics
$queries = [
    // Total unique candidates
    "SELECT COUNT(DISTINCT fullName) as total_candidates FROM rankinglist",
    
    // Average title grade
    "SELECT AVG(titleGrade) as avg_grade FROM rankinglist",
    
    // Average experience
    "SELECT AVG(experience) as avg_experience FROM rankinglist",
    
    // Average points
    "SELECT AVG(points) as avg_points FROM rankinglist"
];

$overall_stats = [];
foreach ($queries as $query) {
    $result = $mysqli->query($query);
    $overall_stats[] = $result->fetch_assoc();
}

// 2. Category Distribution
$query = "SELECT 
            c.fields,
            c.type,
            c.season,
            c.year,
            COUNT(r.id) as candidate_count,
            AVG(r.points) as avg_points,
            MAX(r.points) as max_points,
            MIN(r.points) as min_points
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          GROUP BY c.fields, c.type, c.season, c.year
          ORDER BY c.year DESC, c.season DESC";
$category_stats = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// 3. Experience Distribution
$query = "SELECT 
            FLOOR(experience) as exp_years,
            COUNT(*) as count
          FROM rankinglist
          GROUP BY FLOOR(experience)
          ORDER BY exp_years";
$experience_stats = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// 4. Points Range Distribution
$query = "SELECT 
            CASE 
                WHEN points >= 90 THEN '90-100'
                WHEN points >= 80 THEN '80-89'
                WHEN points >= 70 THEN '70-79'
                ELSE 'Below 70'
            END as point_range,
            COUNT(*) as count
          FROM rankinglist
          GROUP BY 
            CASE 
                WHEN points >= 90 THEN '90-100'
                WHEN points >= 80 THEN '80-89'
                WHEN points >= 70 THEN '70-79'
                ELSE 'Below 70'
            END
          ORDER BY point_range DESC";
$points_stats = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// 5. Yearly Trends
$query = "SELECT 
            c.year,
            c.season,
            COUNT(DISTINCT r.fullName) as candidates,
            AVG(r.points) as avg_points,
            AVG(r.experience) as avg_experience
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          GROUP BY c.year, c.season
          ORDER BY c.year DESC, c.season DESC";
$yearly_trends = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// Add after existing queries
$query = "SELECT 
            c.fields,
            c.type,
            COUNT(DISTINCT r.fullName) as candidate_count
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          GROUP BY c.fields, c.type
          ORDER BY c.fields, c.type";
$field_type_stats = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Statistics</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Add this in the <style> section -->
    <style>
        .table-container {
            max-height: 600px;
            overflow-y: auto;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .sticky-header th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .clickable-row {
            cursor: pointer;
        }
        .clickable-row:hover {
            background-color: rgba(0,0,0,0.05);
        }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="page-header mb-4">
                <h1 class="page-title">Ranking Statistics Dashboard</h1>
                <p class="text-muted">Comprehensive analysis of ranking data</p>
            </div>

            <!-- Overall Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>Total Candidates</h6>
                            <h3><?php echo round($overall_stats[0]['total_candidates']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Average Grade</h6>
                            <h3><?php echo number_format($overall_stats[1]['avg_grade'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Average Experience</h6>
                            <h3><?php echo number_format($overall_stats[2]['avg_experience'], 1); ?> years</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Average Points</h6>
                            <h3><?php echo number_format($overall_stats[3]['avg_points'], 1); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Points Distribution Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Points Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="pointsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Experience Distribution Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Experience Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="experienceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Candidates by Field and Specialization</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="fieldTypeChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Statistics Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Statistics</h5>
                </div>
                <div class="card-body">
                    <!-- Add this before the table -->
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control" placeholder="Search rankings...">
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
                            <tbody>
                                <?php foreach ($category_stats as $stat): ?>
                                <tr style="cursor: pointer;" class="field-row" 
                                    data-field="<?php echo htmlspecialchars($stat['fields']); ?>"
                                    data-type="<?php echo htmlspecialchars($stat['type']); ?>"
                                    data-season="<?php echo htmlspecialchars($stat['season']); ?>"
                                    data-year="<?php echo $stat['year']; ?>">
                                    <td><?php echo htmlspecialchars($stat['fields']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['type']); ?></td>
                                    <td><?php echo htmlspecialchars($stat['season'] . ' ' . $stat['year']); ?></td>
                                    <td><?php echo $stat['candidate_count']; ?></td>
                                    <td><?php echo number_format($stat['avg_points'], 1); ?></td>
                                    <td><?php echo number_format($stat['max_points'], 1); ?></td>
                                    <td><?php echo number_format($stat['min_points'], 1); ?></td>
                                    <td>
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
    
    <script>
    // Points Distribution Chart
    new Chart(document.getElementById('pointsChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($points_stats, 'point_range')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($points_stats, 'count')); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Candidates: ${Math.round(context.raw)}`;
                        }
                    }
                }
            }
        }
    });

    // Experience Distribution Chart
    new Chart(document.getElementById('experienceChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($item) { 
                return $item['exp_years'] . ' years';
            }, $experience_stats)); ?>,
            datasets: [{
                label: 'Number of Candidates',
                data: <?php echo json_encode(array_column($experience_stats, 'count')); ?>,
                backgroundColor: '#36b9cc'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Candidates'
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.round(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Candidates: ${Math.round(context.raw)}`;
                        }
                    }
                }
            }
        }
    });

    // Field and Specialization Distribution Chart
    new Chart(document.getElementById('fieldTypeChart'), {
        type: 'bar',
        data: {
            labels: <?php 
                $labels = array_map(function($item) {
                    return $item['fields'] . ' (' . $item['type'] . ')';
                }, $field_type_stats);
                echo json_encode($labels);
            ?>,
            datasets: [{
                label: 'Number of Candidates',
                data: <?php echo json_encode(array_column($field_type_stats, 'candidate_count')); ?>,
                backgroundColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b',
                    '#858796'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Candidates'
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.round(value);
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Field (Type)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Candidates: ${Math.round(context.raw)}`;
                        }
                    }
                }
            }
        }
    });

    // Initialize modal chart variables
    let modalPointsChart = null;
    let modalExperienceChart = null;

    // Add click event listeners to table rows
    document.querySelectorAll('.field-row').forEach(row => {
        row.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('fieldDetailsModal'));
            modal.show();
            
            const field = this.dataset.field;
            const type = this.dataset.type;
            const season = this.dataset.season;
            const year = this.dataset.year;

            // Update modal title immediately
            document.querySelector('.modal-title').textContent = 
                `${field} (${type}) - ${season} ${year}`;

            // Fetch and update detailed statistics
            fetch(`get_field_stats.php?field=${encodeURIComponent(field)}&type=${encodeURIComponent(type)}&season=${encodeURIComponent(season)}&year=${encodeURIComponent(year)}`)
                .then(response => response.json())
                .then(data => {
                    // Update statistics table
                    document.getElementById('fieldStats').innerHTML = 
                        Object.entries(data.stats)
                            .map(([key, value]) => `<tr><td>${key}</td><td>${value}</td></tr>`)
                            .join('');

                    // Update charts
                    if (modalPointsChart) modalPointsChart.destroy();
                    if (modalExperienceChart) modalExperienceChart.destroy();

                    modalPointsChart = new Chart(document.getElementById('modalPointsChart'), {
                        type: 'pie',
                        data: {
                            labels: data.pointsDistribution.labels,
                            datasets: [{
                                data: data.pointsDistribution.data,
                                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                            }]
                        }
                    });

                    modalExperienceChart = new Chart(document.getElementById('modalExperienceChart'), {
                        type: 'bar',
                        data: {
                            labels: data.experienceDistribution.labels,
                            datasets: [{
                                label: 'Number of Candidates',
                                data: data.experienceDistribution.data,
                                backgroundColor: '#36b9cc'
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1 }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('fieldStats').innerHTML = 
                        '<tr><td colspan="2" class="text-danger">Error loading statistics</td></tr>';
                });
        });
    });
    </script>

    <!-- Field Details Modal -->
<div class="modal fade" id="fieldDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Field Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Points Distribution</h6>
                                <canvas id="modalPointsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Experience Distribution</h6>
                                <canvas id="modalExperienceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody id="fieldStats">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchTable');
    const tableRows = document.querySelectorAll('.clickable-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Row click handler
    tableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking a link or button
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
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