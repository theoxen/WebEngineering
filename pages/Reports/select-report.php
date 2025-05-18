<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in 
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');
$pageTitle = "Select Report";
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
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
        }

        .content-wrapper {
            margin-left: 250px; /* Match sidebar width */
            padding: 30px;
            transition: margin-left 0.3s;
        }

        /* Responsive behavior */
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
            }
        }

        .page-header {
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .report-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.2s ease-in-out;
            margin-bottom: 1.5rem;
            height: 100%;
        }

        .report-card:hover {
            transform: translateY(-5px);
        }

        .report-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .report-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .report-description {
            color: #858796;
            margin-bottom: 1.5rem;
        }

        .btn-generate {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-generate:hover {
            background-color: #2e59d9;
            color: white;
            transform: translateY(-2px);
        }

        .form-select {
            border-radius: 0.5rem;
            border: 1px solid #d1d3e2;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="page-header">
                <h1 class="page-title">Select Report</h1>
                <p class="text-muted">Choose a report type to generate</p>
            </div>


            <div class="row">
                <!-- Position Changes Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card report-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-exchange-alt report-icon"></i>
                            <h3 class="report-title">Position Changes</h3>
                            <p class="report-description">
                                Track position movements and ranking changes throughout the semester/season.
                            </p>
                            <form action="position-changes.php" method="GET" class="text-start mb-3">
                                <!-- Field Selection -->
                                <div class="mb-3">
                                    <label for="field" class="form-label">Field</label>
                                    <select class="form-select" name="field" id="field" required>
                                        <?php
                                        $query = "SELECT DISTINCT fields FROM categories ORDER BY fields";
                                        $result = $mysqli->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row['fields']) . "'>" . 
                                                 htmlspecialchars($row['fields']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Type Selection -->
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" name="type" id="type" required>
                                        <?php
                                        $query = "SELECT DISTINCT type FROM categories ORDER BY type";
                                        $result = $mysqli->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row['type']) . "'>" . 
                                                 htmlspecialchars($row['type']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Season Selection -->
                                <div class="mb-3">
                                    <label for="season" class="form-label">Season</label>
                                    <select class="form-select" name="season" id="season" required>
                                        <option value="Winter">Winter</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>

                                <!-- Year Selection -->
                                <div class="mb-3">
                                    <label for="year" class="form-label">Year</label>
                                    <select class="form-select" name="year" id="year" required>
                                        <?php
                                        $query = "SELECT DISTINCT year FROM categories ORDER BY year DESC";
                                        $result = $mysqli->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row['year']) . "'>" . 
                                                 htmlspecialchars($row['year']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-generate w-100">
                                    Generate Report <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- General Statistics Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card report-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-chart-pie report-icon"></i>
                            <h3 class="report-title">General Statistics</h3>
                            <p class="report-description">
                                View overall statistics including total candidates, field distribution, and seasonal comparisons.
                            </p>
                            <form action="general-statistics.php" method="GET">
                                <button type="submit" class="btn btn-generate w-100">
                                    View Statistics <i class="fas fa-chart-bar ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- New Users Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card report-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-users-gear report-icon"></i>
                            <h3 class="report-title">New Users Report</h3>
                            <p class="report-description">
                                View user registration statistics for a custom date range.
                            </p>
                            <form action="user-statistics.php" method="GET" class="text-start mb-3">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-select" name="start_date" id="start_date" 
                                           required value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-select" name="end_date" id="end_date" 
                                           required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <button type="submit" class="btn btn-generate w-100">
                                    Generate Report <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tracking Report -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card report-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-user-clock report-icon"></i>
                            <h3 class="report-title">My Tracking Report</h3>
                            <p class="report-description">
                                View detailed statistics and updates for all candidates you are tracking.
                            </p>
                            <form action="tracking-report.php" method="GET">
                                <button type="submit" class="btn btn-generate w-100">
                                    View Tracking Report <i class="fas fa-binoculars ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>