<?php
include '../database/db_connect.php';
include '../utils/mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$toastClass = isset($_SESSION['toastClass']) ? $_SESSION['toastClass'] : "";

// Clear session variables after retrieving them
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['toastClass']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Λάθος μορφή email";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Check if the email exists in the database
    $stmt = $mysqli->prepare("SELECT userId, username, email_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if email is verified
        if (!$user['email_verified']) {
            $_SESSION['message'] = "Παρακαλούμε επαληθεύστε πρώτα το email σας. Ελέγξτε τα εισερχόμενά σας για τον σύνδεσμο επαλήθευσης.";
            $_SESSION['toastClass'] = "warning";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        // Generate a reset token
        $resetToken = bin2hex(random_bytes(32));
        $userId = $user['userId'];
        $username = $user['username'];
        
        // Set token expiration (1 hour from now) TODO: DATE TIME FOR SOME REASON IS NOT WORKING PROPERLY MAYBE DUE TO TIMEZONE (+2 is only 1 hour ahead instead of 2)
        $expiryTime = date('Y-m-d H:i:s', strtotime('+2 hour'));
        
        // Store the token in the database
        $updateStmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE userId = ?");
        $updateStmt->bind_param("ssi", $resetToken, $expiryTime, $userId);
        
        if ($updateStmt->execute()) {
            // Send reset email
            if (sendPasswordResetEmail($email, $username, $resetToken)) {
                $_SESSION['message'] = "Ο σύνδεσμος επαναφοράς κωδικού πρόσβασης έχει σταλεί στο email σας.";
                $_SESSION['toastClass'] = "success";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['message'] = "Αποτυχία αποστολής email. Παρακαλούμε δοκιμάστε ξανά.";
                $_SESSION['toastClass'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Προέκυψε σφάλμα. Παρακαλούμε δοκιμάστε ξανά.";
            $_SESSION['toastClass'] = "danger";
        }
        $updateStmt->close();
    } else {
        // Don't reveal if email exists for security
        $_SESSION['message'] = "Εάν το email σας υπάρχει στο σύστημά μας, θα λάβετε έναν σύνδεσμο επαναφοράς κωδικού πρόσβασης.";
        $_SESSION['toastClass'] = "success";
        header("Location: login.php");
        exit();
    }
    $stmt->close();
    $mysqli->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <title>Forgot Password</title>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .auth-card {
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.5rem 0;
        }

        .auth-icon {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            border: 1px solid #d1d3e2;
        }

        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            border-radius: 0.5rem 0 0 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            height: calc(1.5em + 1.5rem + 2px);
        }

        .input-group-text i {
            display: flex;
            align-items: center;
            line-height: 1;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: #4262c5;
            border-color: #4262c5;
            transform: translateY(-2px);
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>

<body class="auth-page">
    <div class="content-wrapper">
        <?php include '../components/sidebar/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Toast container -->
            <?php if ($message): ?>
                <div class="toast-container">
                    <div class="toast align-items-center text-white bg-<?php echo $toastClass; ?> border-0 show" role="alert"
                        aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-<?php echo $toastClass == 'success' ? 'check-circle' : ($toastClass == 'warning' ? 'exclamation-circle' : 'times-circle'); ?> me-2"></i>
                                <?php echo $message; ?>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                                aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-xl-5 col-lg-6 col-md-8">
                        <div class="auth-card card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <div class="p-5">
                                    <div class="text-center">
                                        <i class="fas fa-unlock-alt auth-icon fa-4x"></i>
                                        <h1 class="h4 text-gray-900 mb-4">Ξεχάσατε τον Κωδικό Πρόσβασής σας;</h1>
                                        <p class="mb-4">Εισάγετε τη διεύθυνση email σας και θα σας στείλουμε έναν σύνδεσμο για την επαναφορά του κωδικού πρόσβασής σας.</p>
                                    </div>
                                    <form class="user" method="post">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Διεύθυνση Email" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                                title="Please enter a valid email in format: name@example.com">
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-block w-100 mt-3">
                                            Eπαναφορά Κωδικού Πρόσβασης
                                        </button>
                                    </form>
                                    <div class="text-center mt-3">
                                        <a class="link-secondary" href="login.php"><i class="fas fa-arrow-left me-1"></i> Πίσω στην Σύνδεση</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast functionality
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 5000 });
        });
        toastList.forEach(toast => toast.show());
    </script>
</body>
</html>