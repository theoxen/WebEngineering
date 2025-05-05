<?php
/**
 * User API Keys View
 * Allows users to view their assigned API keys
 */

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$pageTitle = "My API Keys";
// Include sidebar
include_once('../components/sidebar/sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My API Keys</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS for sidebar -->
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">
    <style>
        .main-content{
            margin-left: 250px;
            width: 80%;
            transition: all 0.3s;
            padding: 1rem;
            min-height: 100vh;
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
        .key-expires-soon {
            font-weight: bold;
        }
        .api-key-display {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875rem;
        }
        .permission-badge {
            min-width: 70px;
            display: inline-block;
            text-align: center;
            margin-right: 0.25rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .btn-copy {
            transition: all 0.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
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
        .doc-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .code-block {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.9rem;
            position: relative;
        }
        .code-language {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 12px;
            padding: 2px 8px;
            background-color: #e9ecef;
            border-radius: 0 4px 0 4px;
        }
    </style>
</head>
<body>
    <?php
    // Include sidebar
    include_once('../components/sidebar/sidebar.php');
    ?>
    
    <div class="main-content">
        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="mb-0"><i class="fas fa-key text-primary me-2"></i>My API Keys</h1>
                        <button class="btn btn-outline-primary" onclick="showApiUsageModal()">
                            <i class="fas fa-book me-2"></i>API Documentation
                        </button>
                    </div>
                    
                    <p class="lead text-muted mb-4">View your API keys and their permissions.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="fas fa-info-circle fa-lg me-3"></i>
                        <div>
                            <strong>Note:</strong> API keys provide access to our services programmatically. Keep them secure and never share them publicly.
                            If you need a new API key, please contact an administrator.
                        </div>
                    </div>
                    
                    <!-- Full API Key display (shown when user clicks "Show Key") -->
                    <div id="fullKeyAlert" class="alert alert-success d-none fade-in" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                            <i class="fas fa-key fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5>Your API Key</h5>
                            <p>Copy your API key now. For security reasons, it won't be shown again.</p>
                            <div class="api-key-container mb-2">
                                <div class="input-group">
                                    <input type="text" id="fullApiKey" class="form-control api-key-display" readonly>
                                    <button class="btn btn-outline-primary btn-copy" type="button" onclick="copyFullApiKey()">
                                        <i class="fas fa-copy me-1"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-end">
                                    <small class="text-muted">For security reasons, this key will be hidden when you close this message or leave the page.</small>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" onclick="hideFullKey()"></button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <!-- List of user's keys -->
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
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
                                <i class="fas fa-exclamation-triangle me-2"></i> You don't have any active API keys. Please contact an administrator if you need one.
                            </div>
                            <div class="table-responsive">
                                <table id="apiKeysTable" class="table table-hover d-none mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
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
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About API Permissions</h5>
                        </div>
                        <div class="card-body">
                            <p>Your API keys have specific permissions that control what operations they can perform:</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success permission-badge">GET</span>
                                        <div>
                                            <strong>Read Access</strong>
                                            <div><small class="text-muted">Allows retrieving data from the API</small></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary permission-badge">POST</span>
                                        <div>
                                            <strong>Create Access</strong>
                                            <div><small class="text-muted">Allows creating new data through the API</small></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-warning text-dark permission-badge">PUT</span>
                                        <div>
                                            <strong>Update Access</strong>
                                            <div><small class="text-muted">Allows updating existing data</small></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-danger permission-badge">DELETE</span>
                                        <div>
                                            <strong>Delete Access</strong>
                                            <div><small class="text-muted">Allows deleting data through the API</small></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Security Notice:</strong> Keep your API keys secure! They provide access to your account and should never be shared publicly or included in client-side code.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    
    <!-- API Key Reveal Modal -->
    <div class="modal fade" id="showKeyModal" tabindex="-1" aria-labelledby="showKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="showKeyModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Security Confirmation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <p class="lead">You are about to view your full API key.</p>
                        <p>Please confirm that:</p>
                        <ul class="text-start">
                            <li>You are in a secure location</li>
                            <li>No one else can see your screen</li>
                            <li>You understand that this key provides access to your account</li>
                        </ul>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Important:</strong> For security reasons, the full key will only be shown once. Make sure to copy it immediately if needed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmShowKeyBtn">
                        <i class="fas fa-eye me-1"></i>Show API Key
                    </button>
                </div>
            </div>
        </div>
    </div>
    
   <!-- API Documentation Modal -->
<div class="modal fade" id="apiUsageModal" tabindex="-1" aria-labelledby="apiUsageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="apiUsageModalLabel">
                    <i class="fas fa-book me-2"></i>API Documentation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left side navigation -->
                    <div class="col-md-3 mb-4">
                        <div class="list-group sticky-top pt-2">
                            <a href="#getting-started" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-rocket me-2"></i>Getting Started
                            </a>
                            <a href="#authentication" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-lock me-2"></i>Authentication
                            </a>
                            <a href="#endpoints" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-link me-2"></i>Endpoints
                            </a>
                            <a href="#error-handling" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error Handling
                            </a>
                            <a href="#rate-limits" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-tachometer-alt me-2"></i>Rate Limits
                            </a>
                            <a href="#code-examples" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-code me-2"></i>Code Examples
                            </a>
                            <a href="#best-practices" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-shield-alt me-2"></i>Best Practices
                            </a>
                        </div>
                    </div>
                    
                    <!-- Right side content -->
                    <div class="col-md-9">
                        <div class="tab-content">
                            <!-- Getting Started Section -->
                            <div class="tab-pane fade show active" id="getting-started">
                                <h3>Getting Started with the API</h3>
                                <p class="lead">Welcome to our API documentation. This guide will help you integrate our services into your applications.</p>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5><i class="fas fa-check text-success me-2"></i>Prerequisites</h5>
                                        <ul>
                                            <li>An active user account</li>
                                            <li>An API key with appropriate permissions</li>
                                            <li>Basic understanding of HTTP and RESTful APIs</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <p>Our API uses RESTful principles and returns data in JSON format. All requests should be made to the base URL:</p>
                                <div class="alert alert-secondary mb-4">
                                    <code class="user-select-all">https://cei326-omada1.cut.ac.cy/api/</code>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tip:</strong> If you're new to our API, start with the Authentication section to learn how to set up your first request.
                                </div>
                            </div>
                            
                            <!-- Authentication Section -->
                            <div class="tab-pane fade" id="authentication">
                                <h3>Authentication</h3>
                                <p class="lead">All API requests require authentication using API keys.</p>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="mb-3">API Key Authentication</h5>
                                        <p>Include your API key in all requests using the <code>X-API-Key</code> header:</p>
                                        
                                        <div class="code-block">
                                            <span class="code-language">HTTP</span>
                                            <pre>GET /api/index.php?endpoint=data HTTP/1.1
Host: cei326-omada1.cut.ac.cy
X-API-Key: YOUR_API_KEY_HERE</pre>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> Protect your API keys! Never expose them in client-side code, public repositories, or share them with unauthorized parties.
                                </div>
                                
                                <h5 class="mt-4">Verifying Your API Key</h5>
                                <p>You can verify your API key is working by making a test request to the verification endpoint:</p>
                                
                                <div class="code-block">
                                    <span class="code-language">cURL</span>
                                    <pre>curl -H "X-API-Key: YOUR_API_KEY_HERE" https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=verify</pre>
                                </div>
                                
                                <p>A successful response looks like this:</p>
                                
                                <div class="code-block">
                                    <span class="code-language">JSON</span>
                                    <pre>{
    "status": "success",
    "message": "API key is valid",
    "data": {
        "user_id": 40,
        "username": "username123",
        "email": "user@example.com",
        "permissions": {
            "get": true,
            "post": false,
            "put": false,
            "delete": false
        }
    }
}</pre>
                                </div>
                            </div>
                            
                            <!-- Endpoints Section -->
                            <div class="tab-pane fade" id="endpoints">
                                <h3>API Endpoints</h3>
                                <p class="lead">Our API offers several endpoints for different operations.</p>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Data Endpoint</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6><span class="badge bg-success me-2">GET</span> /api/index.php?endpoint=data</h6>
                                        <p>Retrieve users from the system based on your permissions.</p>
                                        
                                        <h6 class="mt-4">Example Response</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "status": "success",
    "data": [
        {
            "userId": 1,
            "username": "admin",
            "email": "admin@example.com",
            "email_verified": 1
        },
        {
            "userId": 2,
            "username": "user123",
            "email": "user@example.com",
            "email_verified": 0
        }
    ]
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4"><span class="badge bg-primary me-2">POST</span> /api/index.php?endpoint=data</h6>
                                        <p>Create a new user (requires POST permission).</p>
                                        
                                        <h6 class="mt-4">Request Body</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "username": "new_user",
    "email": "new@example.com",
    "password": "secure_password"
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4"><span class="badge bg-warning text-dark me-2">PUT</span> /api/index.php?endpoint=data</h6>
                                        <p>Update an existing user (requires PUT permission).</p>
                                        
                                        <h6 class="mt-4">Request Body</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "id": 1,
    "username": "updated_username",
    "email": "updated@example.com",
    "password": "new_password"
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4"><span class="badge bg-danger me-2">DELETE</span> /api/index.php?endpoint=data&id=1</h6>
                                        <p>Delete a user (requires DELETE permission).</p>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Verify Endpoint</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6><span class="badge bg-success me-2">GET</span> /api/index.php?endpoint=verify</h6>
                                        <p>Verify your API key and get user information.</p>
                                        
                                        <h6 class="mt-4">Example Response</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "status": "success",
    "message": "API key is valid",
    "data": {
        "user_id": 40,
        "username": "username123",
        "email": "user@example.com",
        "permissions": {
            "get": true,
            "post": false,
            "put": false,
            "delete": false
        }
    }
}</pre>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Auth Endpoint</h5>
                                    </div>
                                    <div class="card-body">
                                        <h6><span class="badge bg-primary me-2">POST</span> /api/index.php?endpoint=auth&action=login</h6>
                                        <p>Authenticate and get user information.</p>
                                        
                                        <h6 class="mt-4">Request Body</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "email": "your_email@example.com",
    "password": "your_password"
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4">Example Response</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "status": "success",
    "message": "Authentication successful",
    "user": {
        "userId": 40,
        "username": "username123",
        "email": "user@example.com"
    }
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4">Register New User (Requires API Key with POST permission)</h6>
                                        <p><span class="badge bg-primary me-2">POST</span> /api/index.php?endpoint=auth&action=register</p>
                                        
                                        <h6 class="mt-4">Request Headers</h6>
                                        <div class="code-block">
                                            <span class="code-language">HTTP</span>
                                            <pre>X-API-Key: YOUR_API_KEY</pre>
                                        </div>
                                        
                                        <h6 class="mt-4">Request Body</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "username": "new_username",
    "email": "new_user@example.com",
    "password": "secure_password"
}</pre>
                                        </div>
                                        
                                        <h6 class="mt-4">Example Response</h6>
                                        <div class="code-block">
                                            <span class="code-language">JSON</span>
                                            <pre>{
    "status": "success",
    "message": "Registration successful",
    "user": {
        "userId": 41,
        "username": "new_username",
        "email": "new_user@example.com"
    }
}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Error Handling Section -->
                            <div class="tab-pane fade" id="error-handling">
                                <h3>Error Handling</h3>
                                <p class="lead">Our API uses standard HTTP status codes and consistent error messages.</p>
                                
                                <h5 class="mt-4">HTTP Status Codes</h5>
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>200 OK</code></td>
                                            <td>The request was successful</td>
                                        </tr>
                                        <tr>
                                            <td><code>400 Bad Request</code></td>
                                            <td>The request contains invalid parameters</td>
                                        </tr>
                                        <tr>
                                            <td><code>401 Unauthorized</code></td>
                                            <td>Authentication failed or API key is missing</td>
                                        </tr>
                                        <tr>
                                            <td><code>403 Forbidden</code></td>
                                            <td>The API key doesn't have the required permissions</td>
                                        </tr>
                                        <tr>
                                            <td><code>404 Not Found</code></td>
                                            <td>The requested resource was not found</td>
                                        </tr>
                                        <tr>
                                            <td><code>429 Too Many Requests</code></td>
                                            <td>Rate limit exceeded</td>
                                        </tr>
                                        <tr>
                                            <td><code>500 Internal Server Error</code></td>
                                            <td>An error occurred on the server</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <h5 class="mt-4">Error Response Format</h5>
                                <p>All error responses follow this format:</p>
                                
                                <div class="code-block">
                                    <span class="code-language">JSON</span>
                                    <pre>{
    "status": "error",
    "error": "Detailed error message"
}</pre>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tip:</strong> Always check for the <code>status</code> field in responses to determine if your request was successful.
                                </div>
                            </div>
                            
                            <!-- Rate Limits Section -->
                            <div class="tab-pane fade" id="rate-limits">
                                <h3>Rate Limits</h3>
                                <p class="lead">To ensure API stability, we apply rate limits to all API keys.</p>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5>Current Rate Limits</h5>
                                        <ul>
                                            <li>100 requests per minute per API key</li>
                                            <li>5,000 requests per day per API key</li>
                                        </ul>
                                        
                                        <p>When you exceed a rate limit, you'll receive a <code>429 Too Many Requests</code> response.</p>
                                    </div>
                                </div>
                                
                                <h5>Rate Limit Headers</h5>
                                <p>Each response includes headers that show your current rate limit status:</p>
                                
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Header</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>X-RateLimit-Limit</code></td>
                                            <td>Maximum number of requests allowed per period</td>
                                        </tr>
                                        <tr>
                                            <td><code>X-RateLimit-Remaining</code></td>
                                            <td>Number of requests remaining in the current period</td>
                                        </tr>
                                        <tr>
                                            <td><code>X-RateLimit-Remaining</code></td>
                                            <td>Number of requests remaining in the current period</td>
                                        </tr>
                                        <tr>
                                            <td><code>X-RateLimit-Reset</code></td>
                                            <td>Time when the rate limit will reset (Unix timestamp)</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="alert alert-warning mt-4">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Best Practice:</strong> Implement exponential backoff in your applications to handle rate limit errors gracefully.
                                </div>
                            </div>
                            
                            <!-- Code Examples Section -->
                            <div class="tab-pane fade" id="code-examples">
                                <h3>Code Examples</h3>
                                <p class="lead">These examples demonstrate how to use our API in various programming languages.</p>
                                
                                <ul class="nav nav-tabs mb-3" id="codeExampleTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="curl-example-tab" data-bs-toggle="tab" 
                                                data-bs-target="#curl-example" type="button" role="tab">cURL</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="js-example-tab" data-bs-toggle="tab" 
                                                data-bs-target="#js-example" type="button" role="tab">JavaScript</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="php-example-tab" data-bs-toggle="tab" 
                                                data-bs-target="#php-example" type="button" role="tab">PHP</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="python-example-tab" data-bs-toggle="tab" 
                                                data-bs-target="#python-example" type="button" role="tab">Python</button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="codeExampleTabsContent">
                                    <div class="tab-pane fade show active" id="curl-example" role="tabpanel">
                                        <div class="code-block">
                                            <span class="code-language">cURL</span>
                                            <pre># Login to the API
curl -X POST \
"https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=auth&action=login" \
-H "Content-Type: application/json" \
-d '{"email": "your_email@example.com", "password": "your_password"}'

# Get data from API
curl -X GET \
"https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data" \
-H "X-API-Key: YOUR_API_KEY_HERE"

# Create a new user (requires POST permission)
curl -X POST \
"https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=auth&action=register" \
-H "X-API-Key: YOUR_API_KEY_HERE" \
-H "Content-Type: application/json" \
-d '{"username": "new_user", "email": "new@example.com", "password": "1!qQ1!qQ"}'

# Update user information (requires PUT permission)
curl -X PUT \
"https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data" \
-H "X-API-Key: YOUR_API_KEY_HERE" \
-H "Content-Type: application/json" \
-d '{"id": 1, "username": "updated_username", "email": "updated@example.com", "password": "NewP@ssw0rd"}'</pre>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="js-example" role="tabpanel">
                                        <div class="code-block">
                                            <span class="code-language">JavaScript</span>
                                            <pre>// Get data from API
fetch('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', {
    method: 'GET',
    headers: {
        'X-API-Key': 'YOUR_API_KEY_HERE'
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));

// Create a new record
fetch('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', {
    method: 'POST',
    headers: {
        'X-API-Key': 'YOUR_API_KEY_HERE',
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        name: 'New Item',
        value: 500
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));</pre>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="php-example" role="tabpanel">
                                        <div class="code-block">
                                            <span class="code-language">PHP</span>
                                            <pre>&lt;?php
// Get data from API
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

// Create a new record
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'New Item',
    'value' => 500
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: YOUR_API_KEY_HERE',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
print_r($data);
?></pre>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="python-example" role="tabpanel">
                                        <div class="code-block">
                                            <span class="code-language">Python</span>
                                            <pre>import requests
import json

# API Key
api_key = 'YOUR_API_KEY_HERE'

# Get data from API
response = requests.get(
    'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data',
    headers={'X-API-Key': api_key}
)

data = response.json()
print(data)

# Create a new record
new_record = {
    'name': 'New Item',
    'value': 500
}

response = requests.post(
    'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data',
    headers={
        'X-API-Key': api_key,
        'Content-Type': 'application/json'
    },
    data=json.dumps(new_record)
)

data = response.json()
print(data)</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Best Practices Section -->
                            <div class="tab-pane fade" id="best-practices">
                                <h3>Best Practices</h3>
                                <p class="lead">Follow these guidelines to use our API effectively and securely.</p>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5><i class="fas fa-shield-alt text-primary me-2"></i>Security Best Practices</h5>
                                        <ul>
                                            <li>Store API keys securely, never hardcode them in client-side applications</li>
                                            <li>Use environment variables or secure vaults to store API keys in your applications</li>
                                            <li>Rotate your API keys regularly to minimize risk in case of exposure</li>
                                            <li>Use the minimum required permissions for each API key</li>
                                            <li>Monitor API key usage for suspicious activity</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5><i class="fas fa-tachometer-alt text-success me-2"></i>Performance Best Practices</h5>
                                        <ul>
                                            <li>Implement caching for frequently accessed resources</li>
                                            <li>Use pagination parameters (<code>limit</code> and <code>offset</code>) for large datasets</li>
                                            <li>Include only the fields you need by using field selectors when available</li>
                                            <li>Implement retry logic with exponential backoff for failed requests</li>
                                            <li>Use HTTP compression when available</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <h5><i class="fas fa-code text-info me-2"></i>Integration Best Practices</h5>
                                        <ul>
                                            <li>Always check response status codes and handle errors appropriately</li>
                                            <li>Validate data before sending it to the API</li>
                                            <li>Implement proper logging for debugging and monitoring</li>
                                            <li>Consider using a client library for your language/framework</li>
                                            <li>Test your integration thoroughly with a test API key before going to production</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <a href="../pages/documentation.php" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Full Documentation
                </a>
            </div>
        </div>
    </div>
</div>
    
    <!-- Bottom Right Fixed Countdown Indicator -->
    <div id="refreshIndicator" style="display: none;">
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
        loadApiKeys();
        
        // Set up event handler for the confirm show key button
        document.getElementById('confirmShowKeyBtn').addEventListener('click', function() {
            const keyId = this.getAttribute('data-key-id');
            fetchFullApiKey(keyId);
            
            // Hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('showKeyModal'));
            modal.hide();
        });
    });

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
                    // Format expiry text
                    const expiryDate = new Date(key.expires_at);
                    const now = new Date();
                    const isExpired = key.is_expired || expiryDate < now;
                    
                    let expiryText, expiryClass;
                    if (isExpired) {
                        expiryText = 'Expired';
                        expiryClass = 'text-danger';
                    } else {
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
                    }
                    
                    // Highlight the row if this is the nearest to expiry
                    const highlight = (!isExpired && nearestExpiryKey && key.id === nearestExpiryKey.id)
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
                        <tr class="${highlight}" data-key-id="${key.id}" data-expires-in="${isExpired ? 0 : (key.days_remaining*86400 + key.hours_remaining*3600 + key.minutes_remaining*60 + (key.seconds_remaining || 0))}">
                            <td>${key.name}</td>
                            <td><code>${key.masked_key}</code></td>
                            <td>${permissionsHtml}</td>
                            <td>${new Date(key.created_at).toLocaleString()}</td>
                            <td class="${expiryClass}" data-expiry-cell="${key.id}">${expiryText}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info" 
                                        ${isExpired ? 'disabled' : ''} 
                                        onclick="showApiKey(${key.id}, '${key.name}')">
                                    <i class="fas fa-eye"></i> Show Key
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
                // Hide the full key alert if it's visible
                document.getElementById('fullKeyAlert').classList.add('d-none');
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
                
                // Disable the buttons
                const buttons = row.querySelectorAll('button');
                buttons.forEach(btn => btn.disabled = true);
                
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
    
    // Function to show the security confirmation modal
    function showApiKey(keyId, keyName) {
        const confirmBtn = document.getElementById('confirmShowKeyBtn');
        confirmBtn.setAttribute('data-key-id', keyId);
        
        // Update modal title with key name
        document.getElementById('showKeyModalLabel').innerHTML = 
            `<i class="fas fa-shield-alt me-2"></i>View Key: ${keyName}`;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('showKeyModal'));
        modal.show();
    }
    
    // Function to fetch the full API key
    function fetchFullApiKey(keyId) {
        fetch(`../api/api-keys-handler.php?action=get_full_key&key_id=${keyId}`, {
            method: 'GET',
            credentials: 'include'
        })
        .then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            if (data.status === 'success' && data.api_key) {
                // Display the full key
                document.getElementById('fullApiKey').value = data.api_key;
                document.getElementById('fullKeyAlert').classList.remove('d-none');
                
                // Scroll to the alert
                document.getElementById('fullKeyAlert').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Failed to retrieve API key.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error fetching API key: ' + err);
        });
    }
    
    // Function to copy the full API key to clipboard
    function copyFullApiKey() {
        const apiKeyInput = document.getElementById('fullApiKey');
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
    
    // Function to hide the full key alert
    function hideFullKey() {
        document.getElementById('fullKeyAlert').classList.add('d-none');
    }

    // Function to show API usage modal
    function showApiUsageModal() {
        const apiUsageModal = new bootstrap.Modal(document.getElementById('apiUsageModal'));
        apiUsageModal.show();
        
                // Ensure the "Getting Started" tab is active when the modal opens
                document.querySelector('a[href="#getting-started"]').classList.add('active');
        document.getElementById('getting-started').classList.add('show', 'active');
        
        // Make sure other tabs are not active
        document.querySelectorAll('.list-group-item.list-group-item-action:not([href="#getting-started"])').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Activate first subtab in any tab that has subtabs
        document.querySelectorAll('a[data-bs-toggle="list"]').forEach(tabLink => {
            tabLink.addEventListener('shown.bs.tab', function(event) {
                // Find target tab content
                const tabContent = document.querySelector(this.getAttribute('href'));
                
                // Check if this tab has inner tabs
                const innerTabLinks = tabContent.querySelectorAll('.nav-link');
                if (innerTabLinks.length > 0) {
                    // Activate first inner tab
                    innerTabLinks[0].classList.add('active');
                    
                    // Find and activate its content
                    const innerTabId = innerTabLinks[0].getAttribute('data-bs-target');
                    const innerTabContent = document.querySelector(innerTabId);
                    if (innerTabContent) {
                        innerTabContent.classList.add('show', 'active');
                    }
                }
            });
        });
    }
    </script>
</body>
</html>