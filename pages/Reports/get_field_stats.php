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
    ]
];

// Points distribution: bins 0-1, 1-2, ..., 9-10
$points_labels = [];
$points_data = [];
$points_colors = [];
for ($i = 0; $i < 10; $i++) {
    $min = $i;
    $max = $i + 1;
    $label = "$min-$max";
    $stmt = $mysqli->prepare(
        "SELECT COUNT(*) as count 
         FROM rankinglist r 
         JOIN categories c ON r.categoryID = c.categoryID 
         WHERE c.fields=? AND c.type=? AND c.season=? AND c.year=? AND r.points >= ? AND r.points < ?"
    );
    $stmt->bind_param("ssssdd", $field, $type, $season, $year, $min, $max);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $points_labels[] = $label;
    $points_data[] = (int)$count;
    $points_colors[] = '#4e73df';
}
$response['pointsDistribution'] = [
    'labels' => $points_labels,
    'data' => $points_data,
    'colors' => $points_colors
];

// Experience distribution
$query = "SELECT 
            FLOOR(r.experience) as years,
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

$response['experienceDistribution'] = [
    'labels' => array_map(function($item) { return $item['years'] . ' years'; }, $exp_dist),
    'data' => array_map('intval', array_column($exp_dist, 'count'))
];

echo json_encode($response);