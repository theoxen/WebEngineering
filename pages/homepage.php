<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Κατάλογοι Διοριστέων";

$currentYear = date("Y");
$currentMonth = date("n"); // Numeric representation of the month (1-12)

$monthNames = [
    2 => "Φεβρουάριος",
    6 => "Ιούνιος"
];

// Starting year
$startYear = 2016;
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
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap"
        rel="stylesheet">


    <style>
        /* Additional homepage styles */
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            padding: 2rem 0;
            margin-bottom: 3rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .page-title {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }

        .page-description {
            color: #6c757d;
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .catalogs-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .year-section {
            margin-bottom: 1rem;
        }

        /* Vertical card styling */
        .catalog-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            background: white;
            display: flex;
            flex-direction: row;
            width: 100%;
        }

        .catalog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.15);
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
        }

        .card-header-custom {
            background-color: #4e73df;
            color: white;
            padding: 1.5rem 1rem;
            /* Reduced horizontal padding */
            width: 200px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .card-header-custom::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: -20px;
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .card-title {
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
            margin-top: 1rem;
            /* Add these properties to fix the overflow */
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            width: 100%;
            hyphens: auto;
        }

        .card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        .card-info {
            display: flex;
            justify-content: space-around;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #f1f5fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #4e73df;
        }

        .year-label,
        .month-label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.2rem;
            color: #2c3e50;
        }

        .card-icon {
            font-size: 3rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .btn-view {
            background-color: #4e73df;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            border: none;
        }

        .btn-view:hover {
            background-color: #2e59d9;
            transform: translateX(5px);
        }

        .year-divider {
            width: 100%;
            margin-bottom: 1.5rem;
            position: relative;
            text-align: center;
        }

        .year-divider h3 {
            background: #f8f9fc;
            display: inline-block;
            padding: 0.5rem 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .year-divider::after {
            content: '';
            position: absolute;
            width: 100%;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            left: 0;
            top: 50%;
            z-index: 0;
        }

        .text-end {
            align-self: center;
        }

        @media (max-width: 955px) {
            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }

            .card-title {
                font-size: 1.1rem;
                margin-top: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .catalog-card {
                flex-direction: column;
            }

            .card-header-custom {
                width: 100%;
                padding: 1rem;
            }

            .card-info {
                flex-direction: column;
            }

            .info-item {
                margin-bottom: 1rem;
                margin-right: 0;
            }

            .card-body {
                flex-direction: column;
            }

            .text-end {
                align-self: flex-end;
            }

        }
    </style>
</head>

<body>
    <?php
    // Include sidebar
    include_once('../components/sidebar/sidebar.php');
    ?>
    <div class="main-content">
        <div class="container content-wrapper">
            <div class="page-header">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                <p class="page-description">Πρόσβαση στους καταλόγους διοριστέων εκπαιδευτικών ανά περίοδο και έτος.
                    Επιλέξτε τον κατάλογο που επιθυμείτε.</p>
            </div>

            <div class="catalogs-container">
                <?php
                // Loop through each year from current year down to the starting year
                for ($year = $currentYear; $year >= $startYear; $year--) {
                    // Start a new section for this year
                    echo '<div class="year-section">';

                    // Display year divider
                    echo '<div class="year-divider text-center"><h3>' . $year . '</h3></div>';

                    // Begin cards for this year
                    echo '<div class="year-cards">';

                    // Check which months exist for this year
                    $hasFebruary = ($year < $currentYear || ($year == $currentYear && $currentMonth >= 2));
                    $hasJune = ($year < $currentYear || ($year == $currentYear && $currentMonth >= 6));

                    // February catalog if available
                    if ($hasFebruary) {
                        $monthNum = 2;
                        $monthName = $monthNames[$monthNum];
                        $cardUrl = "year-season-details.php?year=" . $year . "&month=" . $monthNum; // TODO: CHANGE THE FILENAME TO SOMETHING BETTER AND MORE DESCRIPTIVE
                
                        echo '<a href="' . $cardUrl . '" class="card-link">
                    <div class="catalog-card">
                        <div class="card-header-custom" style="background-color: #4e73df;">
                            <i class="fas fa-snowflake card-icon"></i>
                            <h5 class="card-title">
                                ' . $monthName . '
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="card-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="year-label">Έτος</span>
                                        <div class="info-value">' . $year . '</div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-list-alt"></i>
                                    </div>
                                    <div>
                                        <span class="month-label">Κατάλογος</span>
                                        <div class="info-value">Διοριστέων Εκπαιδευτικών</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button class="btn-view">
                                    Προβολή <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </a>';
                    }

                    // June catalog if available
                    if ($hasJune) {
                        $monthNum = 6;
                        $monthName = $monthNames[$monthNum];
                        $cardUrl = "details.php?year=" . $year . "&month=" . $monthNum;

                        echo '<a href="' . $cardUrl . '" class="card-link">
                    <div class="catalog-card">
                        <div class="card-header-custom" style="background-color: #f6c23e;">
                            <i class="fas fa-sun card-icon"></i>
                            <h5 class="card-title">
                                ' . $monthName . '
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="card-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span class="year-label">Έτος</span>
                                        <div class="info-value">' . $year . '</div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-list-alt"></i>
                                    </div>
                                    <div>
                                        <span class="month-label">Κατάλογος</span>
                                        <div class="info-value">Διοριστέων Εκπαιδευτικών</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button class="btn-view">
                                    Προβολή <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </a>';
                    }

                    // Close cards and section for this year
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS (optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>