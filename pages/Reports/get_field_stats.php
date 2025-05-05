<?php
include_once('../../database/db_connect.php');

header('Content-Type: application/json');

$field = $_GET['field'] ?? '';
$type = $_GET['type'] ?? '';
$season = $_GET['season'] ?? '';
$year = $_GET['year'] ?? '';

// Base conditions for queries
$conditions = "WHERE c.fields = ? AND c.type = ? AND c.season = ? AND c.year = ?";
$params = [$field, $type, $season, $year];

// Get detailed statistics
$query = "SELECT 
            COUNT(*) as total_candidates,
            ROUND(AVG(r.points), 1) as avg_points,
            ROUND(MAX(r.points), 1) as max_points,
            ROUND(MIN(r.points), 1) as min_points,
            ROUND(AVG(r.experience), 1) as avg_experience,
            ROUND(AVG(r.titleGrade), 1) as avg_grade,
            COUNT(DISTINCT r.fullName) as unique_candidates
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          $conditions";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssi", ...$params);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Format response data
$response = [
    'stats' => [
        'Total Candidates' => $stats['total_candidates'],
        'Average Points' => $stats['avg_points'],
        'Highest Points' => $stats['max_points'],
        'Lowest Points' => $stats['min_points'],
        'Average Experience' => $stats['avg_experience'] . ' years',
        'Average Grade' => $stats['avg_grade']
    ],
    'pointsDistribution' => [
        'labels' => ['90-100', '80-89', '70-79', 'Below 70'],
        'data' => [0, 0, 0, 0] // Will be updated below
    ],
    'experienceDistribution' => [
        'labels' => [],
        'data' => []
    ]
];

// Get points distribution
$query = "SELECT 
            CASE 
                WHEN points >= 90 THEN 0
                WHEN points >= 80 THEN 1
                WHEN points >= 70 THEN 2
                ELSE 3
            END as range_index,
            COUNT(*) as count
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          $conditions
          GROUP BY range_index";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssi", ...$params);
$stmt->execute();
$points_dist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($points_dist as $range) {
    $response['pointsDistribution']['data'][$range['range_index']] = (int)$range['count'];
}

// Get experience distribution
$query = "SELECT 
            FLOOR(experience) as years,
            COUNT(*) as count
          FROM categories c
          JOIN rankinglist r ON c.categoryID = r.categoryID
          $conditions
          GROUP BY years
          ORDER BY years";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("sssi", ...$params);
$stmt->execute();
$exp_dist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$response['experienceDistribution']['labels'] = array_map(function($item) {
    return $item['years'] . ' years';
}, $exp_dist);
$response['experienceDistribution']['data'] = array_map('intval', array_column($exp_dist, 'count'));

echo json_encode($response);