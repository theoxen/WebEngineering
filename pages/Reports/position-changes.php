<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');

// Get selected parameters
$field = $_GET['field'] ?? '';
$type = $_GET['type'] ?? '';
$selected_season = $_GET['season'] ?? '';
$selected_year = $_GET['year'] ?? '';

// Replace the latest season query with previous season logic
$query = "SELECT year, season 
          FROM categories 
          WHERE (year < ? OR (year = ? AND 
          CASE season 
              WHEN 'Winter' THEN 1 
              WHEN 'Summer' THEN 2 
          END < 
          CASE ? 
              WHEN 'Winter' THEN 1 
              WHEN 'Summer' THEN 2 
          END))
          ORDER BY year DESC, 
          CASE season 
              WHEN 'Winter' THEN 1 
              WHEN 'Summer' THEN 2
          END DESC 
          LIMIT 1";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("iss", $selected_year, $selected_year, $selected_season);
$stmt->execute();
$result = $stmt->get_result();
$previous = $result->fetch_assoc();

// Update the comparison query
$query = "SELECT 
            r1.fullName as name,
            r1.ranking as old_rank,
            r2.ranking as new_rank,
            r1.points as old_points,
            r2.points as new_points
          FROM rankinglist r1
          LEFT JOIN rankinglist r2 ON r1.fullName = r2.fullName
          JOIN categories c1 ON r1.categoryID = c1.categoryID
          LEFT JOIN categories c2 ON r2.categoryID = c2.categoryID
          WHERE c1.year = ? AND c1.season = ?
          AND c1.fields = ? AND c1.type = ?
          AND c2.year = ? AND c2.season = ?
          ORDER BY r1.ranking ASC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ssssss", 
    $previous['year'], $previous['season'],  // Previous season
    $field, $type,
    $selected_year, $selected_season        // Selected season
);
$stmt->execute();
$result = $stmt->get_result();
$rankings = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total = count($rankings);
$improved = 0;
$declined = 0;
$same = 0;
$missing = 0;

foreach ($rankings as $rank) {
    if (is_null($rank['new_rank'])) {
        $missing++;
    } else {
        if ($rank['new_rank'] < $rank['old_rank']) $improved++;
        elseif ($rank['new_rank'] > $rank['old_rank']) $declined++;
        else $same++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Changes Report</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
    <style>
        .stats-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .improved { color: #1cc88a; }
        .declined { color: #e74a3b; }
        .same { color: #858796; }
        .missing { color: #f6c23e; }
        .stats-improved { border-left-color: #1cc88a; }
        .stats-declined { border-left-color: #e74a3b; }
        .stats-same { border-left-color: #858796; }
        .stats-missing { border-left-color: #f6c23e; }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="page-header mb-4">
                <h1 class="page-title">Position Changes Report</h1>
                <p class="text-muted">
                    Comparing <?php echo "{$previous['season']} {$previous['year']}"; ?> 
                    to <?php echo "$selected_season $selected_year"; ?>
                </p>
                <div class="badge bg-primary mb-3">
                    Field: <?php echo htmlspecialchars($field); ?> | 
                    Type: <?php echo htmlspecialchars($type); ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card stats-improved">
                        <div class="card-body">
                            <h5>Improved Positions</h5>
                            <h2><?php echo $improved; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card stats-declined">
                        <div class="card-body">
                            <h5>Declined Positions</h5>
                            <h2><?php echo $declined; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card stats-same">
                        <div class="card-body">
                            <h5>Unchanged</h5>
                            <h2><?php echo $same; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card stats-missing">
                        <div class="card-body">
                            <h5>No Longer Listed</h5>
                            <h2><?php echo $missing; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rankings Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Previous Rank</th>
                                    <th>Current Rank</th>
                                    <th>Change</th>
                                    <th>Previous Points</th>
                                    <th>Current Points</th>
                                    <th>Points Change</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rankings as $rank): 
                                    $rank_diff = !is_null($rank['new_rank']) ? 
                                        ($rank['old_rank'] - $rank['new_rank']) : 'N/A';
                                    $points_diff = !is_null($rank['new_points']) ? 
                                        ($rank['new_points'] - $rank['old_points']) : 'N/A';
                                    
                                    if ($rank_diff === 'N/A') {
                                        $class = 'missing';
                                        $icon = '×';
                                    } else {
                                        $class = $rank_diff > 0 ? 'improved' : 
                                               ($rank_diff < 0 ? 'declined' : 'same');
                                        $icon = $rank_diff > 0 ? '↑' : 
                                               ($rank_diff < 0 ? '↓' : '→');
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rank['name']); ?></td>
                                    <td><?php echo $rank['old_rank']; ?></td>
                                    <td><?php echo $rank['new_rank'] ?? 'Not Listed'; ?></td>
                                    <td class="<?php echo $class; ?>">
                                        <?php echo $icon . ' ' . 
                                            ($rank_diff === 'N/A' ? '' : abs($rank_diff)); ?>
                                    </td>
                                    <td><?php echo $rank['old_points']; ?></td>
                                    <td><?php echo $rank['new_points'] ?? 'N/A'; ?></td>
                                    <td class="<?php echo $class; ?>">
                                        <?php echo $points_diff === 'N/A' ? 'N/A' : 
                                            sprintf('%+.1f', $points_diff); ?>
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
</body>
</html>