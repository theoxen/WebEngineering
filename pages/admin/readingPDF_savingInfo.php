<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();
$pdfFilePath = __DIR__ . '/uploads/pdf_680e4c6523c1d5.61441784.pdf';

if (!file_exists($pdfFilePath)) {
    die("PDF file not found: " . $pdfFilePath);
}

try {
    $pdf = $parser->parseFile($pdfFilePath);
    $text = $pdf->getText();
    $lines = explode("\n", $text);
} catch (Exception $e) {
    die("Error parsing PDF: " . $e->getMessage());
}

if (empty($lines)) {
    die("No lines extracted from the PDF. Check the file path or content.");
}

function parseLine($line) {
    $result = [];
    $cursor = 0;

    preg_match('/^\d+/', $line, $matches);
    $result['ranking'] = isset($matches[0]) ? (int)$matches[0] : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/[\p{Greek}\s]+/u', substr($line, $cursor), $matches);
    $result['fullName'] = isset($matches[0]) ? trim($matches[0]) : '';
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d+/', substr($line, $cursor), $matches);
    $result['appNum'] = isset($matches[0]) ? (int)$matches[0] : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d+,\d{2}/', substr($line, $cursor), $matches);
    $result['points'] = isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d{2}\/\d{2}\/\d{4}/', substr($line, $cursor), $matches);
    $result['titleDate'] = $matches[0] ?? null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d/', substr($line, $cursor), $matches);
    $result['titleGrade'] = isset($matches[0]) ? (int)$matches[0] : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d/', substr($line, $cursor), $matches);
    $result['extraQualifications'] = isset($matches[0]) ? (int)$matches[0] : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d+,\d/', substr($line, $cursor), $matches);
    $result['experience'] = isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d+,\d{2}/', substr($line, $cursor), $matches);
    $result['army'] = isset($matches[0]) ? (float)str_replace(',', '.', $matches[0]) : null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d{2}\/\d{2}\/\d{4}/', substr($line, $cursor), $matches);
    $result['registrationDate'] = $matches[0] ?? null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/\d{2}\/\d{2}\/\d{4}/', substr($line, $cursor), $matches);
    $result['birthdayDate'] = $matches[0] ?? null;
    $cursor += strlen($matches[0] ?? '');

    preg_match('/[\p{Greek}\.]+/u', substr($line, $cursor), $matches);
    $result['notes'] = isset($matches[0]) ? trim($matches[0]) : '';

    return $result;
}

$rankinglist = [];
foreach ($lines as $line) {
    $rankinglist[] = parseLine($line);
}

if (empty($rankinglist)) {
    die("No data extracted from the PDF. Check the pattern or input lines.");
}

$insertQuery = "INSERT INTO rankinglist (ranking, fullName, appNum, points, titleDate, titleGrade, extraQualifications, experience, army, registrationDate, birthdayDate, notes, categoryID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertStmt = $mysqli->prepare($insertQuery);
if (!$insertStmt) {
    die("Failed to prepare insert statement: " . $mysqli->error);
}

$categoryID = 931;
foreach ($rankinglist as $applicant) {
    // Ensure the ranking is valid (line starts with a number)
    if (!is_numeric($applicant['ranking'])) {
        error_log("Skipping invalid applicant: " . json_encode($applicant));
        continue; // Skip this record
    }
// Convert dates from DD/MM/YYYY to YYYY-MM-DD
$applicant['titleDate'] = !empty($applicant['titleDate']) ? 
DateTime::createFromFormat('d/m/Y', $applicant['titleDate'])->format('Y-m-d') : null;

$applicant['registrationDate'] = !empty($applicant['registrationDate']) ? 
DateTime::createFromFormat('d/m/Y', $applicant['registrationDate'])->format('Y-m-d') : null;

$applicant['birthdayDate'] = !empty($applicant['birthdayDate']) ? 
DateTime::createFromFormat('d/m/Y', $applicant['birthdayDate'])->format('Y-m-d') : null;

    $insertStmt->bind_param(
        "issdssddssssi",
        $applicant['ranking'],
        $applicant['fullName'],
        $applicant['appNum'],
        $applicant['points'],
        $applicant['titleDate'],
        $applicant['titleGrade'],
        $applicant['extraQualifications'],
        $applicant['experience'],
        $applicant['army'],
        $applicant['registrationDate'],
        $applicant['birthdayDate'],
        $applicant['notes'],
        $categoryID
    );

    if (!$insertStmt->execute()) {
        error_log("Failed to execute insert statement: " . $insertStmt->error);
    }
}

echo "Data inserted successfully.";

$insertStmt->close();
$mysqli->close();