<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once('../database/db_connect.php');

$pageTitle = "User Settings";

$message = '';
$toastClass = '';

// Check if we have flash messages from the previous request
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $toastClass = $_SESSION['flash_class'];

    // Clear flash messages so they don't show up again on refresh
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_class']);
}

// Get current user data
$userId = $_SESSION['user_id'];
$sql = "SELECT username, email, phoneNumber, dateOfBirth FROM users WHERE userId = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Get current notification settings 
$sql = "SELECT newCatalogNotify, catalogUpdateNotify, positionChangeNotify FROM user_notification_settings WHERE userId = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $settingsData = $result->fetch_assoc();
} else {
    // Create default settings if not exist (they should always exist)
    $sql = "INSERT INTO user_notification_settings (userId, newCatalogNotify, catalogUpdateNotify, positionChangeNotify) VALUES (?, 0, 0, 0)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $settingsData = [
        'newCatalogNotify' => 0,
        'catalogUpdateNotify' => 0,
        'positionChangeNotify' => 0
    ];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $username = trim($_POST['username']);
        $phoneNumber = trim($_POST['phoneNumber']);
        $dateOfBirth = $_POST['dateOfBirth'];

        // Validate input
        $errors = [];

        if (empty($username)) {
            $errors[] = "Username is required";
        }

        if (empty($phoneNumber)) {
            $errors[] = "Phone should not be empty";
        }

        if (empty($errors)) {
            // Update user data
            $sql = "UPDATE users SET username = ?, phoneNumber = ?, dateOfBirth = ? WHERE userId = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssi", $username, $phoneNumber, $dateOfBirth, $userId);

            if ($stmt->execute()) {
                // Set flash message in session
                $_SESSION['flash_message'] = "Profile updated successfully";
                $_SESSION['flash_class'] = "success";

                // Update session data
                $_SESSION['username'] = $username;

                // Refresh user data
                $userData['username'] = $username;
                $userData['phoneNumber'] = $phoneNumber;
                $userData['dateOfBirth'] = $dateOfBirth;
            } else {
                // Set flash message in session
                $_SESSION['flash_message'] = "Error updating profile: " . $mysqli->error;
                $_SESSION['flash_class'] = "danger";
            }
        } else {
            // Set flash message in session
            $_SESSION['flash_message'] = implode("<br>", $errors);
            $_SESSION['flash_class'] = "danger";
        }
    } elseif (isset($_POST['change_password'])) { // TODO CHANGE PASSWORD TO MATCH THE OPTIONS IN THE REGISTER PAGE
        // Change password
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate input
        $errors = [];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = "All password fields are required";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "New password must be at least 8 characters";
        }

        if (empty($errors)) {
            // Verify current password
            $sql = "SELECT password FROM users WHERE userId = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (password_verify($currentPassword, $user['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE userId = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("si", $hashedPassword, $userId);

                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = "Password changed successfully";
                    $_SESSION['flash_class'] = "success";
                } else {
                    $_SESSION['flash_message'] = "Error changing password: " . $mysqli->error;
                    $_SESSION['flash_class'] = "danger";
                }
            } else {
                $_SESSION['flash_message'] = "Current password is incorrect";
                $_SESSION['flash_class'] = "danger";
            }
        } else {
            $_SESSION['flash_message'] = implode("<br>", $errors);
            $_SESSION['flash_class'] = "danger";
        }
    } elseif (isset($_POST['update_notifications'])) {
        // Update notification settings
        $newCatalogNotify = isset($_POST['newCatalogNotify']) ? 1 : 0;
        $positionChangeNotify = isset($_POST['positionChangeNotify']) ? 1 : 0;
        $catalogUpdateNotify = isset($_POST['catalogUpdateNotify']) ? 1 : 0;

        $sql = "UPDATE user_notification_settings SET newCatalogNotify = ?, positionChangeNotify = ?, catalogUpdateNotify = ? WHERE userId = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiis", $newCatalogNotify, $positionChangeNotify, $catalogUpdateNotify, $userId);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Notification settings updated successfully";
            $_SESSION['flash_class'] = "success";

            // Update settings data
            $settingsData['newCatalogNotify'] = $newCatalogNotify;
            $settingsData['positionChangeNotify'] = $positionChangeNotify;
            $settingsData['catalogUpdateNotify'] = $catalogUpdateNotify;
        } else {
            $_SESSION['flash_message'] = "Error updating notification settings: " . $mysqli->error;
            $_SESSION['flash_class'] = "danger";
        }
    }

    // To prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }

        .content-wrapper {
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 1200px;
            width: 100%;
        }

        .container-fluid {
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin-right: 0;
            margin-left: 0;
        }

        .row {
            width: 90% !important;
        }


        .page-header {
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            text-align: center;
            color: #2c3e50;
            margin-bottom: 0.75rem;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: #4e73df;
            display: flex;
            align-items: center;
        }

        .card-header i {
            margin-right: 0.75rem;
            opacity: 0.8;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #5a5c69;
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d3e2;
        }

        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        .form-check-input:checked {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-top: 0.125em;
            cursor: pointer;
        }

        .form-switch .form-check-input:focus {
            border-color: rgba(78, 115, 223, 0.25);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1080;
        }

        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            background-color: #e8eeff;
            color: #4e73df;
            margin-bottom: 1rem;
        }

        .feature-icon i {
            font-size: 1.75rem;
        }

        .feature-card {
            border: none;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            height: 100%;
            padding: 1.5rem;
        }

        .disabled-input {
            background-color: #f8f9fc;
            cursor: not-allowed;
        }

        .page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-check-label {
            margin-left: 5px;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }
        }
    </style>
</head>

<body>
    <?php
    include_once('../components/sidebar/sidebar.php');
    ?>
    <div class="main-content" style="padding-left: 0; padding-right: 0;">
        <div class="page">
            <!-- Toast notifications with fixed positioning -->
            <?php if ($message): ?>
                <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080; margin-top: 15px;">
                    <div class="toast align-items-center text-white bg-<?php echo $toastClass; ?> border-0 show"
                        role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <?php echo $message; ?>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                                aria-label="Close"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container-fluid content-wrapper" style="padding-left: 0; padding-right: 0;">
                <div class="page-header" style="justify-content: center; justify-self: center;">
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="text-muted" style="text-align: center;">Manage your profile information, password, and notification preferences.</p>
                </div>

                <div class="row" style="justify-self: center;">
                    <div class="col-lg-3">
                        <!-- Account Summary -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-id-card"></i> Account Summary
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-center mb-4">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px; font-size: 2rem;">
                                        <?php echo strtoupper(substr($userData['username'], 0, 1)); ?>
                                    </div>
                                </div>

                                <h5 class="text-center mb-3"><?php echo htmlspecialchars($userData['username']); ?></h5>

                                <div class="mb-3">
                                    <strong><i class="fas fa-envelope me-2 text-muted"></i> Email:</strong>
                                    <p class="text-muted"><?php echo htmlspecialchars($userData['email']); ?></p>
                                </div>

                                <div class="mb-3">
                                    <strong><i class="fas fa-clock me-2 text-muted"></i> Account Created:</strong>
                                    <p class="text-muted"><?php echo date('F j, Y'); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="homepage.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-home me-2"></i> Go to Homepage
                                    </a>
                                    <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal"
                                        data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-user-times me-2 text-danger"></i> Delete Account
                                    </a>
                                    <a href="logout.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-sign-out-alt me-2"></i> Log Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <!-- Personal Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-user-circle"></i> Personal Information
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                            value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control disabled-input" id="email"
                                            value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                        <div class="form-text text-muted">Email cannot be changed. Please contact
                                            support if
                                            you need to update your email.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                            value="<?php echo htmlspecialchars($userData['phoneNumber'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth"
                                            value="<?php echo htmlspecialchars($userData['dateOfBirth'] ?? ''); ?>">
                                    </div>

                                    <button type="submit" name="update_profile" class="btn btn-primary">Save
                                        Changes</button>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-lock"></i> Change Password
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="mb-3">
                                        <label for="currentPassword" class="form-label">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="currentPassword"
                                                name="currentPassword" required>
                                            <span class="input-group-text">
                                                <i class="fas fa-eye-slash toggle-password"
                                                    data-target="currentPassword"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="newPassword" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="newPassword"
                                                name="newPassword" minlength="8" required>
                                            <span class="input-group-text">
                                                <i class="fas fa-eye-slash toggle-password"
                                                    data-target="newPassword"></i>
                                            </span>
                                        </div>
                                        <div class="form-text text-muted">Must be at least 8 characters long.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirmPassword"
                                                name="confirmPassword" required>
                                            <span class="input-group-text">
                                                <i class="fas fa-eye-slash toggle-password"
                                                    data-target="confirmPassword"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <button type="submit" name="change_password" class="btn btn-primary">Change
                                        Password</button>
                                </form>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-bell"></i> Email Notification Settings
                            </div>
                            <div class="card-body">
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="newCatalogNotify"
                                            name="newCatalogNotify" <?php echo $settingsData['newCatalogNotify'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="newCatalogNotify">
                                            <strong>New Catalog</strong>
                                            <p class="text-muted mb-0">Receive notifications when new catalogs are
                                                published
                                            </p>
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="catalogUpdateNotify"
                                            name="catalogUpdateNotify" <?php echo $settingsData['catalogUpdateNotify'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="catalogUpdateNotify">
                                            <strong>Catalog Update</strong>
                                            <p class="text-muted mb-0">Get reminders about existing catalog catalogs
                                                that
                                                have been updated</p>
                                        </label>
                                    </div>

                                    <button type="submit" name="update_notifications" class="btn btn-primary">Save
                                        Notification Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Delete Account Modal -->
            <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                            <p>All your personal data and settings will be permanently removed.</p>

                            <form id="deleteAccountForm" method="post" action="delete-user.php">
                                <div class="mb-3">
                                    <label for="deleteConfirm" class="form-label">Type "DELETE" to confirm</label>
                                    <input type="text" class="form-control" id="deleteConfirm" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Delete
                                Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toast functionality
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 5000 });
        });
        toastList.forEach(toast => toast.show());

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);

                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                } else {
                    input.type = "password";
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                }
            });
        });

        // Handle account deletion confirmation
        const deleteConfirmInput = document.getElementById('deleteConfirm');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteAccountForm = document.getElementById('deleteAccountForm');

        deleteConfirmInput.addEventListener('input', function () {
            if (this.value === 'DELETE') {
                confirmDeleteBtn.disabled = false;
            } else {
                confirmDeleteBtn.disabled = true;
            }
        });

        confirmDeleteBtn.addEventListener('click', function () {
            if (deleteConfirmInput.value === 'DELETE') {
                deleteAccountForm.submit();
            }
        });
    </script>
</body>

</html>