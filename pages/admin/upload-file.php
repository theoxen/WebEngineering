<?php
// Set maximum execution time to 1000 seconds
ini_set('max_execution_time', '1000');
set_time_limit(1000);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

header('Content-Type: text/html; charset=utf-8'); // Set UTF-8 encoding
include_once('../../database/db_connect.php');
require '../../vendor/autoload.php'; // Include the PDF parser library
include_once('../../components/sidebar/sidebar.php');

use Smalot\PdfParser\Parser;

// Ορισμός του καταλόγου μεταφόρτωσης
$uploadDir = 'uploads/';
$uploadSuccess = false;
// Check if user is logged in and is admin

$pageTitle = "Admin API Keys Management";
// Variable to hold the response message
$responseMessage = '';
$responseClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Λήψη πληροφοριών για το μεταφορτωμένο αρχείο
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];

    // Επικύρωση τύπου αρχείου (επιτρέπονται μόνο PDF)
    if ($fileType !== 'application/pdf') {
        $responseMessage = "Σφάλμα: Επιτρέπονται μόνο αρχεία PDF.";
        $responseClass = 'error-message';
    } else {
        // Δημιουργία μοναδικού ονόματος αρχείου για αποφυγή συγκρούσεων
        $uniqueFileName = uniqid('pdf_', true) . '.pdf';
        $targetFile = $uploadDir . $uniqueFileName;

        // Διασφάλιση ότι ο κατάλογος μεταφόρτωσης υπάρχει
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Μετακίνηση του μεταφορτωμένου αρχείου
        if (move_uploaded_file($fileTmpName, $targetFile)) {
            $uploadSuccess = true;

            // Parse the PDF file
            $parser = new Parser();
            $pdf = $parser->parseFile($targetFile);
            $pdfText = $pdf->getText(); // Extract text from the PDF

            // Get form data
            $year = $_POST['year'];
            $season = $_POST['season'];
            $type = $_POST['type'];
            $fields = $_POST['fields'] ?? ''; // Assuming you have a "fields" input in your form

            // Insert metadata into the database
            $stmt = $mysqli->prepare("
                INSERT INTO categories (year, season, type, fields, file_path, pdf_content)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param(
                    "ssssss", // Data types: s = string
                    $year,
                    $season,
                    $type,
                    $fields,
                    $targetFile,
                    $pdfText
                );
                $stmt->execute();
                $stmt->close();

                $responseMessage = "File uploaded and metadata saved successfully.";
                $responseClass = 'success-message';
            } else {
                $responseMessage = "Database error: " . $mysqli->error;
                $responseClass = 'error-message';
            }
        } else {
            $responseMessage = "Failed to move the uploaded file.";
            $responseClass = 'error-message';
        }
    }
} else {
  //  $responseMessage = "No file uploaded or an error occurred.";
    $responseClass = 'error-message';
}

// Save selected values from form submission
$selectedYear = $_POST['year'] ?? '';
$selectedSeason = $_POST['season'] ?? '';
$selectedType = $_POST['type'] ?? '';
$selectedField = $_POST['fields'] ?? '';
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Form Container */
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        /* Heading Style */
        h1 {
            color: #007bff;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Form Styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 16px;
            font-weight: 600;
            color: #555;
            text-align: left;
            margin-bottom: 8px;
        }

        input[type="file"],
        select {
            padding: 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f9f9f9;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="file"]:focus,
        select:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        button {
            padding: 12px;
            font-size: 16px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        button:active {
            background-color: #004494;
        }

        /* Success/Error Messages */
        .message {
            font-size: 16px;
            font-weight: 600;
        }

        .success-message {
            color: green;
        }

        .error-message {
            color: red;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Ensure it's above everything */
            flex-direction: column;
        }

        .spinner-container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 80%;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            button {
                font-size: 14px;
            }

            input[type="file"],
            select {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1 class="mb-0">
            <i class="fas fa-upload text-primary me-2"></i>Upload File
        </h1>
        <div class="container mt-5">
            <h2>Ανεβάστε Αρχείο και Επιλέξτε Στοιχεία</h2>
            <form action="upload-file.php" method="post" enctype="multipart/form-data" class="upload-form">
                <div class="mb-3">
                    <label for="file" class="form-label">Επιλέξτε αρχείο:</label>
                    <input type="file" name="file" id="file" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="year" class="form-label">Επιλέξτε έτος:</label>
                    <select name="year" id="year" class="form-select" required>
                        <option value="">-- Επιλέξτε έτος --</option>
                        <?php
                        $currentYear = date("Y");
                        for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                            $selected = ($selectedYear == $i) ? 'selected' : '';
                            echo "<option value=\"$i\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="season" class="form-label">Επιλέξτε περίοδο:</label>
                    <select name="season" id="season" class="form-select" required>
                        <option value="">-- Επιλέξτε περίοδο --</option>
                        <option value="Ιούνιος" <?= ($selectedSeason == 'Ιούνιος') ? 'selected' : '' ?>>Ιούνιος</option>
                        <option value="Φεβρουάριος" <?= ($selectedSeason == 'Φεβρουάριος') ? 'selected' : '' ?>>Φεβρουάριος</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Επιλέξτε τύπο:</label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">-- Επιλέξτε τύπο --</option>
                        <option value="Δημοτική" <?= ($selectedType == 'Δημοτική') ? 'selected' : '' ?>>Δημοτική</option>
                        <option value="Ειδική Εκπαίδευση" <?= ($selectedType == 'Ειδική Εκπαίδευση') ? 'selected' : '' ?>>Ειδική Εκπαίδευση</option>
                        <option value="Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες" <?= ($selectedType == 'Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες') ? 'selected' : '' ?>>Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες</option>
                        <option value="Μέση Γενική" <?= ($selectedType == 'Μέση Γενική') ? 'selected' : '' ?>>Μέση Γενική</option>
                        <option value="Μέση Τεχνική" <?= ($selectedType == 'Μέση Τεχνική') ? 'selected' : '' ?>>Μέση Τεχνική</option>
                        <option value="Προδημοτική" <?= ($selectedType == 'Προδημοτική') ? 'selected' : '' ?>>Προδημοτική</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="fields" class="form-label">Επιλέξτε πεδίο:</label>
                    <select name="fields" id="fields" class="form-select" required>
                        <option value="">-- Επιλέξτε πεδίο --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary w-100">Υποβολή</button>
                </div>
            </form>

            <!-- Loading Overlay - This will cover everything when uploading -->
            <div id="loadingOverlay" class="loading-overlay d-none">
                <div class="spinner-container">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h4 class="mt-3">Processing your file</h4>
                    <p class="text-muted">Please wait while we upload and analyze the PDF...</p>
                </div>
            </div>

            <!-- Display messages dynamically -->
            <div id="responseMessage" class="message <?php echo $responseClass; ?>" style="display: <?php echo ($responseMessage ? 'block' : 'none'); ?>;">
                <?php echo $responseMessage; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeDropdown = document.getElementById('type');
            const fieldsDropdown = document.getElementById('fields');
            const selectedType = '<?= addslashes($selectedType) ?>'; 
            const selectedField = '<?= addslashes($selectedField) ?>';
            
            // Add form submission handler to show loading overlay
            document.querySelector('.upload-form').addEventListener('submit', function(e) {
                // Validate the form first
                if (this.checkValidity()) {
                    document.getElementById('loadingOverlay').classList.remove('d-none');
                }
            });
            
            const optionsMap = {
                "Δημοτική": ["Δασκάλων"],
                "Ειδική Εκπαίδευση": [
                    "Ειδικός Εκπαιδευτικός (Ειδικής Γυμναστικής)",
                    "Ειδικός Εκπαιδευτικός (Ειδικών Μαθησιακών, Νοητικών, Λειτουργικών και Προσαρμοστικών Δυσκολιών)",
                    "Ειδικός Εκπαιδευτικός (Εκπαιδευτικής Ακουολογίας)",
                    "Ειδικός Εκπαιδευτικός (Εργοθεραπείας)",
                    "Ειδικός Εκπαιδευτικός (Κωφών)",
                    "Ειδικός Εκπαιδευτικός (Λογοθεραπείας)",
                    "Ειδικός Εκπαιδευτικός (Μουσικοθεραπείας)",
                    "Ειδικός Εκπαιδευτικός (Τυφλών)",
                    "Ειδικός Εκπαιδευτικός (Φυσιοθεραπείας)"
                ],
                "Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες": [
                    "Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες (όλες οι ειδικότητες)"
                ],
                "Μέση Γενική": [
                    "Αγγλικών", "Βιολογίας", "Γαλλικών", "Γερμανικών", "Γεωγραφίας", "Γεωλογίας",
                    "Γεωπονίας", "Εμπορικών/Οικονομικών", "Θεατρολογίας", "Θρησκευτικών", "Ισπανικών",
                    "Ιταλικών", "Μαθηματικών", "Μουσικής", "Οικιακής Οικονομίας", "Πληροφορικής/Επιστήμης Η.Υ.",
                    "Ρωσσικών", "Συμβουλευτικής και Επαγγελματικής Αγωγής", "Τέχνης", "Τεχνολογίας",
                    "Τεχνολογίας (χωρίς μαθήματα)", "Τουρκικών", "Φιλολογικών", "Φυσικής", "Φυσικής Αγωγής",
                    "Φωτογραφικής Τέχνης", "Χημείας", "Ψυχολογίας"
                ],
                "Μέση Τεχνική": [
                    "Αργυροχοΐας Χρυσοχοΐας", "Γεωπονίας (Ανθοκομία-Κηποτεχνία)", "Γεωπονίας (Γενική)",
                    "Γεωπονίας (Ζωϊκή Παραγωγή)", "Γεωπονίας (Φυτική Παραγωγή)", "Γραφικών Τεχνών",
                    "Διακοσμητικής", "Δομικών (Αρχιτεκτονική)", "Δομικών (Πολιτική Μηχανική Δομικά Έργα)",
                    "Δομικών (Πολιτική Μηχανική Κατασκευές)", "Δομικών (Τοπογραφία)",
                    "Ηλεκτρολογία Εγκαταστάσεων", "Ηλεκτρολογίας (Γενική)", "Ηλεκτρολογίας (Ηλεκτρονική)",
                    "Ηλεκτρολογίας (Ρεύμα Ψηλής Έντασης)", "Ηλεκτρονικών (Επιδιόρθωση Τηλεοράσεων)",
                    "Κεραμικής-Αγγειοπλαστικής", "Κοπτικής-Ραπτικής","Κομμωτικής (Α5-7)", "Μηχανικής Αυτοκινήτων",
                    "Μηχανικής Ηλεκτρονικών Υπολογιστών", "Μηχανολογίας (Γενική)",
                    "Μηχανολογίας (Γεωργική Μηχαν/Αρδεύσεις)", "Μηχανολογίας (Γεωργική Μηχανική)",
                    "Μηχανολογίας (Θερμοδυναμικής Ενέργειας)", "Μηχανολογίας (Μηχανική Παραγωγής)",
                    "Ξενοδοχειακών (Γενικά)", "Ξενοδοχειακών (Επιστήμη Τεχνολογίας Τροφίμων)",
                    "Ξενοδοχειακών (Μαγειρική)", "Ξενοδοχειακών (Τεχνολογία Τροφίμων)",
                    "Ξενοδοχειακών (Τραπεζοκομία Α5)","Ξενοδοχειακών (Τραπεζοκομία Α8)", "Ξυλουργικής-Επιπλοποιίας", "Σχεδίασης Επίπλων",
                    "Σχεδίασης-Κατασκευής Ενδυμάτων", "Υποδηματοποιίας", "Χημικής Μηχανικής",
                    "Ψύξης-Κλιματισμού"
                ],
                "Προδημοτική": ["Νηπιαγωγών", "Νηπιαγωγών Α5-Α7"]
            };

            // Initial population of fields dropdown if type is already selected
            if (selectedType && optionsMap[selectedType]) {
                populateFieldsDropdown(selectedType, selectedField);
            }
            
            // Event listener for type changes
            typeDropdown.addEventListener('change', function () {
                populateFieldsDropdown(typeDropdown.value, '');
            });
            
            // Function to populate fields dropdown
            function populateFieldsDropdown(type, fieldToSelect) {
                fieldsDropdown.innerHTML = '<option value="">-- Επιλέξτε πεδίο --</option>';
                
                if (optionsMap[type]) {
                    optionsMap[type].forEach(function (field) {
                        const option = document.createElement('option');
                        option.value = field;
                        option.textContent = field;
                        option.selected = (field === fieldToSelect);
                        fieldsDropdown.appendChild(option);
                    });
                }
            }
        });

        // File validation and form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.querySelector('.upload-form');
            const fileInput = document.getElementById('pdfFile');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            uploadForm.addEventListener('submit', function(e) {
                // Validate the form first
                if (this.checkValidity() && fileInput.files.length > 0) {
                    // Show loading overlay
                    loadingOverlay.classList.remove('d-none');
                }
            });
        });
    </script>
</body>
</html>