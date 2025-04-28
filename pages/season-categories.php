<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get parameters from URL
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$season = isset($_GET['season']) ? $_GET['season'] : null;

// Validate parameters
if (!$year || !$season) {
    header("Location: homepage.php");
    exit;
}

// Connect to the database
require_once "../database/db_connect.php";

// Query to get categories for this season
$stmt = $mysqli->prepare("SELECT categoryID, year, season, type, fields, file_path FROM categories WHERE year = ? AND season = ? ORDER BY type, fields");
$stmt->bind_param("is", $year, $season);
$stmt->execute();
$result = $stmt->get_result();

$pageTitle = "$season $year - Κατηγορίες Καταλόγων";

// Group categories by type
$groupedCategories = [];
while ($category = $result->fetch_assoc()) {
    // Normalize the type (trim whitespace AND convert to lowercase for comparison)
    $normalizedType = preg_replace('/\s+/', '', strtolower(trim($category['type'])));
    
    // Store the original type for display
    $displayType = $category['type'];
    
    if (!isset($groupedCategories[$normalizedType])) {
        $groupedCategories[$normalizedType] = [
            'displayName' => $displayType,
            'categories' => []
        ];
    }
    $groupedCategories[$normalizedType]['categories'][] = $category;
}
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
    
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
        }
        
        .category-group {
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .group-header {
            background-color: #4e73df;
            color: white;
            padding: 0.75rem 1.25rem;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .group-header:hover {
            background-color: #3a5ecc;
        }
        
        .category-count {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .group-content {
            display: none;
        }
        
        .group-content.show {
            display: block;
        }
        
        .category-item {
            padding: 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: white;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-button {
            width: 100%;
            text-align: left;
            padding: 1rem 1.25rem;
            background: none;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .category-button:hover {
            background-color: #f8f9fc;
        }
        
        .category-name {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .back-btn {
            color: #5a5c69;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        
        .back-btn:hover {
            color: #4e73df;
            transform: translateX(-5px);
        }

        .category-dropdown {
            width: 100%;
            border-top: 1px solid #e3e6f0;
            padding: 0;
            display: none;
        }
        
        .category-dropdown.show {
            display: block;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: #5a5c69;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fc;
            color: #4e73df;
        }
    </style>
</head>
<body>
    <?php include_once "../components/sidebar/sidebar.php"; ?>
    
    <div class="container mt-4">
        <div class="page-header text-center">
            <h1><?php echo $season . " " . $year; ?></h1>
            <p class="text-muted">Κατάλογοι διοριστέων εκπαιδευτικών ανά κατηγορία</p>
        </div>
        
        <a href="homepage.php" class="back-btn">
            <i class="fas fa-arrow-left me-2"></i> Επιστροφή στην αρχική
        </a>
        
        <?php if (empty($groupedCategories)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Δεν βρέθηκαν κατάλογοι για τη συγκεκριμένη περίοδο.
            </div>
        <?php else: ?>
            <?php foreach ($groupedCategories as $normalizedType => $groupData): ?>
                <div class="category-group">
                    <div class="group-header" onclick="toggleGroupContent(this)">
                        <div>
                            <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($groupData['displayName']); ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="category-count me-2">
                                <?php echo count($groupData['categories']); ?> <?php echo count($groupData['categories']) > 1 ? 'κατάλογοι' : 'κατάλογος'; ?>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="group-content">
                        <?php foreach ($groupData['categories'] as $category): ?>
                            <div class="category-item">
                                <button type="button" class="category-button" onclick="toggleDropdown(<?php echo $category['categoryID']; ?>)">
                                    <span class="category-name"><?php echo htmlspecialchars($category['fields']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div id="dropdown-<?php echo $category['categoryID']; ?>" class="category-dropdown">
                                    <a class="dropdown-item" href="category-details.php?id=<?php echo $category['categoryID']; ?>">
                                        <i class="fas fa-eye me-2"></i> Προβολή Καταλόγου
                                    </a>
                                    <?php if (!empty($category['file_path'])): ?>
                                    <a class="dropdown-item" href="<?php echo htmlspecialchars($category['file_path']); ?>" target="_blank">
                                        <i class="fas fa-file-pdf me-2"></i> Άνοιγμα PDF
                                    </a>
                                    <a class="dropdown-item" href="download.php?id=<?php echo $category['categoryID']; ?>">
                                        <i class="fas fa-download me-2"></i> Κατέβασμα PDF
                                    </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="admin/edit-category.php?id=<?php echo $category['categoryID']; ?>">
                                        <i class="fas fa-edit me-2"></i> Επεξεργασία
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to toggle group content visibility
        function toggleGroupContent(header) {
            const groupContent = header.nextElementSibling;
            const icon = header.querySelector('.fa-chevron-down, .fa-chevron-up');
            
            // Close other group contents
            document.querySelectorAll('.group-content.show').forEach(content => {
                if (content !== groupContent) {
                    content.classList.remove('show');
                    const otherIcon = content.previousElementSibling.querySelector('.fa-chevron-down, .fa-chevron-up');
                    if (otherIcon && otherIcon.classList.contains('fa-chevron-up')) {
                        otherIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                }
            });
            
            // Toggle current group content
            groupContent.classList.toggle('show');
            
            // Change icon direction
            if (groupContent.classList.contains('show')) {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
            
            // Close any open category dropdowns
            document.querySelectorAll('.category-dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
                const buttonIcon = dropdown.previousElementSibling.querySelector('i');
                buttonIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            });
        }
        
        // Function to toggle dropdown visibility
        function toggleDropdown(categoryId) {
            const dropdown = document.getElementById(`dropdown-${categoryId}`);
            
            // Close all other dropdowns first
            document.querySelectorAll('.category-dropdown.show').forEach(item => {
                if (item.id !== `dropdown-${categoryId}`) {
                    item.classList.remove('show');
                    const button = item.previousElementSibling;
                    const icon = button.querySelector('i');
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('show');
            
            // Change the chevron icon direction
            const button = dropdown.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (dropdown.classList.contains('show')) {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
            
            // Prevent the click event from propagating to document
            event.stopPropagation();
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            // Check if click is outside a category item
            if (!event.target.closest('.category-item')) {
                document.querySelectorAll('.category-dropdown.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                    const button = dropdown.previousElementSibling;
                    const icon = button.querySelector('i');
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                });
            }
            
            // Check if click is outside a group header
            if (!event.target.closest('.group-header') && !event.target.closest('.group-content')) {
                document.querySelectorAll('.group-content.show').forEach(content => {
                    content.classList.remove('show');
                    const header = content.previousElementSibling;
                    const icon = header.querySelector('.fa-chevron-up, .fa-chevron-down');
                    if (icon) {
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                });
            }
        });
    </script>
</body>
</html>