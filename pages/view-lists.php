<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add authentication check if required
// If page requires login:
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection if needed
include_once('../database/db_connect.php');

$pageTitle = "View Categories"; // Updated title


$categories = [
    [
        'id' => 1, 
        'name' => 'Προδημοτική Εκπαίδευση',
        'icon' => 'fas fa-book-reader',
        'color' => '#f8c74d',
        'count' => 5
    ],
    [
        'id' => 2, 
        'name' => 'Δημοτική Εκπαίδευση',
        'icon' => 'fas fa-chalkboard-teacher',
        'color' => '#1cc88a',
        'count' => 8
    ],
    [
        'id' => 3, 
        'name' => 'Ειδική Εκπαίδευση',
        'icon' => 'fas fa-user-graduate',
        'color' => '#36b9cc',
        'count' => 3
    ],
    [
        'id' => 4, 
        'name' => 'Μέση Γενική Εκπαίδευση',
        'icon' => 'fas fa-graduation-cap',
        'color' => '#4e73df',
        'count' => 2
    ],
    [
        'id' => 5, 
        'name' => 'Μέση Τεχνική Εκπαίδευση',
        'icon' => 'fas fa-tools',
        'color' => '#f6c23e',
        'count' => 1
    ]
    ,

    [
        'id' => 6, 
        'name' => 'Ειδικοί Καταλόγοι εκπαιδευτικών με αναπηρίες',
        'icon' => 'fas fa-info-circle',
        'color' => '#e74a3b',
        'count' => 4
    ]
];

// Filter categories if search term is provided
$filteredCategories = $categories;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($searchTerm)) {
    $filteredCategories = array_filter($categories, function($category) use ($searchTerm) {
        return stripos($category['name'], $searchTerm) !== false;
    });
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
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }
        
        .content-wrapper {
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .page-title {
            font-weight: 700;
            text-align: center;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .page-description {
            color: #6c757d;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .search-container {
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        
        .category-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-right: 1rem;
        }
        
        .category-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .category-count {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .category-link {
            text-decoration: none;
            color: inherit;
        }
        
        .badge-count {
            background-color: #4e73df;
            color: white;
            padding: 0.4rem 0.6rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <?php include_once('../components/sidebar/sidebar.php'); ?>
    
    <div class="container content-wrapper">
        <div class="page-header">
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            <p class="page-description">Browse through the available categories of appointment lists</p>
        </div>
        


        
        <!-- Categories List -->
        <div class="categories-container">
            <?php if (empty($filteredCategories)): ?>
                <div class="card empty-state">
                    <div class="card-body">
                        <i class="fas fa-folder-open"></i>
                        <h4>No categories found</h4>
                        <p class="text-muted">Try searching with different terms or browse all categories.</p>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-primary">View All Categories</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($filteredCategories as $category): ?>
                    <div class="col-md-12">
                        <a href="view-lists/daskaloi.php?id=<?php echo $category['id']; ?>" class="category-link">
                            <div class="card category-card">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="category-icon" style="background-color: <?php echo $category['color']; ?>">
                                            <i class="<?php echo $category['icon']; ?>"></i>
                                        </div>
                                        <div>
                                            <h5 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h5>
                                            <div class="category-count">
                                                <i class="fas fa-list-alt me-1"></i> Contains <?php echo $category['count']; ?> lists
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge-count"><?php echo $category['count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>