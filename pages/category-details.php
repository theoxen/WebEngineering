<?php
// filepath: c:\xampp\htdocs\WebEngineering\category-details.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get category ID from URL
$categoryID = isset($_GET['id']) ? intval($_GET['id']) : null;

// Validate parameter
if (!$categoryID) {
    header("Location: homepage.php");
    exit;
}

// Get limit from URL parameter (default to 50)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Validate the limit to only allow 25, 50, or 100
if (!in_array($limit, [25, 50, 100])) {
    $limit = 50;
}

// Connect to the database
require_once "../database/db_connect.php";

// Query to get category details
$stmt = $mysqli->prepare("SELECT * FROM categories WHERE categoryID = ?");
$stmt->bind_param("i", $categoryID);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

// If category not found, redirect
if (!$category) {
    header("Location: homepage.php");
    exit;
}

// Query to get candidates for the category
$sql = "SELECT * FROM rankinglist WHERE categoryID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $categoryID);
$stmt->execute();
$result = $stmt->get_result();
$candidates = $result->fetch_all(MYSQLI_ASSOC);

// Apply limit here if you're not using LIMIT in SQL
$candidates = array_slice($candidates, 0, $limit);

$pageTitle = $category['type'] . " - " . $category['season'] . " " . $category['year'];
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
    
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .pdf-container {
            max-width: 100%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    
    <div class="container mt-4">
        <div class="page-header">
            <h1 class="text-center"><?php echo htmlspecialchars($category['type']); ?></h1>
            <p class="text-center">
                <?php echo htmlspecialchars($category['season'] . " " . $category['year']); ?> - 
                <?php echo htmlspecialchars($category['fields'] ?? ''); ?>
            </p>
        </div>
        
        <?php if (!empty($category['file_path'])): ?>
        <!-- <div class="pdf-container">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Προβολή Αρχείου PDF</h5>
                    <iframe src="<?php echo htmlspecialchars($category['file_path']); ?>" 
                            width="100%" height="600" style="border: none;">
                    </iframe>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo htmlspecialchars($category['file_path']); ?>" 
                       class="btn btn-primary" target="_blank">
                        <i class="fas fa-download me-2"></i> Λήψη PDF
                    </a>
                </div>
            </div>
        </div> -->
        <?php else: ?>

        <?php endif; ?>
        
        <div class="mb-3 d-flex align-items-center">
            <span class="me-2">Show entries:</span>
            <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto;">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
            </select>
        </div>

        <div class="table-responsive mt-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ranking</th>
                        <th>Full Name</th>
                        <th>Application Number</th>
                        <th>Points</th>
                        <th>Title Date</th>
                        <th>Title Grade</th>
                        <th>Extra Qualifications</th>
                        <th>Experience</th>
                        <th>Army</th>
                        <th>Registration Date</th>
                        <th>Birthday Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($candidates)) {
                        foreach ($candidates as $candidate) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($candidate['ranking']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['fullName']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['appNum']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['points']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['titleDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['titleGrade']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['extraQualifications']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['experience']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['army']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['registrationDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['birthdayDate']) . "</td>";
                            echo "<td>" . htmlspecialchars($candidate['notes']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' class='text-center'>Δεν υπάρχουν δεδομένα για αυτή την κατηγορία.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-4 mb-5">
            <a href="season-categories.php?year=<?php echo $category['year']; ?>&season=<?php echo urlencode($category['season']); ?>" 
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Επιστροφή στις κατηγορίες
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add this script to make the entriesPerPage dropdown functional
        document.addEventListener('DOMContentLoaded', function() {
            const entriesSelect = document.getElementById('entriesPerPage');
            if (entriesSelect) {
                entriesSelect.addEventListener('change', function() {
                    // Get current URL and parameters
                    const url = new URL(window.location.href);
                    // Set the limit parameter
                    url.searchParams.set('limit', this.value);
                    // Redirect to the new URL
                    window.location.href = url.toString();
                });
                
                // Set the dropdown to match the current limit parameter
                const urlParams = new URLSearchParams(window.location.search);
                const currentLimit = urlParams.get('limit');
                if (currentLimit && (currentLimit === '25' || currentLimit === '50' || currentLimit === '100')) {
                    entriesSelect.value = currentLimit;
                }
            }
        });
    </script>
</body>
</html>