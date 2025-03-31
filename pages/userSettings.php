<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include_once('../database/db_connect.php');

// Set page title
$pageTitle = "User Settings";

// Initialize variables
$message = '';
$toastClass = '';

// Get current user data
$userId = $_SESSION['user_id'];
$sql = "SELECT username, email, phoneNumber, dateOfBirth FROM users WHERE userId = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Get current notification settings
$sql = "SELECT newCatalogNotify, reminderNotify, updateNotify FROM user_settings WHERE userId = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $settingsData = $result->fetch_assoc();
} else {
    // Create default settings if not exist
    $sql = "INSERT INTO user_settings (userId, newCatalogNotify, reminderNotify, updateNotify) VALUES (?, 1, 1, 1)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $settingsData = [
        'newCatalogNotify' => 1,
        'reminderNotify' => 1,
        'updateNotify' => 1
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
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        
        if (!empty($phoneNumber) && !preg_match("/^[0-9]{10}$/", $phoneNumber)) {
            $errors[] = "Phone number must be 10 digits";
        }
        
        if (empty($errors)) {
            // Update user data
            $sql = "UPDATE users SET username = ?, phoneNumber = ?, dateOfBirth = ? WHERE userId = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssi", $username, $phoneNumber, $dateOfBirth, $userId);
            
            if ($stmt->execute()) {
                $message = "Profile updated successfully";
                $toastClass = "success";
                
                // Update session data
                $_SESSION['username'] = $username;
                
                // Refresh user data
                $userData['username'] = $username;
                $userData['phoneNumber'] = $phoneNumber;
                $userData['dateOfBirth'] = $dateOfBirth;
            } else {
                $message = "Error updating profile: " . $mysqli->error;
                $toastClass = "danger";
            }
        } else {
            $message = implode("<br>", $errors);
            $toastClass = "danger";
        }
    } elseif (isset($_POST['change_password'])) {
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
                    $message = "Password changed successfully";
                    $toastClass = "success";
                } else {
                    $message = "Error changing password: " . $mysqli->error;
                    $toastClass = "danger";
                }
            } else {
                $message = "Current password is incorrect";
                $toastClass = "danger";
            }
        } else {
            $message = implode("<br>", $errors);
            $toastClass = "danger";
        }
    } elseif (isset($_POST['update_notifications'])) {
        // Update notification settings
        $newCatalogNotify = isset($_POST['newCatalogNotify']) ? 1 : 0;
        $reminderNotify = isset($_POST['reminderNotify']) ? 1 : 0;
        $updateNotify = isset($_POST['updateNotify']) ? 1 : 0;
        
        $sql = "UPDATE user_settings SET newCatalogNotify = ?, reminderNotify = ?, updateNotify = ? WHERE userId = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiis", $newCatalogNotify, $reminderNotify, $updateNotify, $userId);
        
        if ($stmt->execute()) {
            $message = "Notification settings updated successfully";
            $toastClass = "success";
            
            // Update settings data
            $settingsData['newCatalogNotify'] = $newCatalogNotify;
            $settingsData['reminderNotify'] = $reminderNotify;
            $settingsData['updateNotify'] = $updateNotify;
        } else {
            $message = "Error updating notification settings: " . $mysqli->error;
            $toastClass = "danger";
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom site CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fc;
        }
        
        .content-wrapper {
            padding-top: 30px;
            padding-bottom: 50px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-title {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
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
        }
        
        .form-switch .form-check-input:focus {
            border-color: rgba(78, 115, 223, 0.25);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
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
    // Include sidebar
    include_once('../components/sidebar/sidebar.php');
    ?>
    
    <div class="container content-wrapper">
        <?php if ($message): ?>
            <div class="toast-container">
                <div class="toast align-items-center text-white bg-<?php echo $toastClass; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <?php echo $message; ?>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            <p class="text-muted">Manage your profile information, password, and notification preferences.</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Personal Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-circle"></i> Personal Information
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control disabled-input" id="email" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                                <div class="form-text text-muted">Email cannot be changed. Please contact support if you need to update your email.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phoneNumber" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($userData['phoneNumber'] ?? ''); ?>" placeholder="10-digit phone number">
                            </div>
                            
                            <div class="mb-3">
                                <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($userData['dateOfBirth'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
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
                                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                    <span class="input-group-text">
                                        <i class="fas fa-eye-slash toggle-password" data-target="currentPassword"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                    <span class="input-group-text">
                                        <i class="fas fa-eye-slash toggle-password" data-target="newPassword"></i>
                                    </span>
                                </div>
                                <div class="form-text text-muted">Must be at least 8 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                    <span class="input-group-text">
                                        <i class="fas fa-eye-slash toggle-password" data-target="confirmPassword"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-bell"></i> Notification Settings
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="newCatalogNotify" name="newCatalogNotify" <?php echo $settingsData['newCatalogNotify'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="newCatalogNotify">
                                    <strong>New Catalog Notifications</strong>
                                    <p class="text-muted mb-0">Receive notifications when new catalogs are published</p>
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="reminderNotify" name="reminderNotify" <?php echo $settingsData['reminderNotify'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="reminderNotify">
                                    <strong>Catalog Reminders</strong>
                                    <p class="text-muted mb-0">Get reminders about upcoming catalog deadlines</p>
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="updateNotify" name="updateNotify" <?php echo $settingsData['updateNotify'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="updateNotify">
                                    <strong>System Updates</strong>
                                    <p class="text-muted mb-0">Receive notifications about system updates and new features</p>
                                </label>
                            </div>
                            
                            <button type="submit" name="update_notifications" class="btn btn-primary">Save Notification Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Account Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-id-card"></i> Account Summary
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-4">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
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
                        
                        <div class="mb-3">
                            <strong><i class="fas fa-shield-alt me-2 text-muted"></i> Account Status:</strong>
                            <p><span class="badge bg-success">Active</span></p>
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
                            <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-user-times me-2 text-danger"></i> Delete Account
                            </a>
                            <a href="logout.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-sign-out-alt me-2"></i> Log Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
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
                    
                    <form id="deleteAccountForm" method="post" action="delete_account.php">
                        <div class="mb-3">
                            <label for="deleteConfirm" class="form-label">Type "DELETE" to confirm</label>
                            <input type="text" class="form-control" id="deleteConfirm" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>Delete Account</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toast functionality
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function(toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 5000 });
        });
        toastList.forEach(toast => toast.show());
        
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
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
        
        deleteConfirmInput.addEventListener('input', function() {
            if (this.value === 'DELETE') {
                confirmDeleteBtn.disabled = false;
            } else {
                confirmDeleteBtn.disabled = true;
            }
        });
        
        confirmDeleteBtn.addEventListener('click', function() {
            if (deleteConfirmInput.value === 'DELETE') {
                deleteAccountForm.submit();
            }
        });
    </script>
</body>
</html>