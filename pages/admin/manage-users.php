<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include_once('../../database/db_connect.php');

$message = "";
$messageClass = "";

// Handle user actions (delete, promote, demote)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        $action = $_POST['action'];

        // Validate that we're not acting on ourselves
        if ($userId == $_SESSION['user_id'] && ($action == 'delete' || $action == 'demote')) {
            $message = "You cannot $action yourself!";
            $messageClass = "danger";
        } else {
            switch ($action) {
                case 'delete':
                    $stmt = $mysqli->prepare("DELETE FROM users WHERE userId = ?");
                    $stmt->bind_param("i", $userId);
                    if ($stmt->execute()) {
                        $message = "User deleted successfully";
                        $messageClass = "success";
                    } else {
                        $message = "Error deleting user: " . $mysqli->error;
                        $messageClass = "danger";
                    }
                    $stmt->close();
                    break;

                case 'promote':
                    $stmt = $mysqli->prepare("UPDATE users SET role = 'admin' WHERE userId = ?");
                    $stmt->bind_param("i", $userId);
                    if ($stmt->execute()) {
                        $message = "User promoted to admin successfully";
                        $messageClass = "success";
                    } else {
                        $message = "Error promoting user: " . $mysqli->error;
                        $messageClass = "danger";
                    }
                    $stmt->close();
                    break;

                case 'demote':
                    $stmt = $mysqli->prepare("UPDATE users SET role = 'user' WHERE userId = ?");
                    $stmt->bind_param("i", $userId);
                    if ($stmt->execute()) {
                        $message = "Admin demoted to regular user successfully";
                        $messageClass = "success";
                    } else {
                        $message = "Error demoting admin: " . $mysqli->error;
                        $messageClass = "danger";
                    }
                    $stmt->close();
                    break;
            }
        }
    }
}

// Set up pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Build WHERE clause for filtering
$whereClause = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $whereClause .= " AND (username LIKE ? )";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $types .= "s";
}

if (!empty($role)) {
    $whereClause .= " AND role = ?";
    $params[] = $role;
    $types .= "s";
}

// Get total users count for pagination
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
$stmt = $mysqli->prepare($countQuery);

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$totalUsers = $result->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $perPage);
$stmt->close();

// Get users with pagination and filtering
$query = "SELECT userId, username, email, phoneNumber, dateOfBirth, role, dateCreated FROM users 
          $whereClause ORDER BY userId DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);

// Add pagination parameters
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">

    <!-- Custom styles -->
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .content-wrapper {
            padding: 50px;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #5a5c69;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 700;
            padding: 1rem 1.25rem;
        }

        .table th {
            font-weight: 600;
            background-color: #f8f9fc;
            border-top: none;
        }

        .table td {
            vertical-align: middle;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4e73df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
            margin-top: 5px;
        }

        .pagination {
            margin-bottom: 0;
        }

        .search-bar {
            max-width: 400px;
        }

        .modal-header,
        .modal-footer {
            border-color: #e3e6f0;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .modal-icon.warning {
            color: #f6c23e;
        }

        .modal-icon.danger {
            color: #e74a3b;
        }

        .modal-icon.success {
            color: #1cc88a;
        }

        @media screen and (max-width: 370px) {
            .header-content {
                flex-direction: column;
            }

            .content-wrapper {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include_once('../../components/sidebar/sidebar.php'); ?>
    <div class="main-content">
        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4 header-content">
                    <h1 class="page-title">Manage Users</h1>
                    <div>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- User Management Card -->
                <div class="card shadow">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i> User Management
                    </div>
                    <div class="card-body">
                        <!-- Search and Filter -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="get" class="search-bar">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search users..."
                                            name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <select class="form-select" name="role" style="max-width: 120px;">
                                            <option value="">All Roles</option>
                                            <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Users
                                            </option>
                                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>
                                                Admins</option>
                                        </select>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search) || !empty($role)): ?>
                                            <a href="manage-users.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted">Total: <?php echo $totalUsers; ?> users</span>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Registered Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No users found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['userId']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($user['username']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phoneNumber']); ?></td>
                                                <td>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="badge bg-danger">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary">User</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($user['dateCreated'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-user"
                                                            data-id="<?php echo $user['userId']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                            data-phone="<?php echo htmlspecialchars($user['phoneNumber']); ?>"
                                                            data-dob="<?php echo htmlspecialchars($user['dateOfBirth']); ?>"
                                                            data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                            data-created="<?php echo date('M d, Y', strtotime($user['dateCreated'])); ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <?php if ($user['role'] === 'user'): ?>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-success promote-user ms-1"
                                                                data-id="<?php echo $user['userId']; ?>"
                                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                                <i class="fas fa-user-shield"></i>
                                                            </button>
                                                        <?php elseif ($user['userId'] != $_SESSION['user_id']): ?>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-warning demote-user ms-1"
                                                                data-id="<?php echo $user['userId']; ?>"
                                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                                <i class="fas fa-user"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if ($user['userId'] != $_SESSION['user_id']): ?>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger delete-user ms-1"
                                                                data-id="<?php echo $user['userId']; ?>"
                                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Hidden forms for actions -->
                                                    <form id="promote-form-<?php echo $user['userId']; ?>" method="post"
                                                        class="d-none">
                                                        <input type="hidden" name="user_id"
                                                            value="<?php echo $user['userId']; ?>">
                                                        <input type="hidden" name="action" value="promote">
                                                    </form>

                                                    <form id="demote-form-<?php echo $user['userId']; ?>" method="post"
                                                        class="d-none">
                                                        <input type="hidden" name="user_id"
                                                            value="<?php echo $user['userId']; ?>">
                                                        <input type="hidden" name="action" value="demote">
                                                    </form>

                                                    <form id="delete-form-<?php echo $user['userId']; ?>" method="post"
                                                        class="d-none">
                                                        <input type="hidden" name="user_id"
                                                            value="<?php echo $user['userId']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="User list pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Details Modal -->
        <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 2rem;"
                                id="modalUserAvatar">U</div>
                            <h4 class="mt-2 mb-0" id="modalUsername">Username</h4>
                            <div class="mb-3">
                                <span class="badge" id="modalUserRole">User</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-envelope me-2"></i>Email</label>
                            <p id="modalUserEmail" class="mb-0">email@example.com</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-phone me-2"></i>Phone</label>
                            <p id="modalUserPhone" class="mb-0">1234567890</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-calendar me-2"></i>Date of Birth</label>
                            <p id="modalUserDob" class="mb-0">January 1, 1990</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fas fa-clock me-2"></i>Registration Date</label>
                            <p id="modalUserCreated" class="mb-0">January 1, 2023</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Promote User Modal -->
        <div class="modal fade" id="promoteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Promote to Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-user-shield modal-icon success"></i>
                        <h4>Promote User to Admin?</h4>
                        <p>Are you sure you want to promote <strong id="promoteUsername"></strong> to admin status? This
                            will give them full administrative rights to the system.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmPromote">Promote to Admin</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demote Admin Modal -->
        <div class="modal fade" id="demoteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Demote Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-user modal-icon warning"></i>
                        <h4>Demote Admin to Regular User?</h4>
                        <p>Are you sure you want to demote <strong id="demoteUsername"></strong> to regular user status?
                            They will lose all administrative privileges.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" id="confirmDemote">Demote to User</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete User Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-exclamation-triangle modal-icon danger"></i>
                        <h4>Delete User Permanently?</h4>
                        <p>Are you sure you want to delete <strong id="deleteUsername"></strong>? This action cannot be
                            undone and all user data will be permanently removed.</p>

                        <div class="form-check mt-3 text-start">
                            <input class="form-check-input" type="checkbox" id="deleteConfirm">
                            <label class="form-check-label" for="deleteConfirm">
                                I understand this action is irreversible.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Delete User</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Variables to store current user info for modals
        let currentUserId = null;

        // Handle View User button click
        document.querySelectorAll('.view-user').forEach(button => {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');
                const email = this.getAttribute('data-email');
                const phone = this.getAttribute('data-phone');
                const dob = this.getAttribute('data-dob');
                const role = this.getAttribute('data-role');
                const created = this.getAttribute('data-created');

                // Update modal content
                document.getElementById('modalUserAvatar').innerText = username.charAt(0).toUpperCase();
                document.getElementById('modalUsername').innerText = username;
                document.getElementById('modalUserEmail').innerText = email;
                document.getElementById('modalUserPhone').innerText = phone;
                document.getElementById('modalUserDob').innerText = dob;
                document.getElementById('modalUserCreated').innerText = created;

                // Update role badge
                const roleBadge = document.getElementById('modalUserRole');
                roleBadge.innerText = role.charAt(0).toUpperCase() + role.slice(1);
                roleBadge.className = role === 'admin' ? 'badge bg-danger' : 'badge bg-primary';

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
                modal.show();
            });
        });

        // Handle Promote User button
        document.querySelectorAll('.promote-user').forEach(button => {
            button.addEventListener('click', function () {
                currentUserId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');

                // Update modal content
                document.getElementById('promoteUsername').innerText = username;

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('promoteModal'));
                modal.show();
            });
        });

        // Handle Demote User button
        document.querySelectorAll('.demote-user').forEach(button => {
            button.addEventListener('click', function () {
                currentUserId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');

                // Update modal content
                document.getElementById('demoteUsername').innerText = username;

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('demoteModal'));
                modal.show();
            });
        });

        // Handle Delete User button
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', function () {
                currentUserId = this.getAttribute('data-id');
                const username = this.getAttribute('data-username');

                // Update modal content
                document.getElementById('deleteUsername').innerText = username;

                // Reset the confirmation checkbox
                document.getElementById('deleteConfirm').checked = false;
                document.getElementById('confirmDelete').disabled = true;

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            });
        });

        // Handle Delete Confirmation Checkbox
        document.getElementById('deleteConfirm').addEventListener('change', function () {
            document.getElementById('confirmDelete').disabled = !this.checked;
        });

        // Handle Confirm Promote
        document.getElementById('confirmPromote').addEventListener('click', function () {
            if (currentUserId) {
                document.getElementById('promote-form-' + currentUserId).submit();
            }
        });

        // Handle Confirm Demote
        document.getElementById('confirmDemote').addEventListener('click', function () {
            if (currentUserId) {
                document.getElementById('demote-form-' + currentUserId).submit();
            }
        });

        // Handle Confirm Delete
        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (currentUserId && document.getElementById('deleteConfirm').checked) {
                document.getElementById('delete-form-' + currentUserId).submit();
            }
        });
    </script>
</body>

</html>