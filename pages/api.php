<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
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
        /* Fade in animation for new key alert */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        /* Custom badges for expiry status */
        .badge-expiry {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <?php
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $pageTitle = "API Keys";
    ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0"><i class="fas fa-key text-primary me-2"></i>Manage API Keys</h1>
                </div>
                
                <p class="lead text-muted mb-4">Create and manage API keys to access our services programmatically.</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div>
                        <strong>Security Notice:</strong> API keys provide full access to your account. Keep them secure and never share them publicly.
                    </div>
                </div>
                
                <!-- Display when a new key is created -->
                <div id="newKeyAlert" class="alert alert-success d-none fade-in" role="alert">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-key fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5>Your New API Key</h5>
                            <p>Copy your API key now. For security reasons, it won't be shown again.</p>
                            <div class="api-key-container mb-2">
                                <div class="input-group">
                                    <input type="text" id="newApiKey" class="form-control api-key-display" readonly>
                                    <button class="btn btn-outline-primary btn-copy" type="button" onclick="copyApiKey()">
                                        <i class="fas fa-copy me-1"></i> Copy
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-info" onclick="showApiUsageModal()">
                                        <i class="fas fa-question-circle me-1"></i> How to Use
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">This key will expire on <span id="newKeyExpiry" class="fw-bold"></span></small>
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
                                <label for="keyName" class="form-label">Key Name</label>
                                <input type="text" class="form-control" id="keyName" placeholder="e.g., Development, Testing" required>
                                <div class="form-text">Choose a name to help you identify this key later.</div>
                            </div>
                            <div class="mb-3">
                                <label for="keyExpiration" class="form-label">Expires In</label>
                                <select class="form-select" id="keyExpiration" required>
                                    <option value="1">1 minute</option>
                                    <option value="7">7 days</option>
                                    <option value="30" selected>30 days</option>
                                    <option value="60">60 days</option>
                                    <option value="90">90 days</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Generate New API Key
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <!-- List of existing keys -->
                <div class="card">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Your API Keys</h5>
                        <button class="btn btn-sm btn-light" onclick="loadApiKeys()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="keysLoading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading your API keys...</p>
                        </div>
                        <div id="noKeysMessage" class="alert alert-warning m-3 d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i> You don't have any active API keys.
                        </div>
                        <div class="table-responsive">
                            <table id="apiKeysTable" class="table table-hover d-none mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Key</th>
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

   <!-- API Usage Information Modal -->
<div class="modal fade" id="apiUsageModal" tabindex="-1" aria-labelledby="apiUsageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="apiUsageModalLabel">
                    <i class="fas fa-code me-2"></i>Using Your API Key
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h5><i class="fas fa-check-circle text-success me-2"></i>Verifying Your API Key</h5>
                    <p>You can verify your API key is working by making a test request:</p>
                    <div class="bg-light p-3 rounded mb-3">
                        <pre class="mb-0"><code>curl -H "X-API-Key: YOUR_API_KEY_HERE" https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=verify</code></pre>
                    </div>
                    <p>A successful response will return your account information:</p>
                    <div class="bg-light p-3 rounded">
                        <pre><code>{
    "status": "success",
    "message": "API key is valid",
    "data": {
        "user_id": 123,
        "account_type": "standard",
        "rate_limit": 1000
    }
}</code></pre>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5><i class="fas fa-code text-primary me-2"></i>Example API Usage</h5>
                    <ul class="nav nav-tabs" id="exampleTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="curl-tab" data-bs-toggle="tab" data-bs-target="#curl" type="button" role="tab" aria-selected="true">cURL</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="js-tab" data-bs-toggle="tab" data-bs-target="#javascript" type="button" role="tab" aria-selected="false">JavaScript</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button" role="tab" aria-selected="false">PHP</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0 rounded-bottom">
                        <div class="tab-pane fade show active" id="curl" role="tabpanel">
                            <pre><code>curl -X GET \
    'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data' \
    -H 'X-API-Key: YOUR_API_KEY_HERE'</code></pre>
                        </div>
                        <div class="tab-pane fade" id="javascript" role="tabpanel">
                            <pre><code>fetch('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', {
    method: 'GET',
    headers: {
        'X-API-Key': 'YOUR_API_KEY_HERE'
    }
    })
    .then(response => response.json())
    .then(data => console.log(data));</code></pre>
                        </div>
                        <div class="tab-pane fade" id="php" role="tabpanel">
                            <pre><code>&lt;?php
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: YOUR_API_KEY_HERE'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    print_r($data);
    ?></code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Local Development:</strong> When testing locally with self-signed certificates, use the <code>-k</code> flag with cURL to bypass certificate verification: <code>curl -k -H "X-API-Key: YOUR_API_KEY_HERE" http://localhost/Webengineering/api/index.php?endpoint=verify</code>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Security Note:</strong> Never expose your API key in client-side code or public repositories.
                </div>
                
                <div class="mb-3">
                    <h5><i class="fas fa-book text-secondary me-2"></i>Further Documentation</h5>
                    <p>For complete API documentation, including all available endpoints and parameters, visit our <a href="documentation.php" target="_blank">API Documentation</a> page.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <a href="documentation.php" class="btn btn-primary">
                    <i class="fas fa-book me-1"></i>Full Documentation
                </a>
            </div>
        </div>
    </div>
</div>

    <div id="refreshIndicator" style="position: fixed; bottom: 20px; right: 20px; padding: 8px 15px; background-color: rgba(0, 0, 0, 0.7); color: white; border-radius: 4px; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 9999; display: none;">
        Checking for expiring tokens...
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
        // Load API keys on page load
        loadApiKeys();
        
        // Set up event handler for the create key form
        document.getElementById('createKeyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createApiKey();
        });
        
        // Set up event handler for the confirm revoke button
        document.getElementById('confirmRevokeBtn').addEventListener('click', handleRevokeConfirmation);
    });

    function showApiUsageModal() {
        const apiUsageModal = new bootstrap.Modal(document.getElementById('apiUsageModal'));
        apiUsageModal.show();
    }

    // Fetch & render API keys
    function loadApiKeys() {
        const keysLoading = document.getElementById('keysLoading');
        const noKeysMessage = document.getElementById('noKeysMessage');
        const apiKeysTable = document.getElementById('apiKeysTable');
        const keysTableBody = document.getElementById('keysTableBody');

        keysLoading.classList.remove('d-none');
        noKeysMessage.classList.add('d-none');
        apiKeysTable.classList.add('d-none');

        fetch('../api/api-keys-handler.php?action=list_keys', {
            method: 'GET',
            credentials: 'include'
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            lastUpdateTime = Date.now();
            keysLoading.classList.add('d-none');

            if (data.keys && data.keys.length) {
                apiKeysTable.classList.remove('d-none');
                keysTableBody.innerHTML = '';

                // Store all non-expired keys for countdown
                allExpiryKeys = data.keys.filter(key => !key.is_expired);
                
                // Clear existing interval if any
                if (expiryCountdownInterval) {
                    clearInterval(expiryCountdownInterval);
                    expiryCountdownInterval = null;
                }
                
                // Determine nearest expiry key for the floating indicator
                findNearestExpiryKey(allExpiryKeys);

                data.keys.forEach(key => {
                    // Calculate exact expiry values
                    let expiryText, expiryClass;
                    
                    if (key.is_expired) {
                        // 1) Already expired
                        expiryText = 'Expired';
                        expiryClass = 'text-danger';
                    } else if (
                        key.days_remaining === 0 &&
                        key.hours_remaining === 0 &&
                        key.minutes_remaining === 0
                    ) {
                        // 2) < 1 minute → show seconds
                        const sec = key.seconds_remaining || 0;
                        expiryText = `<span class="text-danger fw-bold">${sec}s</span>`;
                        expiryClass = 'text-danger fw-bold';
                    } else if (
                        key.days_remaining === 0 &&
                        key.hours_remaining === 0
                    ) {
                        // 3) < 1 hour → show minutes
                        expiryText = `<span class="text-danger">${key.minutes_remaining}m</span>`;
                        expiryClass = 'text-danger';
                    } else if (key.days_remaining === 0) {
                        // 4) < 1 day → show hours + minutes
                        expiryText = `<span class="text-warning">${key.hours_remaining}h ${key.minutes_remaining}m</span>`;
                        expiryClass = 'text-warning';
                    } else if (key.days_remaining < 7) {
                        // 5) < 1 week → show days + hours
                        expiryText = `<span class="text-warning">${key.days_remaining}d ${key.hours_remaining}h</span>`;
                        expiryClass = 'text-warning';
                    } else {
                        // 6) ≥ 1 week → days only
                        expiryText = `${key.days_remaining}d`;
                        expiryClass = '';
                    }

                    const highlight = (!key.is_expired && nearestExpiryKey && key.id === nearestExpiryKey.id)
                                    ? 'table-warning' : '';

                    // Store the total seconds for countdown
                    const totalSeconds = (key.days_remaining * 86400) + 
                                    (key.hours_remaining * 3600) + 
                                    (key.minutes_remaining * 60) + 
                                    (key.seconds_remaining || 0);

                    keysTableBody.insertAdjacentHTML('beforeend', `
                        <tr class="${highlight}" data-key-id="${key.id}" data-expires-in="${key.is_expired ? 0 : totalSeconds}" data-created="${key.created_at}">
                            <td>${key.name}</td>
                            <td><code>${key.masked_key}</code></td>
                            <td>${new Date(key.created_at).toLocaleString()}</td>
                            <td class="${expiryClass}" data-expiry-cell="${key.id}">${expiryText}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger"
                                        ${key.is_expired ? 'disabled' : ''}
                                        onclick="revokeKey(${key.id}, '${key.name}')">
                                    <i class="fas fa-trash-alt"></i> Revoke
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
                loadApiKeys();
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
                
                // Disable the revoke button
                const revokeBtn = row.querySelector('.btn-outline-danger');
                if (revokeBtn) revokeBtn.disabled = true;
                
                // Reload keys list to get proper expired state
                setTimeout(() => loadApiKeys(), 2000);
                
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
        
        // Find the nearest key's current info
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

    // Function to create a new API key
    function createApiKey() {
        const keyName = document.getElementById('keyName').value;
        const keyExpiration = document.getElementById('keyExpiration').value;
        const submitButton = document.querySelector('#createKeyForm button[type="submit"]');
        
        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating...';
        
        fetch('../api/api-keys-handler.php?action=create_key', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                name: keyName,
                expires_in_days: keyExpiration
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
            submitButton.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Generate New API Key';
            document.getElementById('createKeyForm').reset();
            
            // Show the API key in the alert
            document.getElementById('newApiKey').value = data.key.api_key;
            document.getElementById('newKeyAlert').classList.remove('d-none');
            
            // Format the expiry date and time correctly
            const expiryDate = new Date(data.key.expires_at);
            const expiryElem = document.getElementById('newKeyExpiry');
            if (expiryElem) {
                // Format with date and time
                const options = { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric', 
                    hour: '2-digit', 
                    minute: '2-digit',
                    timeZone: 'Europe/Nicosia'
                };
                expiryElem.textContent = expiryDate.toLocaleString(undefined, options);
            }
            
            // Scroll to the alert
            document.getElementById('newKeyAlert').scrollIntoView({ behavior: 'smooth' });
            
            // Reload the keys table
            loadApiKeys();
        })
        .catch(error => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Generate New API Key';
            
            console.error('Error details:', error);
            alert('Error creating API key: ' + error);
        });
    }

    // Function to revoke an API key - this is called when clicking the "Revoke" button in the table row
    function revokeKey(keyId, keyName) {
        // Populate the modal with key details
        document.getElementById('revokeKeyName').textContent = keyName;
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
        
        fetch(`../api/api-keys-handler.php?action=revoke_key&key_id=${keyId}`, {
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
                
                // Reload the keys table
                loadApiKeys();
                
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