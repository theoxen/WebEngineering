<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');

// Get the selected time period
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] . ' 23:59:59' : date('Y-m-d H:i:s');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] . ' 00:00:00' : date('Y-m-d H:i:s', strtotime('-30 days'));

// Calculate days difference for the header
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$days = $date2->diff($date1)->days;

// Get new users statistics
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

// Get daily registration counts
$query = "SELECT 
            DATE(r.registrationDate) as entry_date,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT c.fields) as fields
          FROM rankinglist r
          JOIN categories c ON r.categoryID = c.categoryID
          WHERE r.registrationDate BETWEEN ? AND ?
          GROUP BY DATE(r.registrationDate)
          ORDER BY entry_date ASC";  // Added ASC to ensure proper ordering

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user activity (tracking statistics)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Statistics Report</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .sticky-header th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .search-box {
            margin-bottom: 20px;
        }
        .candidate-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .candidate-row:hover {
            background-color: rgba(0,0,0,0.05);
        }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <!-- Date range selector form -->
            <div class="mb-4">
                <form class="card" method="GET">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" 
                                       value="<?php echo date('Y-m-d', strtotime($start_date)); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" 
                                       value="<?php echo date('Y-m-d', strtotime($end_date)); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Update Report</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Page header with date range -->
            <div class="page-header mb-4">
                <h1 class="page-title">Candidate Entry Report</h1>
                <p class="text-muted">Entries from <?php echo date('M d, Y', strtotime($start_date)); ?> 
                to <?php echo date('M d, Y', strtotime($end_date)); ?> (<?php echo $days; ?> days)</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>Total Candidates</h6>
                            <h3><?php echo $stats['total_candidates']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>High Performers (90+ points)</h6>
                            <h3><?php echo $stats['high_performers']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Experienced (5+ years)</h6>
                            <h3><?php echo $stats['experienced_candidates']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Average Points</h6>
                            <h3><?php echo number_format($stats['avg_points'], 1); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Trend Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Candidate Entries</h5>
                </div>
                <div class="card-body">
                    <div style="height: 400px"> <!-- Added fixed height container -->
                        <canvas id="registrationChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- New Candidate Entries Table with Search -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Candidate Entries</h5>
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
                                    data-name="<?php echo htmlspecialchars($candidate['fullName']); ?>"
                                    data-field="<?php echo htmlspecialchars($candidate['fields']); ?>"
                                    data-type="<?php echo htmlspecialchars($candidate['type']); ?>">
                                    <td><?php echo htmlspecialchars($candidate['fullName']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['fields']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['type']); ?></td>
                                    <td><?php echo $entry_date->format('Y-m-d H:i'); ?></td>
                                    <td><?php echo number_format($candidate['points'], 1); ?></td>
                                    <td><?php echo number_format($candidate['experience'], 1); ?> years</td>
                                    <td><?php echo $days_listed; ?> days</td>
                                    <td>
                                        <a href="/WebEngineering/pages/applicant-details.php?name=<?php echo urlencode($candidate['fullName']); ?>&field=<?php echo urlencode($candidate['fields']); ?>&type=<?php echo urlencode($candidate['type']); ?>" 
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
    
    <script>
    // Registration Trend Chart
    new Chart(document.getElementById('registrationChart'), {
        type: 'line',  // Changed back to line chart
        data: {
            labels: <?php 
                echo json_encode(array_map(function($row) {
                    return date('M d, Y', strtotime($row['entry_date']));
                }, $daily_stats)); 
            ?>,
            datasets: [{
                label: 'New Candidates',
                data: <?php echo json_encode(array_column($daily_stats, 'count')); ?>,
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
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    titleColor: '#4e73df',
                    titleFont: {
                        weight: 'bold'
                    },
                    bodyColor: '#4e73df',
                    bodyFont: {
                        weight: 'normal'
                    },
                    borderColor: '#4e73df',
                    borderWidth: 1,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return `Candidates: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Date validation
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const today = new Date().toISOString().split('T')[0];
        startDate.max = today;
        endDate.max = today;

        function validateDates() {
            if(startDate.value && endDate.value) {
                if(startDate.value > endDate.value) {
                    alert('Start date cannot be after end date');
                    endDate.value = startDate.value;
                }
            }
        }

        startDate.addEventListener('change', validateDates);
        endDate.addEventListener('change', validateDates);

        document.querySelector('form').addEventListener('submit', function(e) {
            if(!startDate.value || !endDate.value) {
                e.preventDefault();
                alert('Please select both start and end dates');
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchTable');
        const tableRows = document.querySelectorAll('.candidate-row');

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

        // Row click handler
        tableRows.forEach(row => {
            row.addEventListener('click', function(e) {
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