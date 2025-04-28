<?php
header('Content-Type: text/html; charset=utf-8'); // Set UTF-8 encoding
include_once('../../database/db_connect.php');
require '../../vendor/autoload.php'; // Include the PDF parser library


use Smalot\PdfParser\Parser;

// Ορισμός του καταλόγου μεταφόρτωσης
$uploadDir = 'uploads/';
$uploadSuccess = false;

// Έλεγχος αν έχει μεταφορτωθεί αρχείο
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Λήψη πληροφοριών για το μεταφορτωμένο αρχείο
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];

    // Επικύρωση τύπου αρχείου (επιτρέπονται μόνο PDF)
    if ($fileType !== 'application/pdf') {
        die("Σφάλμα: Επιτρέπονται μόνο αρχεία PDF.");
    }

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

            echo "File uploaded and metadata saved successfully.";
        } else {
            die("Database error: " . $mysqli->error);
        }
    } else {
        echo "Failed to move the uploaded file.";
    }
} else {
    echo "No file uploaded or an error occurred.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Dropdown with File Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        select, input[type="file"], button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <form action="upload-file.php" method="post" enctype="multipart/form-data">
        <label for="file">Επιλέξτε αρχείο:</label>
        <input type="file" name="file" id="file" required>

        <label for="year">Επιλέξτε έτος:</label>
        <select name="year" id="year" required>
            <option value="">-- Επιλέξτε έτος --</option>
            <?php
            $currentYear = date("Y");
            for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                echo "<option value=\"$i\">$i</option>";
            }
            ?>
        </select>

        <label for="season">Επιλέξτε περίοδο:</label>
        <select name="season" id="season" required>
            <option value="">-- Επιλέξτε περίοδο --</option>
            <option value="Ιούνιος">Ιούνιος</option>
            <option value="Φεβρουάριος">Φεβρουάριος</option>
        </select>

        <label for="type">Επιλέξτε τύπο:</label>
        <select name="type" id="type" required>
            <option value="">-- Επιλέξτε τύπο --</option>
            <option value="Δημοτική">Δημοτική</option>
            <option value="Ειδική Εκπαίδευση">Ειδική Εκπαίδευση</option>
            <option value="Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες">Ειδικοί κατάλογοι εκπαιδευτικών με αναπηρίες</option>
            <option value="Μέση Γενική">Μέση Γενική</option>
            <option value="Μέση Τεχνική">Μέση Τεχνική</option>
            <option value="Προδημοτική">Προδημοτική</option>
        </select>

        <label for="fields">Επιλέξτε πεδίο:</label>
        <select name="fields" id="fields" required>
            <option value="">-- Επιλέξτε πεδίο --</option>
        </select>

        <button type="submit">Υποβολή</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeDropdown = document.getElementById('type');
            const fieldsDropdown = document.getElementById('fields');
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
                    "Κεραμικής-Αγγειοπλαστικής", "Κοπτικής-Ραπτικής", "Μηχανικής Αυτοκινήτων",
                    "Μηχανικής Ηλεκτρονικών Υπολογιστών", "Μηχανολογίας (Γενική)",
                    "Μηχανολογίας (Γεωργική Μηχαν/Αρδεύσεις)", "Μηχανολογίας (Γεωργική Μηχανική)",
                    "Μηχανολογίας (Θερμοδυναμικής Ενέργειας)", "Μηχανολογίας (Μηχανική Παραγωγής)",
                    "Ξενοδοχειακών (Γενικά)", "Ξενοδοχειακών (Επιστήμη Τεχνολογίας Τροφίμων)",
                    "Ξενοδοχειακών (Μαγειρική)", "Ξενοδοχειακών (Τεχνολογία Τροφίμων)",
                    "Ξενοδοχειακών (Τραπεζοκομία)", "Ξυλουργικής-Επιπλοποιίας", "Σχεδίασης Επίπλων",
                    "Σχεδίασης-Κατασκευής Ενδυμάτων", "Υποδηματοποιίας", "Χημικής Μηχανικής",
                    "Ψύξης-Κλιματισμού"
                ],
                "Προδημοτική": ["Νηπιαγωγών", "Νηπιαγωγών Α5-Α7"]
            };

            typeDropdown.addEventListener('change', function () {
                const selectedType = typeDropdown.value;
                fieldsDropdown.innerHTML = '<option value="">-- Επιλέξτε πεδίο --</option>';

                if (optionsMap[selectedType]) {
                    optionsMap[selectedType].forEach(function (fields) {
                        const option = document.createElement('option');
                        option.value = fields;
                        option.textContent = fields;
                        fieldsDropdown.appendChild(option);
                    });
                }
            });
        });
    </script>
</body>
</html>