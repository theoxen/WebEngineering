<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and their role
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);

$isLocalhost = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
$baseUrl = $isLocalhost ? "/WebEngineering/pages/" : "/pages/";
?>

<!-- Mobile Toggle Button -->
<button type="button" id="sidebarCollapseBtn" class="sidebar-toggle-btn">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar-wrapper">
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo $baseUrl ?>homepage.php" class="d-flex align-items-center text-decoration-none">
                <span class="fs-5 fw-bold">WebEngineering</span>
            </a>
        </div>

        <div class="sidebar-body">
            <ul class="nav flex-column">
                <!-- Available to all users -->
                <li class="nav-item">
                    <a href="<?php echo $baseUrl ?>homepage.php"
                        class="nav-link <?php echo $currentPage == 'homepage.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                </li>

                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user options -->

                    <li class="nav-item">
                        <a href="<?php echo $baseUrl ?>user-settings.php"
                            class="nav-link <?php echo $currentPage == 'user-settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?php echo $baseUrl ?>user-api-keys.php"
                            class="nav-link <?php echo $currentPage == 'user-api-keys.php' ? 'active' : ''; ?>">
                            <i class="fas fa-key"></i> My API Keys
                        </a>
                    </li>
                    <?php if($isAdmin): ?>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl ?>admin/upload-file.php"
                            class="nav-link <?php echo $currentPage, 'upload-file.php' ? 'active' : ''; ?>">
                            <i class="fas fa-upload"></i> Upload File
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- /////////////////////////////////////////////////////////////////////////////-->
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#reportsSubmenu">
                            <i class="fas fa-chart-line"></i>
                            <span>Reports</span>
                            <i class="fas fa-angle-down ms-auto"></i>
                        </a>
                        <div class="collapse" id="reportsSubmenu">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a href="/WebEngineering/pages/Reports/select-report.php" class="nav-link">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Select Report</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/WebEngineering/pages/Reports/general-statistics.php" class="nav-link">
                                        <i class="fas fa-chart-bar"></i>
                                        <span>General Statistics</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/WebEngineering/pages/Reports/user-statistics.php" class="nav-link">
                                        <i class="fas fa-users"></i>
                                        <span>User Statistics</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- /////////////////////////////////////////////////////////////////////////// -->
                    <?php if ($isAdmin): ?>
                        <!-- Admin only options -->
                        <li class="nav-heading mt-3 mb-1 text-uppercase ps-3 small fw-bold text-muted"
                            style="color: white !important;">Admin</li>
                        <li class="nav-item">
                            <a href="<?php echo $baseUrl ?>admin/dashboard.php"
                                class="nav-link <?php echo strpos($currentPage, 'admin/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $baseUrl ?>admin/api.php"
                                class="nav-link <?php echo strpos($currentPage, 'admin/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-key"></i> API Keys
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item mt-3">
                        <a href="<?php echo $baseUrl ?>logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Not logged in options -->
                    <li class="nav-item mt-3">
                        <a href="<?php echo $baseUrl ?>login.php"
                            class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl ?>register.php"
                            class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if ($isLoggedIn): ?>
            <div class="sidebar-footer">
                <div class="user-info d-flex align-items-center">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle fa-2x"></i>
                    </div>
                    <div class="user-details ms-2">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['email']); ?>
                        </div>
                        <div class="user-role small text-muted" style="color: white !important; font-weight: bold;">
                            <?php echo $isAdmin ? 'Administrator' : 'User'; ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </nav>
    <style>
        .sidebar-toggle-btn {
            background: transparent;
            border: none;
            color: black;
            /* Change from #333 to black for better visibility */
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1050;
            padding: 0.5rem;
            position: fixed;
            left: 10px;
            top: 10px;
            transition: all 0.3s ease;
        }

        /* Keep active state white when sidebar is open */
        .sidebar-toggle-btn.active-toggle {
            color: white;
            left: 15px;
            top: 15px;
        }

        .sidebar-active .sidebar-header {
            padding-left: 60px;
        }

        .sidebar-header a {
            margin-left: 5px;
            /* Add some extra margin to the anchor itself */
        }

        @media (max-width: 767.98px) {
            .sidebar-active .sidebar-toggle-btn {
                color: white;
            }
        }
    </style>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
        const sidebar = document.querySelector('.sidebar-wrapper');

        // Toggle function for sidebar with icon change
        function toggleSidebar() {
            if (sidebar) {
                sidebar.classList.toggle('active');
                document.body.classList.toggle('sidebar-active');
                sidebarCollapseBtn.classList.toggle('active-toggle'); // Add this line

                // Change the icon based on sidebar state
                if (sidebar.classList.contains('active')) {
                    // If sidebar is now open
                    setTimeout(() => {
                        sidebarCollapseBtn.innerHTML = '<i class="fas fa-times"></i>'; // Change to X icon
                    }, 150); // Short delay for smooth animation
                } else {
                    // If sidebar is now closed
                    setTimeout(() => {
                        sidebarCollapseBtn.innerHTML = '<i class="fas fa-bars"></i>'; // Change back to bars
                    }, 150);
                }
            }
        }

        // Handle responsiveness
        function handleResponsiveness() {
            if (window.innerWidth < 768) {
                // Mobile view - hide sidebar by default
                if (sidebar) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-active');
                    sidebarCollapseBtn.style.display = 'flex'; // Show toggle button
                    sidebarCollapseBtn.innerHTML = '<i class="fas fa-bars"></i>'; // Reset to bars icon
                }
            } else {
                // Desktop view - show sidebar by default
                if (sidebar) {
                    sidebar.classList.remove('active'); // Reset any active states
                    document.body.classList.remove('sidebar-active');
                    sidebarCollapseBtn.style.display = 'none'; // Hide toggle button
                }
            }
        }

        // Add click event listener to toggle button
        if (sidebarCollapseBtn) {
            sidebarCollapseBtn.addEventListener('click', function (e) {
                e.stopPropagation(); // Prevent event bubbling
                toggleSidebar();
            });
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            const isMobile = window.innerWidth < 768;
            if (isMobile && sidebar &&
                sidebar.classList.contains('active') &&
                !sidebar.contains(event.target) &&
                event.target !== sidebarCollapseBtn) {
                toggleSidebar();
            }
        });

        // Prevent click events within sidebar from propagating
        if (sidebar) {
            sidebar.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        // Initial check for responsiveness
        handleResponsiveness();

        // Listen for window resize events
        window.addEventListener('resize', handleResponsiveness);
    });
</script>