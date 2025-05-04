<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../database/db_connect.php';
$pageTitle = "Στοιχεία Υποψηφίου";

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: homepage.php");
    exit;
}

// // Database connection
// $servername = "localhost";
// $username = "root"; 
// $password = ""; 
// $dbname = "cei326omada1";

// $conn = new mysqli($servername, $username, $password, $dbname);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

$applicantID = $_GET['id'];
$applicantDetails = null;

// Check if categories table exists
$sql = "SHOW TABLES LIKE 'categories'";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    // Categories table exists
    $sql = "SELECT r.*, c.fields 
            FROM rankinglist r 
            JOIN categories c ON r.categoryID = c.categoryID 
            WHERE r.id = ?";
} else {
    // No categories table, just query rankinglist
    $sql = "SELECT r.*, 'Unknown' as fields 
            FROM rankinglist r 
            WHERE r.id = ?";
}

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $applicantDetails = $result->fetch_assoc();
} else {
    // Check if in session tracked list
    if (isset($_SESSION['tracked_applicants'])) {
        foreach ($_SESSION['tracked_applicants'] as $tracked) {
            if ($tracked['id'] == $applicantID) {
                $applicantDetails = $tracked;
                break;
            }
        }
    }
    
    if (!$applicantDetails) {
        header("Location: homepage.php?error=applicant_not_found");
        exit;
    }
}

// Check if applicant is being tracked
$isTracked = false;
if (isset($_SESSION['tracked_applicants'])) {
    foreach ($_SESSION['tracked_applicants'] as $tracked) {
        if ($tracked['id'] == $applicantID) {
            $isTracked = true;
            break;
        }
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="el">
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
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
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2rem;
            font-size: 3rem;
            color: #4e73df;
        }
        
        .profile-info h1 {
            margin-bottom: 0.5rem;
        }
        
        .applicant-details-card {
            margin-top: 1rem;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
        }

        .ranking-box {
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #4e73df;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            margin: 1rem auto;
        }
    </style>
</head>
<body>
    <?php include_once('../components/sidebar/sidebar.php'); ?>
    
    <div class="container content-wrapper">
    <div class="mb-4">
    <a href="homepage.php?return=search" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Επιστροφή στα αποτελέσματα
    </a>
</div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="profile-header">
                    <div class="profile-image">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($applicantDetails['fullName']); ?></h1>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($applicantDetails['fields']); ?></span>
                            <span class="badge bg-success">Αρ. Αίτησης: <?php echo $applicantDetails['appNum']; ?></span>
                        </div>
                        <p class="text-muted mb-0">Κατάταξη: <?php echo $applicantDetails['ranking']; ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card applicant-details-card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Βαθμολογία & Στοιχεία Κατάταξης</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Συνολικά Μόρια</div>
                                    <div class="col-md-8"><?php echo number_format($applicantDetails['points'], 2); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Ημ/νία Πτυχίου</div>
                                    <div class="col-md-8"><?php echo date('d/m/Y', strtotime($applicantDetails['titleDate'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Βαθμός Πτυχίου</div>
                                    <div class="col-md-8"><?php echo $applicantDetails['titleGrade']; ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Επιπλέον Προσόντα</div>
                                    <div class="col-md-8"><?php echo $applicantDetails['extraQualifications']; ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Προϋπηρεσία</div>
                                    <div class="col-md-8"><?php echo $applicantDetails['experience']; ?></div>
                                </div>
                                <?php if (isset($applicantDetails['army']) && $applicantDetails['army'] > 0): ?>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Στρατιωτική Θητεία</div>
                                    <div class="col-md-8"><?php echo $applicantDetails['army']; ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card applicant-details-card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Προσωπικά Στοιχεία</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Ημ/νία Γέννησης</div>
                                    <div class="col-md-8"><?php echo date('d/m/Y', strtotime($applicantDetails['birthdayDate'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Ημ/νία Εγγραφής</div>
                                    <div class="col-md-8"><?php echo date('d/m/Y', strtotime($applicantDetails['registrationDate'])); ?></div>
                                </div>
                                <?php if (!empty($applicantDetails['notes'])): ?>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Σημειώσεις</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($applicantDetails['notes']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card applicant-details-card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Κατάταξη</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="ranking-box">#<?php echo $applicantDetails['ranking']; ?></div>
                                <p class="text-muted">Στην κατηγορία <?php echo htmlspecialchars($applicantDetails['fields']); ?></p>
                                
                                <hr>
                                
                                <div class="d-grid gap-2 mt-3">
                                    <?php if ($isTracked): ?>
                                        <button id="untrackBtn" data-id="<?php echo $applicantDetails['id']; ?>" class="btn btn-danger">
                                            <i class="fas fa-user-minus me-2"></i> Διακοπή Παρακολούθησης
                                        </button>
                                    <?php else: ?>
                                        <button id="trackBtn" data-id="<?php echo $applicantDetails['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-user-plus me-2"></i> Προσθήκη στην Παρακολούθηση
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Show success message function
        function showMessage(message, type) {
            const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                                    ${message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>`);
            
            $('.content-wrapper .mb-4').after(alertDiv);
            setTimeout(() => {
                alertDiv.alert('close');
            }, 3000);
        }
        
        // Track button functionality
        $('#trackBtn').click(function() {
            const applicantID = $(this).data('id');
            
            $.ajax({
                url: '../track-applicants.php',
                method: 'POST',
                data: {
                    track_single: true,
                    applicantID: applicantID
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage('<i class="fas fa-check-circle me-2"></i> Ο υποψήφιος προστέθηκε στην παρακολούθηση', 'success');
                        
                        // Change button without reloading the page
                        $('#trackBtn').replaceWith(`
                            <button id="untrackBtn" data-id="${applicantID}" class="btn btn-danger">
                                <i class="fas fa-user-minus me-2"></i> Διακοπή Παρακολούθησης
                            </button>
                        `);
                        
                        // Re-bind click event to the new button
                        $('#untrackBtn').click(function() {
                            const id = $(this).data('id');
                            untrackApplicant(id);
                        });
                    }
                }
            });
        });
        
        // Untrack function
        function untrackApplicant(applicantID) {
            $.ajax({
                url: '../track-applicants.php',
                method: 'POST',
                data: {
                    untrack: true,
                    applicantID: applicantID
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showMessage('<i class="fas fa-check-circle me-2"></i> Ο υποψήφιος αφαιρέθηκε από την παρακολούθηση', 'warning');
                        
                        // Change button without reloading
                        $('#untrackBtn').replaceWith(`
                            <button id="trackBtn" data-id="${applicantID}" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i> Προσθήκη στην Παρακολούθηση
                            </button>
                        `);
                        
                        // Re-bind click event to the new button
                        $('#trackBtn').click(function() {
                            const id = $(this).data('id');
                            
                            $.ajax({
                                url: '../track-applicants.php',
                                method: 'POST',
                                data: {
                                    track_single: true,
                                    applicantID: id
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        showMessage('<i class="fas fa-check-circle me-2"></i> Ο υποψήφιος προστέθηκε στην παρακολούθηση', 'success');
                                        location.reload();
                                    }
                                }
                            });
                        });
                    }
                }
            });
        }
        
        // Untrack button functionality
        $('#untrackBtn').click(function() {
            const applicantID = $(this).data('id');
            untrackApplicant(applicantID);
        });
    });
</script>
</body>
</html>