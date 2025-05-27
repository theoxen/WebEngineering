<?php
include '../database/db_connect.php';

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

// Initialize variables
$token = isset($_GET['token']) ? $_GET['token'] : '';
$validToken = false;
$userId = null;
$username = '';

// Validate token
if (!empty($token)) {
    $stmt = $mysqli->prepare("SELECT userId, username FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $validToken = true;
        $userData = $result->fetch_assoc();
        $userId = $userData['userId'];
        $username = $userData['username'];
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate password
    if (strlen($password) < 8) {
        $_SESSION['message'] = "Ο κωδικός πρόσβασης πρέπει να περιέχει τουλάχιστον 8 χαρακτήρες";
        $_SESSION['toastClass'] = "warning";
        header("Location: reset-password.php?token=" . $token);
        exit();
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = "Ο κωδικός επαλήθευσης δεν ταιριάζει με τον νέο κωδικό πρόσβασης";
        $_SESSION['toastClass'] = "warning";
        header("Location: reset-password.php?token=" . $token);
        exit();
    }

    // Update password and clear reset token
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE userId = ?");
    $updateStmt->bind_param("si", $passwordHash, $userId);

    if ($updateStmt->execute()) {
        $_SESSION['message'] = "Ο κωδικός πρόσβασής σας έχει επαναρυθμιστεί με επιτυχία. Μπορείτε τώρα να συνδεθείτε.";
        $_SESSION['toastClass'] = "success";
        header("Location: login.php");
        $updateStmt->close();
        exit();

    } else {
        $_SESSION['message'] = "Σφάλμα επαναφοράς κωδικού πρόσβασης: " . $mysqli->error;
        $_SESSION['toastClass'] = "danger";
        header("Location: reset-password.php?token=" . $token);
        $updateStmt->close();
        exit();
    }
}

if (!$validToken && !empty($token)) {
    $_SESSION['message'] = "Μη έγκυρο ή ληγμένο σύνδεσμο επαναφοράς κωδικού πρόσβασης.";
    $_SESSION['toastClass'] = "warning";
    header("Location: forgot-password.php");
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
    <title>Reset Password</title>
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

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 16px;
            cursor: pointer;
            color: #aaa;
            z-index: 10;
        }

        .progress {
            height: 5px;
            margin-bottom: 10px;
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
                    <div class="toast align-items-center text-white bg-<?php echo $toastClass; ?> border-0 show"
                        role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i
                                    class="fas fa-<?php echo $toastClass == 'success' ? 'check-circle' : ($toastClass == 'warning' ? 'exclamation-circle' : 'times-circle'); ?> me-2"></i>
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
                        <?php if ($validToken): ?>
                            <div class="auth-card card o-hidden border-0 shadow-lg my-5">
                                <div class="card-body p-0">
                                    <div class="p-5">
                                        <div class="text-center">
                                            <i class="fas fa-key auth-icon fa-4x"></i>
                                            <h1 class="h4 text-gray-900 mb-4">Επαναφορά Κωδικού Προσβασης</h1>
                                            <p class="mb-4">Γεια σου <?php echo htmlspecialchars($username); ?>, παρακαλώ πληκτρολογήστε τον νέο κωδικό πρόσβασης.</p>
                                        </div>
                                        <form class="user" method="post">
                                            <div class="input-group mb-3 password-container">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="password" name="password"
                                                    placeholder="New Password" required>
                                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                            </div>
                                            <div class="form-text text-muted mb-2">
                                                Ο κωδικός πρόσβασης πρέπει να περιέχει τουλάχιστον 8 χαρακτήρες
                                            </div>

                                            <div class="progress mb-3">
                                                <div id="password-strength" class="progress-bar" role="progressbar"
                                                    style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small id="passwordHelpBlock" class="form-text text-muted mb-3">
                                                Δύναμη Κωδικού: <span id="password-strength-text">Κανένας Κωδικός</span>
                                            </small>

                                            <div class="input-group mb-3 password-container">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="confirm_password"
                                                    name="confirm_password" placeholder="Confirm New Password" required>
                                                <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-block w-100 mt-3">
                                                Επαναφορά Κωδικού
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-card card o-hidden border-0 shadow-lg my-5">
                                <div class="card-body p-0">
                                    <div class="p-5 text-center">
                                        <i class="fas fa-exclamation-triangle auth-icon fa-4x text-warning"></i>
                                        <h1 class="h4 text-gray-900 mb-4">Λάθος Σύνδεσμος Επαναφοράς Κωδικου</h1>
                                        <p>Ο σύνδεσμος επαναφοράς κωδικού πρόσβασης είναι μη έγκυρος ή έχει λήξει.</p>
                                        <a href="forgot-password.php" class="btn btn-primary mt-3">Ζητήστε Νέο Σύνδεσμο Επαναφοράς</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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

        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm_password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        if (toggleConfirmPassword && confirmPassword) {
            toggleConfirmPassword.addEventListener('click', function () {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('password-strength');
        const strengthText = document.getElementById('password-strength-text');

        if (passwordInput && strengthBar && strengthText) {
            passwordInput.addEventListener('input', function () {
                const value = passwordInput.value;
                let strength = 0;
                let status = '';
                let color = '';

                if (value.length >= 8) strength += 20;
                if (/[A-Z]/.test(value)) strength += 20;
                if (/[a-z]/.test(value)) strength += 20;
                if (/[0-9]/.test(value)) strength += 20;
                if (/[^A-Za-z0-9]/.test(value)) strength += 20;

                if (strength <= 20) {
                    status = 'Very weak';
                    color = '#dc3545'; // danger
                } else if (strength <= 40) {
                    status = 'Weak';
                    color = '#fd7e14'; // warning
                } else if (strength <= 60) {
                    status = 'Medium';
                    color = '#ffc107'; // warning lighter
                } else if (strength <= 80) {
                    status = 'Strong';
                    color = '#20c997'; // success lighter
                } else {
                    status = 'Very strong';
                    color = '#28a745'; // success
                }

                strengthBar.style.width = strength + '%';
                strengthBar.style.backgroundColor = color;
                strengthText.textContent = status;
                strengthText.style.color = color;
            });
        }
    </script>
</body>

</html>