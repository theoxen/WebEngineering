<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once('../../database/db_connect.php');

// Get admin info
$userId = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE userId = ? AND role = 'admin'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$adminData = $result->fetch_assoc();

// Get dashboard statistics
// Count total users
$userQuery = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$userResult = $mysqli->query($userQuery);
$userCount = $userResult->fetch_assoc()['total_users'];

// Count total admins
$adminQuery = "SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'";
$adminResult = $mysqli->query($adminQuery);
$adminCount = $adminResult->fetch_assoc()['total_admins'];

// Count users registered in last 30 days
$newUsersQuery = "SELECT COUNT(*) as new_users FROM users WHERE role = 'user' AND dateCreated >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$newUsersResult = $mysqli->query($newUsersQuery);
$newUsersCount = $newUsersResult->fetch_assoc()['new_users'];

// Get recent user registrations
$recentUsersQuery = "SELECT userId, username, email, dateCreated FROM users ORDER BY dateCreated DESC LIMIT 5";
$recentUsersResult = $mysqli->query($recentUsersQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">

    <!-- Custom CSS for admin dashboard -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
        }

        .admin-wrapper {
            display: flex;
        }

        .content-wrapper {
            flex: 1;
            padding: 50px;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 700;
            padding: 1rem 1.25rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        .stat-card {
            border-left: 0.25rem solid;
            position: relative;
            overflow: hidden;
        }

        .stat-card.primary {
            border-left-color: var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--secondary-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card-body {
            display: flex;
            flex-direction: column;
        }

        .stat-card-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }

        .stat-card-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .primary-text {
            color: var(--primary-color);
        }

        .success-text {
            color: var(--secondary-color);
        }

        .warning-text {
            color: var(--warning-color);
        }

        .danger-text {
            color: var(--danger-color);
        }

        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 160px;
            text-align: center;
            padding: 1.5rem;
        }

        .action-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .action-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .action-description {
            color: var(--dark-color);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <?php
    // Include sidebar
    include_once('../../components/sidebar/sidebar.php');
    ?>
    <div class="admin-wrapper">
        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">Admin Dashboard</h1>
                    <div>
                        <a href="../homepage.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> Back to Site
                        </a>
                        <a href="../logout.php" class="btn btn-outline-danger ms-2">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Content Row - Statistics Cards -->
                <div class="row">
                    <!-- Total Users Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary h-100">
                            <div class="card-body">
                                <div class="stat-card-body">
                                    <div class="stat-card-title primary-text">Total Users</div>
                                    <div class="stat-card-value"><?php echo $userCount; ?></div>
                                </div>
                                <i class="fas fa-users stat-card-icon primary-text"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Admins Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card danger h-100">
                            <div class="card-body">
                                <div class="stat-card-body">
                                    <div class="stat-card-title danger-text">Administrators</div>
                                    <div class="stat-card-value"><?php echo $adminCount; ?></div>
                                </div>
                                <i class="fas fa-user-shield stat-card-icon danger-text"></i>
                            </div>
                        </div>
                    </div>

                    <!-- New Users Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success h-100">
                            <div class="card-body">
                                <div class="stat-card-body">
                                    <div class="stat-card-title success-text">New Users (30 days)</div>
                                    <div class="stat-card-value"><?php echo $newUsersCount; ?></div>
                                </div>
                                <i class="fas fa-user-plus stat-card-icon success-text"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Date Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card warning h-100">
                            <div class="card-body">
                                <div class="stat-card-body">
                                    <div class="stat-card-title warning-text">Current Date</div>
                                    <div class="stat-card-value"><?php echo date('M d, Y'); ?></div>
                                </div>
                                <i class="fas fa-calendar-alt stat-card-icon warning-text"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row - Admin Actions -->
                <div class="row">
                    <div class="col-12 mb-4">
                        <h2 class="h4 mb-3">Administration Tools</h2>
                    </div>

                    <!-- Manage Users -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="action-card">
                                <div class="action-icon" style="background-color: var(--primary-color);">
                                    <i class="fas fa-users-cog"></i>
                                </div>
                                <h3 class="action-title">Manage Users</h3>
                                <p class="action-description">View, edit, and manage user accounts</p>
                                <a href="manage-users.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Go to User Management
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Manage Lists -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="action-card">
                                <div class="action-icon" style="background-color: var(--secondary-color);">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h3 class="action-title">Manage Lists</h3>
                                <p class="action-description">Configure candidate lists</p>
                                <a href="system-settings.php" class="btn btn-success">
                                    <i class="fas fa-arrow-right"></i> Go to List Management
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- View Reports -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="action-card">
                                <div class="action-icon" style="background-color: var(--info-color);">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h3 class="action-title">Analytics</h3>
                                <p class="action-description">View site statistics and reports</p>
                                <a href="analytics.php" class="btn btn-info text-white">
                                    <i class="fas fa-arrow-right"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- EXTRA CARD -->
                    <!-- <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="action-card">
                                <div class="action-icon" style="background-color: var(--warning-color);">
                                    <i class="fas fa-tools"></i> ICON HERE
                                </div>
                                <h3 class="action-title">TITLE</h3>
                                <p class="action-description">DESCRIPTION</p>
                                <a href="maintenance.php" class="btn btn-warning">
                                    <i class="fas fa-arrow-right"></i> LINK NAME
                                </a>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- Content Row - Charts and Tables -->
                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-clock mr-2"></i> Recent User Registrations
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Registered</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = $recentUsersResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($user['dateCreated'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="manage-users.php" class="btn btn-sm btn-primary">View All Users</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                </div>


            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>