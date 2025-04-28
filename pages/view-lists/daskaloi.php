<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once('../../database/db_connect.php');

// Get category ID from URL
$categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($categoryId <= 0) {
    header("Location: ../view-lists.php");
    exit();
}

// Category data - match the categories from view-lists.php
$categories = [
    1 => [
        'id' => 1, 
        'name' => 'Προδημοτική Εκπαίδευση',
        'icon' => 'fas fa-book-reader',
        'color' => '#f8c74d',
        'description' => 'Κατάλογοι διοριστέων εκπαιδευτικών προδημοτικής εκπαίδευσης'
    ],
    2 => [
        'id' => 2, 
        'name' => 'Δημοτική Εκπαίδευση',
        'icon' => 'fas fa-chalkboard-teacher',
        'color' => '#1cc88a',
        'description' => 'Κατάλογοι διοριστέων εκπαιδευτικών δημοτικής εκπαίδευσης'
    ],
    3 => [
        'id' => 3, 
        'name' => 'Ειδική Εκπαίδευση',
        'icon' => 'fas fa-user-graduate',
        'color' => '#36b9cc',
        'description' => 'Κατάλογοι διοριστέων εκπαιδευτικών ειδικής εκπαίδευσης'
    ],
    4 => [
        'id' => 4, 
        'name' => 'Μέση Γενική Εκπαίδευση',
        'icon' => 'fas fa-graduation-cap',
        'color' => '#4e73df',
        'description' => 'Κατάλογοι διοριστέων εκπαιδευτικών μέσης γενικής εκπαίδευσης'
    ],
    5 => [
        'id' => 5, 
        'name' => 'Μέση Τεχνική Εκπαίδευση',
        'icon' => 'fas fa-tools',
        'color' => '#f6c23e',
        'description' => 'Κατάλογοι διοριστέων εκπαιδευτικών μέσης τεχνικής εκπαίδευσης'
    ],
    6 => [
        'id' => 6, 
        'name' => 'Ειδικοί Καταλόγοι εκπαιδευτικών με αναπηρίες',
        'icon' => 'fas fa-info-circle',
        'color' => '#e74a3b',
        'description' => 'Ειδικοί κατάλογοι διοριστέων εκπαιδευτικών με αναπηρίες'
    ]
];

// Check if category exists
if (!isset($categories[$categoryId])) {
    header("Location: ../view-lists.php");
    exit();
}

$category = $categories[$categoryId];

// Lists data for the selected category - in a real app, this would come from a database
$lists = [];

// Generate sample lists based on category
switch ($categoryId) {
    case 1: // Προδημοτική Εκπαίδευση
        $lists = [
            ['id' => 101, 'title' => 'Κατάλογος Νηπιαγωγών (ΠΕ60)', 'year' => '2023', 'count' => 780],
            ['id' => 102, 'title' => 'Κατάλογος Βρεφονηπιοκόμων', 'year' => '2023', 'count' => 450],
            ['id' => 103, 'title' => 'Κατάλογος Ειδικής Αγωγής Προδημοτικής', 'year' => '2022', 'count' => 320],
            ['id' => 104, 'title' => 'Κατάλογος Μουσικής Προδημοτικής', 'year' => '2022', 'count' => 180],
            ['id' => 105, 'title' => 'Κατάλογος Αγγλικής Γλώσσας Προδημοτικής', 'year' => '2021', 'count' => 140],
        ];
        break;
    case 2: // Δημοτική Εκπαίδευση
        $lists = [
            ['id' => 201, 'title' => 'Κατάλογος Δασκάλων (ΠΕ70)', 'year' => '2023', 'count' => 1250],
            ['id' => 202, 'title' => 'Κατάλογος Αγγλικής Γλώσσας (ΠΕ06)', 'year' => '2023', 'count' => 420],
            ['id' => 203, 'title' => 'Κατάλογος Φυσικής Αγωγής (ΠΕ11)', 'year' => '2022', 'count' => 380],
            ['id' => 204, 'title' => 'Κατάλογος Μουσικής (ΠΕ79)', 'year' => '2022', 'count' => 250],
            ['id' => 205, 'title' => 'Κατάλογος Πληροφορικής (ΠΕ86)', 'year' => '2021', 'count' => 180],
            ['id' => 206, 'title' => 'Κατάλογος Θεατρικής Αγωγής (ΠΕ91)', 'year' => '2021', 'count' => 120],
            ['id' => 207, 'title' => 'Κατάλογος Εικαστικών (ΠΕ08)', 'year' => '2021', 'count' => 110],
            ['id' => 208, 'title' => 'Κατάλογος Γαλλικής Γλώσσας (ΠΕ05)', 'year' => '2020', 'count' => 90],
        ];
        break;
    case 3: // Ειδική Εκπαίδευση
        $lists = [
            ['id' => 301, 'title' => 'Κατάλογος Ειδικής Αγωγής Δασκάλων (ΠΕ71)', 'year' => '2023', 'count' => 480],
            ['id' => 302, 'title' => 'Κατάλογος Ειδικής Αγωγής Νηπιαγωγών (ΠΕ61)', 'year' => '2022', 'count' => 320],
            ['id' => 303, 'title' => 'Κατάλογος Ψυχολόγων (ΠΕ23)', 'year' => '2021', 'count' => 250],
        ];
        break;
    case 4: // Μέση Γενική Εκπαίδευση
        $lists = [
            ['id' => 401, 'title' => 'Κατάλογος Φιλολόγων (ΠΕ02)', 'year' => '2023', 'count' => 1100],
            ['id' => 402, 'title' => 'Κατάλογος Μαθηματικών (ΠΕ03)', 'year' => '2023', 'count' => 850],
            ['id' => 403, 'title' => 'Κατάλογος Φυσικών (ΠΕ04)', 'year' => '2022', 'count' => 620],
        ];
        break;
    case 5: // Μέση Τεχνική Εκπαίδευση
        $lists = [
            ['id' => 501, 'title' => 'Κατάλογος Μηχανολόγων (ΠΕ82)', 'year' => '2023', 'count' => 320],
            ['id' => 502, 'title' => 'Κατάλογος Ηλεκτρολόγων (ΠΕ83)', 'year' => '2022', 'count' => 280],
            ['id' => 503, 'title' => 'Κατάλογος Ηλεκτρονικών (ΠΕ84)', 'year' => '2021', 'count' => 210],
        ];
        break;
    case 6: // Ειδικοί Καταλόγοι εκπαιδευτικών με αναπηρίες
        $lists = [
            ['id' => 601, 'title' => 'Κατάλογος Εκπαιδευτικών με Κινητική Αναπηρία', 'year' => '2023', 'count' => 120],
            ['id' => 602, 'title' => 'Κατάλογος Εκπαιδευτικών με Προβλήματα Όρασης', 'year' => '2022', 'count' => 85],
            ['id' => 603, 'title' => 'Κατάλογος Εκπαιδευτικών με Προβλήματα Ακοής', 'year' => '2022', 'count' => 70],
            ['id' => 604, 'title' => 'Κατάλογος Εκπαιδευτικών με Λοιπές Αναπηρίες', 'year' => '2021', 'count' => 110],
        ];
        break;
}

// Filter lists if search term is provided
$filteredLists = $lists;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($searchTerm)) {
    $filteredLists = array_filter($lists, function($list) use ($searchTerm) {
        return stripos($list['title'], $searchTerm) !== false;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - Lists</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    
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
        
        .category-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .category-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .category-description {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .search-container {
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        
        .breadcrumb-container {
            margin-bottom: 1.5rem;
        }
        
        .list-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        
        .list-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .list-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .list-meta {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .list-meta div {
            margin-right: 1rem;
        }
        
        .badge-year {
            background-color: #4e73df;
            color: white;
            padding: 0.25rem 0.5rem;
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
    <?php 
    // Set the baseUrl for sidebar (since we're in a subdirectory)
    $baseUrl = "../../pages/";
    include_once('../../components/sidebar/sidebar.php'); 
    ?>
    
    <div class="container content-wrapper">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../homepage.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="../view-lists.php">Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
                </ol>
            </nav>
        </div>
        
        <!-- Category Header -->
        <div class="category-header">
            <div class="category-icon" style="background-color: <?php echo $category['color']; ?>">
                <i class="<?php echo $category['icon']; ?>"></i>
            </div>
            <h1 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h1>
            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
        </div>
        
        <!-- Search Box -->
        <div class="search-container">
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="mb-4">
                <input type="hidden" name="id" value="<?php echo $categoryId; ?>">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search lists..." name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($searchTerm)): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $categoryId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lists Container -->
        <div class="lists-container">
            <?php if (empty($filteredLists)): ?>
                <div class="card empty-state">
                    <div class="card-body">
                        <i class="fas fa-list-alt"></i>
                        <h4>No lists found</h4>
                        <p class="text-muted">Try searching with different terms or browse all lists.</p>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $categoryId; ?>" class="btn btn-primary">View All Lists</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($filteredLists as $list): ?>
                    <div class="col-12">
                        <div class="card list-card mb-3">
                            <div class="card-body">
                                <h5 class="list-title"><?php echo htmlspecialchars($list['title']); ?></h5>
                                <div class="list-meta">
                                    <div>
                                        <i class="far fa-calendar-alt me-1"></i> 
                                        <span class="badge-year"><?php echo $list['year']; ?></span>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-graduate me-1"></i> 
                                        <?php echo number_format($list['count']); ?> υποψήφιοι
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="#" class="btn btn-sm btn-primary view-list-btn">
                                        <i class="fas fa-eye me-1"></i> Προβολή
                                    </a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-sm btn-outline-primary favorite-btn" data-list-id="<?php echo $list['id']; ?>">
                                        <i class="far fa-star me-1"></i> Αποθήκευση
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle favorite buttons (demo functionality)
        document.addEventListener('DOMContentLoaded', function() {
            const favoriteButtons = document.querySelectorAll('.favorite-btn');
            
            favoriteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const listId = this.getAttribute('data-list-id');
                    const icon = this.querySelector('i');
                    
                    // Toggle star icon
                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.innerHTML = '<i class="fas fa-star me-1"></i> Αποθηκεύτηκε';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.innerHTML = '<i class="far fa-star me-1"></i> Αποθήκευση';
                    }
                    
                    // In a real application, you would send an AJAX request to save the favorite status
                    console.log(`List ${listId} favorite status toggled`);
                });
            });
        });
    </script>
</body>
</html>