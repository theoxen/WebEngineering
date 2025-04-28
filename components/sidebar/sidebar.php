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


<!-- Mobile Toggle Button OUTSIDE sidebar-wrapper -->
<button type="button" id="sidebarCollapseBtn" class="sidebar-toggle-btn">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar-wrapper">
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo $baseUrl?>homepage.php" class="d-flex align-items-center text-decoration-none">
                <span class="fs-5 fw-bold">WebEngineering</span>
            </a>
        </div>
        
        <div class="sidebar-body">
            <ul class="nav flex-column">
                <!-- Available to all users -->
                <li class="nav-item">
                    <a href="<?php echo $baseUrl?>homepage.php" class="nav-link <?php echo $currentPage == 'homepage.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                </li>
                
                
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user options -->
                     <!-- TODO USER PROFILE THAT WILL DISPLAY THE PROFILE + THE CANDIDATES THAT ARE BEING TRACKED (?) -->
                    <li class="nav-item"> 
                        <a href="<?php echo $baseUrl?>myprofile.php" class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?php echo $baseUrl?>user-settings.php" class="nav-link <?php echo $currentPage == 'user-settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $pageTitle === 'API Keys' ? 'active' : '' ?>" href="api.php">
                            <i class="fas fa-key"></i> API Keys
                        </a>
                    </li>

                    
                    <?php if ($isAdmin): ?>
                        <!-- Admin only options -->
                        <li class="nav-heading mt-3 mb-1 text-uppercase ps-3 small fw-bold text-muted">Admin</li>
                        <li class="nav-item">
                            <a href="<?php echo $baseUrl?>admin/dashboard.php" class="nav-link <?php echo strpos($currentPage, 'admin/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item mt-3">
                        <a href="<?php echo $baseUrl?>logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Not logged in options -->
                    <li class="nav-item mt-3">
                        <a href="<?php echo $baseUrl?>login.php" class="nav-link <?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $baseUrl?>register.php" class="nav-link <?php echo $currentPage == 'register.php' ? 'active' : ''; ?>">
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
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['email']); ?></div>
                    <div class="user-role small text-muted"><?php echo $isAdmin ? 'Administrator' : 'User'; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </nav>
</div>

<script>
// This is an IIFE (Immediately Invoked Function Expression) that runs as soon as it's defined
(function() {
    
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const sidebar = document.querySelector('.sidebar-wrapper');
    
    // Toggle function
    function toggleSidebar() {
        if (sidebar) {
            sidebar.classList.toggle('active');
            
            // Toggle body class for overlay
            document.body.classList.toggle('sidebar-active');
            
            // Prevent body scrolling when sidebar is active
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
            
            console.log('Sidebar toggled, active:', sidebar.classList.contains('active'));
        }
    }
    
    // Checking window width and update UI accordingly
    function handleResponsiveness() {
        if (window.innerWidth < 768) {
            // Mobile view
            sidebarCollapseBtn.style.display = 'block';
        } else {
            // Desktop view
            sidebarCollapseBtn.style.display = 'none';
            // Make sure sidebar is visible on desktop
            if (sidebar) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-active');
                document.body.style.overflow = '';
            }
        }
    }
    
    // Adding click event listener to button
    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event from bubbling
            toggleSidebar();
        });
    }
    
    // Adding click event listener to close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        if (sidebar && 
            sidebar.classList.contains('active') &&
            !sidebar.contains(event.target) &&
            event.target !== sidebarCollapseBtn) {
            toggleSidebar();
        }
    });
    
    // Make sure sidebar content clicks don't close the sidebar
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Initial check
    handleResponsiveness();
    
    // Listen for window resize
    window.addEventListener('resize', handleResponsiveness);
})();

function toggleSidebar() {
    if (sidebar) {
        console.log('Toggle function called');
        sidebar.classList.toggle('active');
        console.log('Sidebar active class:', sidebar.classList.contains('active'));
        
        // Toggle body class for overlay
        document.body.classList.toggle('sidebar-active');
        
        // Prevent body scrolling when sidebar is active
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    } else {
        console.error('Sidebar element not found');
    }
}
</script>