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
    <title>Στατιστικά Χρηστών</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Main content wrapper */
        .content-wrapper {
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            transition: margin-left 0.3s;
        }

        /* Container adjustments */
        .container {
            max-width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }

        /* Responsive behavior */
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
                width: 100%;
            }
        }

        /* Improve chart responsiveness */
        .card {
            margin-bottom: 1.5rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        canvas {
            max-width: 100%;
        }

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
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <!-- Add back button -->
            <div class="mb-3">
                <a href="select-report.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Πίσω
                </a>
            </div>

            <!-- Existing page header -->
            <div class="page-header mb-4">
                <h1 class="page-title">Αναφορά Εγγραφών Υποψηφίων</h1>
                <p class="text-muted">Εγγραφές από <?php echo date('d/m/Y', strtotime($start_date)); ?> 
                έως <?php echo date('d/m/Y', strtotime($end_date)); ?> (<?php echo $days; ?> ημέρες)</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>Σύνολο Υποψηφίων</h6>
                            <h3><?php echo $stats['total_candidates']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Υψηλές Επιδόσεις (90+ μόρια)</h6>
                            <h3><?php echo $stats['high_performers']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Με Εμπειρία (5+ έτη)</h6>
                            <h3><?php echo $stats['experienced_candidates']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Μέσος Όρος Μορίων</h6>
                            <h3><?php echo number_format($stats['avg_points'], 1); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Trend Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ημερήσιες Εγγραφές Υποψηφίων</h5>
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
                    <h5 class="card-title">Εγγραφές Υποψηφίων</h5>
                </div>
                <div class="card-body">
                    <div class="search-box">
                        <input type="text" id="searchTable" class="form-control" placeholder="Αναζήτηση υποψηφίων...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="candidateTable">
                            <thead class="sticky-header">
                                <tr>
                                    <th>Ονοματεπώνυμο</th>
                                    <th>Πεδίο</th>
                                    <th>Τύπος</th>
                                    <th>Ημερομηνία Εγγραφής</th>
                                    <th>Μόρια</th>
                                    <th>Εμπειρία</th>
                                    <th>Ημέρες στη Λίστα</th>
                                
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
                                    <td><?php echo number_format($candidate['experience'], 1); ?> έτη</td>
                                    <td><?php echo $days_listed; ?> ημέρες</td>
 
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- Pagination controls -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="pagination-info">
                                Εμφάνιση <span id="pageStart">1</span> έως <span id="pageEnd">10</span> από <span id="totalItems">0</span> εγγραφές
                            </div>
                            <ul class="pagination mb-0">
                                <li class="page-item" id="previousPage">
                                    <button class="page-link" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </button>
                                </li>
                                <li class="page-item" id="nextPage">
                                    <button class="page-link" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
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
                            return `Υποψήφιοι: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // PAGINATION for Candidate Entries Table
        const rowsPerPage = 10;
        let currentPage = 1;
        let filteredRows = [];

        const tableRows = Array.from(document.querySelectorAll('#candidateTable tbody tr'));
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

            // Update info
            pageStart.textContent = totalRows ? start + 1 : 0;
            pageEnd.textContent = end;
            totalItems.textContent = totalRows;

            // Enable/disable buttons
            previousPage.classList.toggle('disabled', currentPage === 1);
            nextPage.classList.toggle('disabled', currentPage === totalPages || totalRows === 0);

            // Show/hide rows
            tableRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(start, end).forEach(row => row.style.display = '');
        }

        // Initialize
        filteredRows = tableRows;
        updatePagination();

        // Search with pagination
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

        // Pagination controls
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