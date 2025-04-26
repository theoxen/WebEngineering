<?php
include '../database/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$toastClass = isset($_SESSION['toastClass']) ? $_SESSION['toastClass'] : "";

$formData = isset($_SESSION['formData']) ? $_SESSION['formData'] : array();

// Clearing session variables after retrieving them
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['toastClass']);
    unset($_SESSION['formData']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Save form data
    $formData = [
        'email' => isset($_POST['email']) ? trim($_POST['email']) : ''
    ];

    // Store in session for after redirect
    $_SESSION['formData'] = $formData;

    // Validate required fields
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $_SESSION['message'] = "Email and password are required";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $email = $formData['email'];
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Prepare a statement to avoid SQL injection
    $stmt = $mysqli->prepare("SELECT userId, username, email, password, role, email_verified FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // Check if a user exists with that email
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($userId, $username, $userEmail, $hashedPassword, $userRole, $emailVerified);
            $stmt->fetch();

            // Verify the password against the hashed password in the database
            if (password_verify($password, $hashedPassword)) {
                // Successful login: set session variables
                if ($emailVerified) {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['email'] = $userEmail;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $userRole;

                    // Redirect to homepage
                    header("Location: homepage.php");
                    exit();
                } else {
                    $_SESSION['message'] = "Please verify your email before logging in. Check your inbox for the verification link.";
                    $_SESSION['toastClass'] = "warning";
                }
            } else {
                $_SESSION['message'] = "Invalid email or password";
                $_SESSION['toastClass'] = "warning";
            }
        } else {
            $_SESSION['message'] = "Invalid email or password";
            $_SESSION['toastClass'] = "warning";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Database query error";
        $_SESSION['toastClass'] = "danger";
    }

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
    <title>Login</title>
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

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #b7b9cc;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eaecf4;
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .link-secondary {
            color: var(--primary-color) !important;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .link-secondary:hover {
            color: #2e59d9;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .box-input-field-container {
            padding: 40px;
        }

        @media screen and (max-width: 325px) {
            .box-input-field-container {
                padding: 20px;
            }
            
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
                    <div class="col-xl-6 col-lg-9 col-md-8">
                        <div class="auth-card card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <div class="box-input-field-container">
                                    <div class="text-center">
                                        <i class="fas fa-user-circle auth-icon fa-4x"></i>
                                        <h1 class="h4 text-gray-900 mb-4">Sign In</h1>
                                    </div>
                                    <form class="user" method="post" id="loginForm">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Email Address" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                                title="Please enter a valid email in format: name@example.com"
                                                value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>">
                                        </div>

                                        <div class="input-group mb-3 password-container">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Password" required>
                                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-block w-100 mt-3">
                                            Login
                                        </button>
                                    </form>
                                    <div class="text-center mb-3" style="margin-top: 5px;">
                                        <a href="forgot-password.php" class="link-secondary">Forgot Password?</a>
                                    </div>
                                    <div class="divider">
                                        <span>OR</span>
                                    </div>
                                    <div class="text-center">
                                        <p>Don't have an account? <a class="link-secondary" href="./register.php">Create Account</a></p>
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

        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // // Add sidebar toggle functionality
        // document.addEventListener('DOMContentLoaded', function() {
        //     const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
        //     const sidebar = document.querySelector('.sidebar-wrapper');
            
        //     if (sidebarCollapseBtn) {
        //         sidebarCollapseBtn.addEventListener('click', function() {
        //             sidebar.classList.toggle('active');
        //         });
        //     }
        // });
    </script>
    
</body>

</html>