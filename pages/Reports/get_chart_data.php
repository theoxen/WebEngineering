<?php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

include_once('../../database/db_connect.php');

header('Content-Type: application/json');

$chart = $_GET['chart'] ?? '';
$response = ['error' => 'Invalid chart type'];

switch ($chart) {
    case 'pointsChart':
        $data = $mysqli->query("
            SELECT FLOOR(points) as range_start, COUNT(*) as count
            FROM rankinglist
            WHERE points IS NOT NULL
            GROUP BY FLOOR(points)
            ORDER BY range_start
        ")->fetch_all(MYSQLI_ASSOC);

        $response = [
            'chartData' => [
                'labels' => array_map(fn($d) => $d['range_start'].'-'.($d['range_start']+1), $data),
                'datasets' => [[
                    'data' => array_column($data, 'count'),
                    'borderColor' => '#4e73df',
                    'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
                    'fill' => true
                ]]
            ]
        ];
        break;

    case 'experienceChart':
        $data = $mysqli->query("
            SELECT 
                CASE 
                    WHEN experience < 5 THEN '0-5'
                    WHEN experience < 10 THEN '5-10'
                    WHEN experience < 15 THEN '10-15'
                    WHEN experience < 20 THEN '15-20'
                    WHEN experience < 25 THEN '20-25'
                    ELSE '25+'
                END as range_label,
                COUNT(*) as count
            FROM rankinglist
            WHERE experience IS NOT NULL
            GROUP BY range_label
            ORDER BY MIN(experience)
        ")->fetch_all(MYSQLI_ASSOC);

        $response = [
            'chartData' => [
                'labels' => array_column($data, 'range_label'),
                'datasets' => [[
                    'data' => array_column($data, 'count'),
                    'backgroundColor' => '#36b9cc'
                ]]
            ]
        ];
        break;

    case 'fieldTypeChart':
        $data = $mysqli->query("
            SELECT 
                c.fields,
                c.type,
                COUNT(DISTINCT r.fullName) as candidate_count
            FROM categories c
            LEFT JOIN rankinglist r ON c.categoryID = r.categoryID
            GROUP BY c.fields, c.type
            ORDER BY c.fields, c.type
        ")->fetch_all(MYSQLI_ASSOC);

        $response = [
            'chartData' => [
                'labels' => array_map(fn($d) => $d['fields'] . ' (' . $d['type'] . ')', $data),
                'datasets' => [[
                    'data' => array_column($data, 'candidate_count'),
                    'backgroundColor' => array_map(
                        fn() => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ',0.7)',
                        range(1, count($data))
                    )
                ]]
            ]
        ];
        break;
}

echo json_encode($response);