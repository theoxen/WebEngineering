<?php
include '../database/db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";
$toastClass = isset($_SESSION['toastClass']) ? $_SESSION['toastClass'] : "";
// Store form data in variables
$formData = isset($_SESSION['formData']) ? $_SESSION['formData'] : array();


// Clear session variables after retrieving them
if (isset($_SESSION['message'])) {
    unset($_SESSION['message']);
    unset($_SESSION['toastClass']);
    unset($_SESSION['formData']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Save form data
    $formData = [
        'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
        'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
        'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
        'dob' => isset($_POST['dob']) ? trim($_POST['dob']) : ''
    ];

    // Store in session for after redirect
    $_SESSION['formData'] = $formData;

    // Validate required fields
    if (
        empty($_POST['username']) || empty($_POST['email']) ||
        empty($_POST['phone']) || empty($_POST['dob']) ||
        empty($_POST['password'])
    ) {
        $_SESSION['message'] = "All fields are required";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }


    if (strlen($_POST['password']) < 8) {
        $_SESSION['message'] = "Password must be at least 8 characters long";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $username = $formData['username'];
    $email = $formData['email'];
    $phone = $formData['phone'];
    $dob = $formData['dob'];
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Validate phone number (backend validation)
    if (!ctype_digit($phone)) {
        $_SESSION['message'] = "Phone number must contain only digits";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Validate date of birth
    $dateObj = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $dob) {
        $_SESSION['message'] = "Invalid date format";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Check if date of birth is in the past
    $today = new DateTime('today');
    if ($dateObj >= $today) {
        $_SESSION['message'] = "Date of birth must be in the past";
        $_SESSION['toastClass'] = "warning";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Hash the password only after all validations pass
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmailStmt = $mysqli->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $checkEmailStmt->store_result();

    $checkPhoneStmt = $mysqli->prepare("SELECT phoneNumber FROM users WHERE phoneNumber = ?");
    $checkPhoneStmt->bind_param("s", $phone);
    $checkPhoneStmt->execute();
    $checkPhoneStmt->store_result();

    $checkUsernameStmt = $mysqli->prepare("SELECT username FROM users WHERE username = ?");
    $checkUsernameStmt->bind_param("s", $username);
    $checkUsernameStmt->execute();
    $checkUsernameStmt->store_result();

    if ($checkEmailStmt->num_rows > 0) {
        $_SESSION['message'] = "Email address already exists";
        $_SESSION['toastClass'] = "warning";
    } else if ($checkPhoneStmt->num_rows > 0) {
        $_SESSION['message'] = "Phone number already exists";
        $_SESSION['toastClass'] = "warning";
    } else if ($checkUsernameStmt->num_rows > 0) {
        $_SESSION['message'] = "Username already exists";
        $_SESSION['toastClass'] = "warning";
    } else {
        // Prepare and bind
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, phoneNumber, dateOfBirth, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->bind_param("sssss", $username, $email, $phone, $dob, $passwordHash);
        

        if ($stmt->execute()) {
            // Get the newly created user ID
            $newUserId = $mysqli->insert_id;
            $stmt->close(); // Close the statement before exiting
            
            // Create default notification settings for the new user
            $settingsStmt = $mysqli->prepare("INSERT INTO user_notification_settings (userId, newCatalogNotify, catalogUpdateNotify, positionChangeNotify) VALUES (?, 0, 0, 0)");
            $settingsStmt->bind_param("i", $newUserId);
            $settingsStmt->execute();
            $settingsStmt->close();

            $_SESSION['message'] = "Account created successfully";
            $_SESSION['toastClass'] = "success";

            // Log the user in automatically
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';

            // Redirect to homepage instead of back to registration page
            header("Location: homepage.php");
            exit();
        } else {
            $stmt->close(); // Close the statement before exiting

            $_SESSION['message'] = "Error: " . $stmt->error;
            $_SESSION['toastClass'] = "danger";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    $checkEmailStmt->close();
    $checkPhoneStmt->close();
    $checkUsernameStmt->close();
    $mysqli->close();

    // Redirect to same page to prevent form resubmission
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
    <title>Create Account</title>
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
                    <div class="col-xl-5 col-lg-6 col-md-8">
                        <div class="auth-card card o-hidden border-0 shadow-lg my-5">
                            <div class="card-body p-0">
                                <div class="p-5">
                                    <div class="text-center">
                                        <i class="fas fa-user-circle auth-icon fa-4x"></i>
                                        <h1 class="h4 text-gray-900 mb-4">Create Your Account</h1>
                                    </div>
                                    <form class="user" method="post" id="registerForm">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username"
                                                placeholder="Username" required
                                                value="<?php echo isset($formData['username']) ? htmlspecialchars($formData['username']) : ''; ?>">
                                        </div>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Email Address" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                                title="Please enter a valid email in format: name@example.com"
                                                value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>">
                                        </div>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                placeholder="Phone Number" required pattern="[0-9]+"
                                                title="Please enter numbers only"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                value="<?php echo isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''; ?>">
                                        </div>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <input type="date" class="form-control" id="dob" name="dob"
                                                placeholder="Date of Birth" required
                                                value="<?php echo isset($formData['dob']) ? htmlspecialchars($formData['dob']) : ''; ?>">
                                        </div>
                                        <div class="input-group mb-3 password-container">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Password" required>
                                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                        </div>
                                        <div class="form-text text-muted mb-2">
                                            Password must be at least 8 characters long
                                        </div>

                                        <div class="progress mb-3" style="height: 5px;">
                                            <div id="password-strength" class="progress-bar" role="progressbar"
                                                style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small id="passwordHelpBlock" class="form-text text-muted mb-3">
                                            Password strength: <span id="password-strength-text">No password</span>
                                        </small>

                                        <button type="submit" class="btn btn-primary btn-block w-100 mt-3">
                                            Create Account
                                        </button>
                                    </form>
                                    <div class="divider">
                                        <span>OR</span>
                                    </div>
                                    <div class="text-center">
                                        <p>Already have an account? <a class="link-secondary" href="./login.php">Sign In</a></p>
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

        // Phone number validation - only allow digits
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('keypress', function (e) {
            // Get the character code of the pressed key
            const charCode = (e.which) ? e.which : e.keyCode;
            // If the character is not a digit (0-9), prevent the input
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                e.preventDefault();
            }
        });

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('password-strength');
        const strengthText = document.getElementById('password-strength-text');

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