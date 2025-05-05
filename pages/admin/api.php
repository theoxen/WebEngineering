<?php
/**
 * Admin API Key Management
 * Allows administrators to create and manage API keys for users with specific permissions
 */

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

$pageTitle = "Admin API Keys Management";
// Include sidebar
include_once('../../components/sidebar/sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin API Keys Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../../components/sidebar/sidebar.css">
    <style>
        .main-content{
            margin-left: 250px;
            width: 80%;
            transition: all 0.3s;
            padding: 1rem;
            min-height: 100vh;
        }
        .col-lg-6{
            width: 100%;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.125);
            margin-bottom: 1.5rem;
        }
        .card-header {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            padding: 1rem;
        }
        .api-key-container {
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 0.75rem;
        }
        .btn-copy {
            transition: all 0.2s;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .key-expires-soon {
            font-weight: bold;
        }
        .api-key-display {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875rem;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .badge-expiry {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .user-search-result {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .user-search-result:hover {
            background-color: #f8f9fa;
        }
        .permission-toggle {
            min-width: 130px;
        }
        .method-badge {
            min-width: 70px;
            display: inline-block;
            text-align: center;
        }
        #refreshIndicator {
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            padding: 8px 15px; 
            background-color: rgba(0, 0, 0, 0.7); 
            color: white; 
            border-radius: 4px; 
            font-size: 14px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
            z-index: 9999; 
            display: none;
        }
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-left: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php
    // Include sidebar
    include_once('../../components/sidebar/sidebar.php');
    ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0"><i class="fas fa-key text-primary me-2"></i>Admin API Keys Management</h1>
                    </div>
                    
                    <p class="lead text-muted mb-4">Create and manage API keys for users with specific permissions.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle fa-lg me-3"></i>
                        <div>
                            <strong>Admin Notice:</strong> You can create API keys for any user and control what permissions each key has.
                        </div>
                    </div>
                    
                    <!-- Display when a new key is created -->
                    <div id="newKeyAlert" class="alert alert-success d-none fade-in" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-key fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5>New API Key Created</h5>
                                <p>Copy this API key now. For security reasons, it won't be shown again.</p>
                                <div class="api-key-container mb-2">
                                    <div class="input-group">
                                        <input type="text" id="newApiKey" class="form-control api-key-display" readonly>
                                        <button class="btn btn-outline-primary btn-copy" type="button" onclick="copyApiKey()">
                                            <i class="fas fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Created for user: <span id="newKeyUser" class="fw-bold"></span></small>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <small class="text-muted">Expires on: <span id="newKeyExpiry" class="fw-bold"></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-lg-6 col-md-8 col-sm-12">
                    <!-- Create new key form -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New API Key</h5>
                        </div>
                        <div class="card-body">
                            <form id="createKeyForm">
                                <div class="mb-3">
                                    <label for="userSearch" class="form-label">Search User</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="userSearch" placeholder="Search by username or email">
                                        <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Find the user you want to create an API key for.</div>
                                </div>
                                
                                <div id="searchResults" class="mb-3 d-none">
                                    <label class="form-label">Search Results</label>
                                    <div class="list-group" id="userList"></div>
                                </div>
                                
                                <div id="selectedUserInfo" class="mb-3 d-none">
                                    <label class="form-label">Selected User</label>
                                    <div class="card">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong id="selectedUsername"></strong>
                                                    <div><small id="selectedEmail" class="text-muted"></small></div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearUserSelection">
                                                    <i class="fas fa-times"></i> Change
                                                </button>
                                            </div>
                                            <input type="hidden" id="selectedUserId">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="keyName" class="form-label">Key Name</label>
                                    <input type="text" class="form-control" id="keyName" placeholder="e.g., Development, Testing" required>
                                    <div class="form-text">Choose a name to help identify this key later.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">API Permissions</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row gy-2">
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="allowGet" checked>
                                                        <label class="form-check-label" for="allowGet">
                                                            <span class="badge bg-success method-badge">GET</span>
                                                            Read Access
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="allowPost">
                                                        <label class="form-check-label" for="allowPost">
                                                            <span class="badge bg-primary method-badge">POST</span>
                                                            Create Access
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="allowPut">
                                                        <label class="form-check-label" for="allowPut">
                                                            <span class="badge bg-warning text-dark method-badge">PUT</span>
                                                            Update Access
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="allowDelete">
                                                        <label class="form-check-label" for="allowDelete">
                                                            <span class="badge bg-danger method-badge">DELETE</span>
                                                            Delete Access
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="keyExpiration" class="form-label">Expires In</label>
                                    <select class="form-select" id="keyExpiration" required>
                                        <option value="1">1 minute (testing)</option>
                                        <option value="7">7 days</option>
                                        <option value="30" selected>30 days</option>
                                        <option value="60">60 days</option>
                                        <option value="90">90 days</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" disabled id="generateKeyButton">
                                    <i class="fas fa-plus-circle me-1"></i> Generate API Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <!-- List of all keys -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Active API Keys</h5>
                            <button class="btn btn-sm btn-light" onclick="loadAllApiKeys()">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div id="keysLoading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading API keys...</p>
                            </div>
                            <div id="noKeysMessage" class="alert alert-warning m-3 d-none">
                                <i class="fas fa-exclamation-triangle me-2"></i> There are no active API keys.
                            </div>
                            <div class="table-responsive">
                                <table id="apiKeysTable" class="table table-hover d-none mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>User</th>
                                            <th>Key</th>
                                            <th>Permissions</th>
                                            <th>Created</th>
                                            <th>Expires</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="keysTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revoke API Key Modal -->
        <div class="modal fade" id="revokeKeyModal" tabindex="-1" aria-labelledby="revokeKeyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="revokeKeyModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Revoke API Key
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-key text-danger fa-3x mb-3"></i>
                            <p class="lead">You are about to revoke the following API key:</p>
                            <p class="fw-bold fs-5" id="revokeKeyName"></p>
                            <p class="text-muted">Assigned to: <span id="revokeKeyUser"></span></p>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. Any applications using this key will immediately lose access.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-danger" id="confirmRevokeBtn">
                            <i class="fas fa-trash-alt me-1"></i>Revoke Key
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permission Update Modal -->
        <div class="modal fade" id="updatePermissionsModal" tabindex="-1" aria-labelledby="updatePermissionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="updatePermissionsModalLabel">
                            <i class="fas fa-edit me-2"></i>Update API Key Permissions
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 class="mb-0">Key: <span id="updatePermKeyName" class="fw-bold"></span></h6>
                            <p class="text-muted mb-3">Assigned to: <span id="updatePermKeyUser"></span></p>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="row gy-2">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="updateAllowGet">
                                            <label class="form-check-label" for="updateAllowGet">
                                                <span class="badge bg-success method-badge">GET</span>
                                                Read Access
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="updateAllowPost">
                                            <label class="form-check-label" for="updateAllowPost">
                                                <span class="badge bg-primary method-badge">POST</span>
                                                Create Access
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="updateAllowPut">
                                            <label class="form-check-label" for="updateAllowPut">
                                                <span class="badge bg-warning text-dark method-badge">PUT</span>
                                                Update Access
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="updateAllowDelete">
                                            <label class="form-check-label" for="updateAllowDelete">
                                                <span class="badge bg-danger method-badge">DELETE</span>
                                                Delete Access
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="updateKeyId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmUpdatePermBtn">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
        


        <div id="refreshIndicator" style="display: none;">
            Checking for expiring tokens...
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // Global variables
    let allExpiryKeys = [];
    let lastUpdateTime = Date.now();
    let nearestExpiryKey = null;
    let nextRefreshTime = Infinity;
    let expiryCountdownInterval = null; // Keep track of the interval

    // Load API keys on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Load all API keys
        loadAllApiKeys();
        
        // Set up event handlers
        document.getElementById('createKeyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createApiKey();
        });
        
        document.getElementById('confirmRevokeBtn').addEventListener('click', handleRevokeConfirmation);
        document.getElementById('confirmUpdatePermBtn').addEventListener('click', handlePermissionUpdate);
        
        // User search functionality - Live search
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const query = this.value.trim();
            
            // Only perform search if at least 2 characters are entered
            if (query.length >= 2) {
                performUserSearch(query);
            } else if (query.length === 0) {
                // Clear results if search field is empty
                document.getElementById('searchResults').classList.add('d-none');
                document.getElementById('userList').innerHTML = '';
            }
        });
        
        // Keep the search button for visual consistency
        document.getElementById('searchButton').addEventListener('click', function() {
            const query = document.getElementById('userSearch').value.trim();
            if (query.length >= 2) {
                performUserSearch(query);
            }
        });
        
        document.getElementById('clearUserSelection').addEventListener('click', function() {
            document.getElementById('selectedUserInfo').classList.add('d-none');
            document.getElementById('searchResults').classList.remove('d-none');
            document.getElementById('userSearch').value = '';
            document.getElementById('generateKeyButton').disabled = true;
        });
    });

    function showApiUsageModal() {
        const apiUsageModal = new bootstrap.Modal(document.getElementById('apiUsageModal'));
        apiUsageModal.show();
    }

    // Function to perform the user search
    function performUserSearch(query) {
        const searchResultsContainer = document.getElementById('searchResults');
        const userList = document.getElementById('userList');
        
        // Show loading state
        userList.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
        searchResultsContainer.classList.remove('d-none');
        
        fetch(`../../api/api-keys-handler.php?action=search_users&q=${encodeURIComponent(query)}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            userList.innerHTML = '';
            
            if (data.users && data.users.length > 0) {
                data.users.forEach(user => {
                    userList.insertAdjacentHTML('beforeend', `
                        <div class="list-group-item list-group-item-action user-search-result" 
                            onclick="selectUser(${user.userId}, '${user.username}', '${user.email}')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${user.username}</strong>
                                    <div><small class="text-muted">${user.email}</small></div>
                                </div>
                                <span class="badge bg-${user.role === 'admin' ? 'danger' : 'secondary'}">${user.role}</span>
                            </div>
                        </div>
                    `);
                });
            } else {
                userList.innerHTML = '<div class="text-center p-3 text-muted">No users found</div>';
            }
        })
        .catch(err => {
            console.error(err);
            userList.innerHTML = '<div class="text-center p-3 text-danger">Error searching users</div>';
        });
    }

    // Fetch & render all API keys
    function loadAllApiKeys() {
        const keysLoading = document.getElementById('keysLoading');
        const noKeysMessage = document.getElementById('noKeysMessage');
        const apiKeysTable = document.getElementById('apiKeysTable');
        const keysTableBody = document.getElementById('keysTableBody');

        keysLoading.classList.remove('d-none');
        noKeysMessage.classList.add('d-none');
        apiKeysTable.classList.add('d-none');

        // Add cache-busting parameter to prevent browser caching
        const cacheBuster = new Date().getTime();
        
        fetch(`../../api/api-keys-handler.php?action=admin_list_all_keys&_=${cacheBuster}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            lastUpdateTime = Date.now();
            keysLoading.classList.add('d-none');

            // Filter out expired and revoked keys
            // IMPORTANT FIX: Make sure we're strictly checking is_revoked flag
            const activeKeys = data.keys ? data.keys.filter(key => 
                !key.is_expired && 
                key.is_active === 1 &&  // Make sure key is active
                (key.is_revoked === 0 || !key.is_revoked) // Ensure key is not revoked
            ) : [];

            if (activeKeys.length > 0) {
                apiKeysTable.classList.remove('d-none');
                keysTableBody.innerHTML = '';
                
                // Store all active keys for countdown
                allExpiryKeys = activeKeys;
                
                // Clear existing interval if any
                if (expiryCountdownInterval) {
                    clearInterval(expiryCountdownInterval);
                    expiryCountdownInterval = null;
                }
                
                // Determine nearest expiry key for the floating indicator
                findNearestExpiryKey(allExpiryKeys);

                activeKeys.forEach(key => {
                    // Format expiry text
                    const expiryDate = new Date(key.expires_at);
                    const now = new Date();
                    
                    let expiryText, expiryClass;
                    const days = key.days_remaining;
                    
                    if (days === 0 && key.hours_remaining === 0 && key.minutes_remaining === 0) {
                        // < 1 minute → seconds only
                        const sec = key.seconds_remaining || 0;
                        expiryText = `<span class="text-danger fw-bold">${sec}s</span>`;
                        expiryClass = 'text-danger fw-bold';
                    } else if (days === 0 && key.hours_remaining === 0) {
                        // < 1 hour → minutes only
                        expiryText = `<span class="text-danger">${key.minutes_remaining}m</span>`;
                        expiryClass = 'text-danger';
                    } else if (days === 0) {
                        // < 1 day → hours + minutes
                        expiryText = `<span class="text-warning">${key.hours_remaining}h ${key.minutes_remaining}m</span>`;
                        expiryClass = 'text-warning';
                    } else if (days < 7) {
                        // < 1 week → days + hours
                        expiryText = `<span class="text-warning">${days}d ${key.hours_remaining}h</span>`;
                        expiryClass = 'text-warning';
                    } else {
                        // ≥ 1 week → days only
                        expiryText = `${days}d`;
                        expiryClass = '';
                    }
                    
                    // Highlight the row if this is the nearest to expiry
                    const highlight = (nearestExpiryKey && key.id === nearestExpiryKey.id)
                                ? 'table-warning' : '';
                    
                    // Format permission badges
                    const permissionBadges = [];
                    if (key.allow_get == 1) permissionBadges.push('<span class="badge bg-success me-1">GET</span>');
                    if (key.allow_post == 1) permissionBadges.push('<span class="badge bg-primary me-1">POST</span>');
                    if (key.allow_put == 1) permissionBadges.push('<span class="badge bg-warning text-dark me-1">PUT</span>');
                    if (key.allow_delete == 1) permissionBadges.push('<span class="badge bg-danger me-1">DELETE</span>');
                    
                    const permissionsHtml = permissionBadges.length > 0 
                        ? permissionBadges.join('') 
                        : '<span class="badge bg-secondary">None</span>';

                    keysTableBody.insertAdjacentHTML('beforeend', `
                        <tr class="${highlight}" data-key-id="${key.id}" data-expires-in="${(key.days_remaining*86400 + key.hours_remaining*3600 + key.minutes_remaining*60 + (key.seconds_remaining || 0))}">
                            <td>${key.name}</td>
                            <td>
                                <div><strong>${key.username}</strong></div>
                                <small class="text-muted">${key.email}</small>
                            </td>
                            <td><code>${key.masked_key}</code></td>
                            <td>${permissionsHtml}</td>
                            <td>${new Date(key.created_at).toLocaleString()}</td>
                            <td class="${expiryClass}" data-expiry-cell="${key.id}">${expiryText}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editPermissions(${key.id}, '${key.name}', '${key.username}', ${key.allow_get}, ${key.allow_post}, ${key.allow_put}, ${key.allow_delete})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="revokeKey(${key.id}, '${key.name}', '${key.username}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>`);
                });
                
                // Start countdown timer after updating the table
                startExpiryCountdown();
                
                // Show the indicator if we have any non-expired keys
                const indicator = document.getElementById('refreshIndicator');
                if (allExpiryKeys.length > 0) {
                    indicator.style.display = 'block';
                } else {
                    indicator.style.display = 'none';
                }
            } else {
                noKeysMessage.classList.remove('d-none');
                nearestExpiryKey = null;
                nextRefreshTime = Infinity;
                allExpiryKeys = [];
                
                // Hide the indicator if no keys
                document.getElementById('refreshIndicator').style.display = 'none';
                
                // Clear any existing interval
                if (expiryCountdownInterval) {
                    clearInterval(expiryCountdownInterval);
                    expiryCountdownInterval = null;
                }
            }
        })
        .catch(err => {
            console.error(err);
            keysLoading.classList.add('d-none');
            noKeysMessage.classList.remove('d-none');
            alert('Error loading API keys.');
            nearestExpiryKey = null;
            nextRefreshTime = Infinity;
            allExpiryKeys = [];
            
            // Hide the indicator in case of error
            document.getElementById('refreshIndicator').style.display = 'none';
            
            // Clear any existing interval
            if (expiryCountdownInterval) {
                clearInterval(expiryCountdownInterval);
                expiryCountdownInterval = null;
            }
        });
    }

    // Pick soonest expiry and schedule reload
    function findNearestExpiryKey(keys) {
        let best = null, bestSecs = Infinity;

        keys.forEach(k => {
            if (!k.is_expired) {
                const total = k.days_remaining*86400
                            + k.hours_remaining*3600
                            + k.minutes_remaining*60
                            + (k.seconds_remaining||0);
                if (total < bestSecs) {
                    bestSecs = total;
                    best = k;
                }
            }
        });

        nearestExpiryKey = best;
        if (best) {
            const expireAt = Date.now() + bestSecs*1000;
            nextRefreshTime = expireAt + 1000;
        } else {
            nextRefreshTime = Infinity;
        }
    }

    // Start 1s ticker - make sure this is called only once when keys are loaded
    function startExpiryCountdown() {
        // Clear any existing interval first to prevent multiple timers
        if (expiryCountdownInterval) {
            clearInterval(expiryCountdownInterval);
        }
        
        const indicator = document.getElementById('refreshIndicator');
        indicator.style.display = allExpiryKeys.length > 0 ? 'block' : 'none';

        // Set fresh interval for countdown
        expiryCountdownInterval = setInterval(() => {
            // Check if we need to refresh the whole list
            if (Date.now() >= nextRefreshTime) {
                // Hide the new key alert if it's visible (if the key got expired we dont want to show the copy button)
                document.getElementById('newKeyAlert').classList.add('d-none');
                loadAllApiKeys();
                return;
            }
            
            // Update all expiry countdowns
            updateAllExpiryCountdowns();
            
            // Also update the floating indicator
            updateFloatingIndicator();
        }, 1000);
    }

    // Update all key expiry cells based on elapsed time
    function updateAllExpiryCountdowns() {
        const rows = document.querySelectorAll('tr[data-expires-in]');
        const elapsed = Math.floor((Date.now() - lastUpdateTime) / 1000);
        
        rows.forEach(row => {
            const keyId = row.getAttribute('data-key-id');
            const initialSeconds = parseInt(row.getAttribute('data-expires-in'), 10);
            
            if (initialSeconds <= 0) return; // Skip expired keys
            
            // Calculate current seconds left
            const secsLeft = Math.max(0, initialSeconds - elapsed);
            
            // Don't update the row's data attribute to avoid affecting the timer calculation
            
            // Find and update the expiry cell
            const cell = row.querySelector(`[data-expiry-cell="${keyId}"]`);
            if (!cell) return;
            
            let html, cls = '';
            
            if (secsLeft <= 0) {
                // Just expired
                html = 'Expired';
                cls = 'text-danger';
                
                // Disable the buttons
                const buttons = row.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = true);
                
                // Reload keys list to get proper expired state
                setTimeout(() => loadAllApiKeys(), 2000);
                
            } else if (secsLeft < 60) {
                // < 1 minute → seconds
                html = `<span class="text-danger fw-bold">${secsLeft}s</span>`;
                cls = 'text-danger fw-bold';
                
            } else if (secsLeft < 3600) {
                // < 1 hour → minutes + seconds
                const mins = Math.floor(secsLeft / 60);
                const secs = secsLeft % 60;
                html = `<span class="text-danger">${mins}m ${secs}s</span>`;
                cls = 'text-danger';
                
            } else if (secsLeft < 86400) {
                // < 1 day → hours + minutes
                const hrs = Math.floor(secsLeft / 3600);
                const mins = Math.floor((secsLeft % 3600) / 60);
                html = `<span class="text-warning">${hrs}h ${mins}m</span>`;
                cls = 'text-warning';
                
            } else if (secsLeft < 604800) {
                // < 7 days → days + hours
                const days = Math.floor(secsLeft / 86400);
                const hrs = Math.floor((secsLeft % 86400) / 3600);
                html = `<span class="text-warning">${days}d ${hrs}h</span>`;
                cls = 'text-warning';
                
            } else {
                // ≥ 7 days → days only
                const days = Math.floor(secsLeft / 86400);
                html = `${days}d`;
            }
            
            cell.innerHTML = html;
            cell.className = cls;
        });
    }

    // Update floating indicator with time remaining for nearest expiry key
    function updateFloatingIndicator() {
        const indicator = document.getElementById('refreshIndicator');
        
        if (!nearestExpiryKey) {
            indicator.textContent = 'No keys to track';
            return;
        }
        
        // Calculate based on elapsed time since last update
        const elapsed = Math.floor((Date.now() - lastUpdateTime) / 1000);
        
        // Use the nearest key data directly
        const totalInitialSeconds = nearestExpiryKey.days_remaining * 86400 + 
                                   nearestExpiryKey.hours_remaining * 3600 + 
                                   nearestExpiryKey.minutes_remaining * 60 + 
                                   (nearestExpiryKey.seconds_remaining || 0);
        
        const secsLeft = Math.max(0, totalInitialSeconds - elapsed);
        
        // Format the display
        let disp;
        if (secsLeft >= 86400) {
            // ≥ 24h → days + hours
            const days = Math.floor(secsLeft / 86400);
            const hours = Math.floor((secsLeft % 86400) / 3600);
            disp = `${days}d ${hours}h`;
        } else if (secsLeft >= 3600) {
            // ≥1h and <24h → hours + minutes
            const hours = Math.floor(secsLeft / 3600);
            const minutes = Math.floor((secsLeft % 3600) / 60);
            disp = `${hours}h ${minutes}m`;
        } else if (secsLeft >= 60) {
            // ≥1min and <1h → minutes + seconds
            const minutes = Math.floor(secsLeft / 60);
            const seconds = secsLeft % 60;
            disp = `${minutes}m ${seconds}s`;
        } else {
            // <1min → seconds only
            disp = `${secsLeft}s`;
        }
        
        indicator.innerHTML = `Key "${nearestExpiryKey.name}" expires in ${disp}`;
        indicator.style.backgroundColor = secsLeft < 300
            ? 'rgba(220,53,69,0.9)' 
            : 'rgba(0,0,0,0.7)';
    }

    // Select a user from search results
    function selectUser(userId, username, email) {
        // Update selected user display
        document.getElementById('selectedUsername').textContent = username;
        document.getElementById('selectedEmail').textContent = email;
        document.getElementById('selectedUserId').value = userId;
        
        // Show selected user info and hide search results
        document.getElementById('selectedUserInfo').classList.remove('d-none');
        document.getElementById('searchResults').classList.add('d-none');
        
        // Enable the generate key button
        document.getElementById('generateKeyButton').disabled = false;
    }

    // Function to create a new API key
    function createApiKey() {
        const keyName = document.getElementById('keyName').value;
        const keyExpiration = document.getElementById('keyExpiration').value;
        const userId = document.getElementById('selectedUserId').value;
        const allowGet = document.getElementById('allowGet').checked ? 1 : 0;
        const allowPost = document.getElementById('allowPost').checked ? 1 : 0;
        const allowPut = document.getElementById('allowPut').checked ? 1 : 0;
        const allowDelete = document.getElementById('allowDelete').checked ? 1 : 0;
        
        const submitButton = document.getElementById('generateKeyButton');
        
        // Validate inputs
        if (!keyName || !userId) {
            alert('Please provide all required information');
            return;
        }
        
        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating...';
        
        fetch('../../api/api-keys-handler.php?action=create_key', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                name: keyName,
                target_user_id: userId,
                expires_in_days: keyExpiration,
                allow_get: allowGet,
                allow_post: allowPost,
                allow_put: allowPut,
                allow_delete: allowDelete
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Reset form and button
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Generate API Key';
            document.getElementById('createKeyForm').reset();
            
            // Reset user selection
            document.getElementById('selectedUserInfo').classList.add('d-none');
            document.getElementById('generateKeyButton').disabled = true;
            
            // Show the API key in the alert
            document.getElementById('newApiKey').value = data.key.api_key;
            document.getElementById('newKeyUser').textContent = document.getElementById('selectedUsername').textContent;
            document.getElementById('newKeyAlert').classList.remove('d-none');
            
            // Format the expiry date
            const expiryDate = new Date(data.key.expires_at);
            document.getElementById('newKeyExpiry').textContent = expiryDate.toLocaleString();
            
            // Scroll to the alert
            document.getElementById('newKeyAlert').scrollIntoView({ behavior: 'smooth' });
            
            // Reload the keys table
            loadAllApiKeys();
        })
        .catch(error => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Generate API Key';
            
            console.error('Error details:', error);
            alert('Error creating API key: ' + error);
        });
    }

    // Function to edit permissions
    function editPermissions(keyId, keyName, username, allowGet, allowPost, allowPut, allowDelete) {
        // Set the key information
        document.getElementById('updateKeyId').value = keyId;
        document.getElementById('updatePermKeyName').textContent = keyName;
        document.getElementById('updatePermKeyUser').textContent = username;
        
        // Set the permission checkboxes
        document.getElementById('updateAllowGet').checked = allowGet === 1;
        document.getElementById('updateAllowPost').checked = allowPost === 1;
        document.getElementById('updateAllowPut').checked = allowPut === 1;
        document.getElementById('updateAllowDelete').checked = allowDelete === 1;
        
        // Show the modal
        const permModal = new bootstrap.Modal(document.getElementById('updatePermissionsModal'));
        permModal.show();
    }

    // Function to handle permission update
    function handlePermissionUpdate() {
        const keyId = document.getElementById('updateKeyId').value;
        const allowGet = document.getElementById('updateAllowGet').checked ? 1 : 0;
        const allowPost = document.getElementById('updateAllowPost').checked ? 1 : 0;
        const allowPut = document.getElementById('updateAllowPut').checked ? 1 : 0;
        const allowDelete = document.getElementById('updateAllowDelete').checked ? 1 : 0;
        
        const permModal = bootstrap.Modal.getInstance(document.getElementById('updatePermissionsModal'));
        const updateButton = this;
        const originalHTML = updateButton.innerHTML;
        
        // Disable button and show loading state
        updateButton.disabled = true;
        updateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
        
        fetch('../../api/api-keys-handler.php?action=update_key_permissions', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                key_id: keyId,
                allow_get: allowGet,
                allow_post: allowPost,
                allow_put: allowPut,
                allow_delete: allowDelete
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Hide the modal
                permModal.hide();
                
                // Show temporary success message
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show';
                successAlert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> 
                    API key permissions updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container').insertBefore(successAlert, document.querySelector('.row'));
                
                // Auto dismiss the alert after 3 seconds
                setTimeout(() => {
                    if (successAlert.parentNode) {
                        successAlert.classList.remove('show');
                        setTimeout(() => successAlert.remove(), 300);
                    }
                }, 3000);
                
                // Reload the keys table
                loadAllApiKeys();
                
                // Reset button state
                updateButton.disabled = false;
                updateButton.innerHTML = originalHTML;
            } else {
                // Reset button state
                updateButton.disabled = false;
                updateButton.innerHTML = originalHTML;
                
                // Hide the modal
                permModal.hide();
                
                // Show error message
                alert('Error updating permissions: ' + data.error);
            }
        })
        .catch(error => {
            // Reset button state
            updateButton.disabled = false;
            updateButton.innerHTML = originalHTML;
            
            // Hide the modal
            permModal.hide();
            
            console.error('Error details:', error);
            alert('Error updating permissions: ' + error);
        });
    }

    // Function to revoke an API key
    function revokeKey(keyId, keyName, username) {
        // Populate the modal with key details
        document.getElementById('revokeKeyName').textContent = keyName;
        document.getElementById('revokeKeyUser').textContent = username;
        document.getElementById('confirmRevokeBtn').setAttribute('data-key-id', keyId);
        
        // Show the modal
        const revokeModal = new bootstrap.Modal(document.getElementById('revokeKeyModal'));
        revokeModal.show();
    }

    // Function to handle revocation confirmation
    function handleRevokeConfirmation() {
        const keyId = this.getAttribute('data-key-id');
        const revokeModal = bootstrap.Modal.getInstance(document.getElementById('revokeKeyModal'));
        const revokeButton = this;
        const originalHTML = revokeButton.innerHTML;
        
        // Disable button and show loading state
        revokeButton.disabled = true;
        revokeButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Revoking...';
        
        fetch(`../../api/api-keys-handler.php?action=revoke_key&key_id=${keyId}`, {
            method: 'DELETE',
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Hide the modal
                revokeModal.hide();
                
                // Hide the new key alert if it's visible
                document.getElementById('newKeyAlert').classList.add('d-none');
                
                // Show temporary success message
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success alert-dismissible fade show';
                successAlert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> 
                    API key has been successfully revoked.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.container').insertBefore(successAlert, document.querySelector('.row'));
                
                // Auto dismiss the alert after 3 seconds
                setTimeout(() => {
                    if (successAlert.parentNode) {
                        successAlert.classList.remove('show');
                        setTimeout(() => successAlert.remove(), 300);
                    }
                }, 3000);
                
                // Immediate removal of the row from the table
                const row = document.querySelector(`tr[data-key-id="${keyId}"]`);
                if (row) {
                    row.remove();
                    
                    // Check if table is now empty
                    if (document.querySelectorAll('#keysTableBody tr').length === 0) {
                        document.getElementById('apiKeysTable').classList.add('d-none');
                        document.getElementById('noKeysMessage').classList.remove('d-none');
                    }
                }
                
                // Update the allExpiryKeys array
                allExpiryKeys = allExpiryKeys.filter(k => k.id != keyId);
                
                // Recalculate nearest expiry if needed
                if (nearestExpiryKey && nearestExpiryKey.id == keyId) {
                    findNearestExpiryKey(allExpiryKeys);
                    
                    // Update the floating indicator
                    const indicator = document.getElementById('refreshIndicator');
                    if (allExpiryKeys.length === 0) {
                        indicator.style.display = 'none';
                    } else {
                        updateFloatingIndicator();
                    }
                }
                
                // Reset button state
                revokeButton.disabled = false;
                revokeButton.innerHTML = originalHTML;
            } else {
                // Reset button state
                revokeButton.disabled = false;
                revokeButton.innerHTML = originalHTML;
                
                // Hide the modal
                revokeModal.hide();
                
                // Show error message
                alert('Error revoking API key: ' + data.error);
            }
        })
        .catch(error => {
            // Reset button state
            revokeButton.disabled = false;
            revokeButton.innerHTML = originalHTML;
            
            // Hide the modal
            revokeModal.hide();
            
            console.error('Error details:', error);
            alert('Error revoking API key: ' + error);
        });
    }

    // Function to copy API key to clipboard
    function copyApiKey() {
        const apiKeyInput = document.getElementById('newApiKey');
        apiKeyInput.select();
        document.execCommand('copy');
        
        // Show feedback
        const copyButton = apiKeyInput.nextElementSibling;
        const originalText = copyButton.innerHTML;
        copyButton.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
        copyButton.classList.replace('btn-outline-primary', 'btn-success');
        
        // Reset button after 2 seconds
        setTimeout(() => {
            copyButton.innerHTML = originalText;
            copyButton.classList.replace('btn-success', 'btn-outline-primary');
        }, 2000);
    }
    </script>
</body>
</html>