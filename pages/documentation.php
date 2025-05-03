<?php
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <style>
        body {
            padding-top: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .sidebar {
            position: sticky;
            top: 1rem;
            height: calc(100vh - 2rem);
            overflow-y: auto;
        }
        
        .nav-pills .nav-link {
            border-radius: 0;
            padding: 0.5rem 1rem;
            color: #495057;
        }
        
        .nav-pills .nav-link.active,
        .nav-pills .show>.nav-link {
            background-color: #f8f9fa;
            color: #212529;
            border-left: 4px solid #0d6efd;
            font-weight: 500;
        }
        
        .nav-pills .nav-link:hover {
            background-color: #f1f3f5;
        }
        
        pre {
            background: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .endpoint {
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1.5rem;
        }
        
        .endpoint:last-child {
            border-bottom: none;
        }
        
        .method-badge {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.875rem;
            padding: 0.35rem 0.65rem;
            font-weight: 600;
        }
        
        .badge-get {
            background-color: #28a745;
            color: white;
        }
        
        .badge-post {
            background-color: #007bff;
            color: white;
        }
        
        .badge-put {
            background-color: #fd7e14;
            color: white;
        }
        
        .badge-delete {
            background-color: #dc3545;
            color: white;
        }
        
        .param-table th {
            width: 20%;
        }
        
        /* Example response tabs */
        .response-tabs .nav-link {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        
        .section-title {
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
        
        .sticky-top-offset {
            top: 1rem;
        }
        
        .alert-example {
            background-color: #f8f9fa;
            border-color: #e9ecef;
        }
        
        .endpoint-url {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            word-break: break-all;
        }
        
        .response-example {
            max-height: 400px;
            overflow-y: auto;
        }

        #searchDocs {
            margin-bottom: 1rem;
        }
        
        /* Mobile responsiveness improvements */
        @media (max-width: 991.98px) {
            .sidebar {
                position: relative;
                height: auto;
                margin-bottom: 2rem;
            }
            
            .sticky-top {
                position: relative;
                top: 0;
            }
            
            .endpoint-url code {
                font-size: 0.8rem;
            }
            
            pre {
                font-size: 0.85rem;
            }
            
            .table {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .method-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            h4 {
                font-size: 1.2rem;
            }
            
            h5 {
                font-size: 1rem;
            }
            
            .param-table th {
                width: 30%;
            }
            
            pre {
                padding: 0.75rem;
            }
            
            .sidebar .nav-link {
                padding: 0.4rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .sidebar .nav-pills .nav-pills .nav-link {
                padding-left: 1.5rem;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .endpoint-url {
                padding: 0.4rem;
                font-size: 0.75rem;
            }
            
            pre {
                font-size: 0.75rem;
                padding: 0.5rem;
            }
            
            .alert {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            /* Improve table responsiveness on very small screens */
            .table-responsive {
                font-size: 0.75rem;
            }
            
            /* Make sidebar toggleable on mobile */
            .mobile-nav-toggle {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
            }
            
            .sidebar-content {
                display: none;
            }
            
            .sidebar-content.show {
                display: block;
            }
        }
        
        /* Hamburger menu for mobile */
        .mobile-nav-toggle {
            display: none;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            text-align: center;
            margin-bottom: 1rem;
            cursor: pointer;
        }
        
        /* Code snippets in small screens */
        @media (max-width: 767.98px) {
            code {
                word-break: break-word;
            }
            
            pre code {
                white-space: pre-wrap;
            }
        }
        
        /* Ensure tables are scrollable on mobile */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>


    <header class="bg-dark py-3 mb-4">
        <div class="container">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                </div>
                <div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Mobile navigation toggle -->
            <div class="col-12 d-lg-none">
                <button class="mobile-nav-toggle w-100" id="toggleSidebar">
                    <i class="fas fa-bars me-2"></i> Navigation Menu
                </button>
            </div>
            
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <nav id="navbar-docs" class="navbar">
                        <div class="sidebar-content" id="sidebarContent">
                            <div class="mb-3">
                                <input type="text" id="searchDocs" class="form-control" placeholder="Search documentation...">
                            </div>
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="#section-introduction">Introduction</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#section-authentication">Authentication</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#section-errors">Error Handling</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#section-rate-limits">Rate Limits</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#section-endpoints">API Endpoints</a>
                                    <ul class="nav nav-pills flex-column ms-3">
                                        <li class="nav-item">
                                            <a class="nav-link" href="#endpoint-auth">Authentication</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#endpoint-users">Users</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#endpoint-data">Data</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#endpoint-api-keys">API Keys</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>API Base URL:</strong> <code>https://cei326-omada1.cut.ac.cy/api</code>
                </div>

                <section id="section-introduction">
                    <h2 class="section-title">Introduction</h2>
                    <p class="lead">Welcome to our API documentation. This guide will help you integrate your applications with our platform.</p>
                    <p>Our RESTful API provides programmatic access to our service, allowing you to:</p>
                    <ul>
                        <li>Authenticate users</li>
                        <li>Access user account information</li>
                        <li>View data entries</li>
                        <li>Generate and manage API keys</li>
                    </ul>
                    <p>All API access is over HTTPS, and all data is sent and received as JSON.</p>

                </section>

                <section id="section-authentication">
    <h2 class="section-title">Authentication</h2>
    <p>All API requests require authentication using either session-based authentication or an API key. You can obtain your API key from the <a href="api.php">API Keys Management</a> page.</p>
    
    <div class="card mb-4">
    <div class="card-header">
        API Key Authentication
    </div>
    <div class="card-body">
        <p>To authenticate API requests, include your API key in the <code>X-API-Key</code> header:</p>
        
        <h5>cURL Example:</h5>
        <div class="table-responsive">
            <pre><code class="language-bash">curl -X GET "https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data" -H "X-API-Key: YOUR_API_KEY_HERE"</code></pre>
        </div>
        
        <h5>JavaScript Example:</h5>
        <div class="table-responsive">
            <pre><code class="language-javascript">fetch('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', {
  method: 'GET',
  headers: {
    'X-API-Key': 'YOUR_API_KEY_HERE'
  }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
        </div>
        
        <h5>PHP Example:</h5>
        <div class="table-responsive">
            <pre><code class="language-php">$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: YOUR_API_KEY_HERE'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);</code></pre>
        </div>

        <h5>Python Example:</h5>
        <div class="table-responsive">
            <pre><code class="language-python">import requests

headers = {
    'X-API-Key': 'YOUR_API_KEY_HERE'
}

response = requests.get('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', headers=headers)
data = response.json()</code></pre>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Important:</strong> Keep your API keys secure. Do not share them in publicly accessible areas like GitHub or client-side code.
        </div>
        
        
    </div>
</div>
    
    <div class="card mb-4">
        <div class="card-header">
            Session-based Authentication
        </div>
        <div class="card-body">
            <p>For web applications and user interfaces, session-based authentication is automatically handled when you log in through the website interface. If you're developing a client application that needs to maintain sessions:</p>
            
            <h5>1. Log in through the authentication endpoint</h5>
            <p>Send a POST request to the login endpoint with valid credentials. Your application should store and manage the returned session cookies.</p>
            
            <h5>2. Include session cookies in subsequent requests</h5>
            <p>Most HTTP client libraries will automatically handle cookie management for you after login.</p>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Security Note:</strong> Session-based authentication should only be used in secure, trusted environments. For server-to-server communication or third-party integrations, API key authentication is recommended.
            </div>
            
            <h5>Example Implementation (JavaScript):</h5>
            <div class="table-responsive">
                <pre><code class="language-javascript">// Example of login and session management in JavaScript
async function login(email, password) {
  const response = await fetch('https://cei326-omada1.cut.ac.cy/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password }),
    credentials: 'include' // Important: This tells fetch to include cookies
  });
  
  return await response.json();
}

// For subsequent authenticated requests
async function fetchData() {
  const response = await fetch('https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data', {
    method: 'GET',
    credentials: 'include' // Include session cookies
  });
  
  return await response.json();
}</code></pre>
            </div>
        </div>
    </div>
</section>

                <section id="section-errors">
                    <h2 class="section-title">Error Handling</h2>
                    <p>Our API uses conventional HTTP response codes to indicate the success or failure of API requests.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Status Code</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>200 - OK</code></td>
                                    <td>The request was successful.</td>
                                </tr>
                                <tr>
                                    <td><code>201 - Created</code></td>
                                    <td>The resource was successfully created.</td>
                                </tr>
                                <tr>
                                    <td><code>400 - Bad Request</code></td>
                                    <td>The request was invalid or cannot be served.</td>
                                </tr>
                                <tr>
                                    <td><code>401 - Unauthorized</code></td>
                                    <td>Authentication failed or user doesn't have permissions.</td>
                                </tr>
                                <tr>
                                    <td><code>404 - Not Found</code></td>
                                    <td>The requested resource could not be found.</td>
                                </tr>
                                <tr>
                                    <td><code>405 - Method Not Allowed</code></td>
                                    <td>The HTTP method used is not supported for this resource.</td>
                                </tr>
                                <tr>
                                    <td><code>409 - Conflict</code></td>
                                    <td>The request could not be completed due to a conflict with the current state of the resource.</td>
                                </tr>
                                <tr>
                                    <td><code>429 - Too Many Requests</code></td>
                                    <td>You've exceeded the rate limit.</td>
                                </tr>
                                <tr>
                                    <td><code>500 - Internal Server Error</code></td>
                                    <td>An error occurred on the server.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 class="mt-4">Error Response Format</h5>
                    <p>Error responses will include a JSON object with the following structure:</p>
                    <div class="table-responsive">
                        <pre><code class="language-json">{
  "error": "A human-readable error message"
}</code></pre>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Common Error:</strong> When attempting to use POST, PUT, or DELETE methods with an API key, you will receive a 403 Forbidden response with the message: "Method not allowed. This API key only has read permissions."
                    </div>
                </section>

                <section id="section-rate-limits">
                    <h2 class="section-title">Rate Limits</h2>
                    <p>To ensure the stability of the API, rate limits are enforced. The default limits are:</p>
                    <ul>
                        <li><strong>Standard Users:</strong> 60 requests per minute</li>
                        <li><strong>Admin Users:</strong> 300 requests per minute</li>
                    </ul>
                    
                    <p>If you exceed the rate limit, you'll receive a 429 Too Many Requests response.</p>
                </section>

                <section id="section-endpoints">
                    <h2 class="section-title">API Endpoints</h2>
                    
                    <div id="endpoint-auth" class="endpoint">
                        <h3 class="mb-3">Authentication Endpoints</h3>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/auth/login</h4>
                            </div>
                            <p>Authenticates a user and creates a session.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=auth&action=login</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "your_password"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Authentication successful",
  "user": {
    "userId": 123,
    "username": "username123",
    "email": "user@example.com",
    "role": "user"
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/auth/register</h4>
                            </div>
                            <p>Creates a new user account. <strong>(Requires API key with POST permission)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=auth&action=register</code>
                            </div>
                            
                            <h5>Headers</h5>
                            <div class="table-responsive">
                                <pre><code class="language-http">X-API-Key: YOUR_API_KEY</code></pre>
                            </div>
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "secure_password"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Registration successful",
  "user": {
    "userId": 124,
    "username": "newuser",
    "email": "newuser@example.com",
    "role": "user"
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/auth/logout</h4>
                            </div>
                            <p>Ends the current user session.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/auth/logout</code>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Logout successful"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/auth/reset-password</h4>
                            </div>
                            <p>Initiates a password reset for a user.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/auth/reset-password</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "email": "user@example.com"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "If your email is registered, you will receive password reset instructions"
}</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div id="endpoint-users" class="endpoint">
                        <h3 class="mb-3">User Endpoints</h3>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/users</h4>
                            </div>
                            <p>Returns a list of users (admin only).</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/users</code>
                            </div>
                            
                            <h5>Query Parameters</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered param-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>page</code></td>
                                            <td>Integer</td>
                                            <td>No</td>
                                            <td>Page number (default: 1)</td>
                                        </tr>
                                        <tr>
                                            <td><code>limit</code></td>
                                            <td>Integer</td>
                                            <td>No</td>
                                            <td>Results per page (default: 20, max: 100)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "users": [
    {
      "userId": 1,
      "username": "admin",
      "email": "admin@example.com",
      "dateCreated": "2023-01-01T00:00:00Z",
      "role": "admin"
    },
    {
      "userId": 2,
      "username": "user",
      "email": "user@example.com",
      "dateCreated": "2023-01-02T00:00:00Z",
      "role": "user"
    }
  ],
  "pagination": {
    "total": 50,
    "page": 1,
    "limit": 20,
    "pages": 3
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/users/{id}</h4>
                            </div>
                            <p>Returns details of a specific user.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/users/123</code>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "userId": 123,
  "username": "username",
  "email": "user@example.com",
  "dateCreated": "2023-06-01T10:00:00Z",
  "role": "user"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/users/me</h4>
                            </div>
                            <p>Returns details of the currently authenticated user.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/users/me</code>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "userId": 123,
  "username": "username",
  "email": "user@example.com",
  "dateCreated": "2023-06-01T10:00:00Z",
  "role": "user"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-put me-2">PUT</span>
                                <h4 class="mb-0">/users/{id}</h4>
                            </div>
                            <p>Updates a user's information. <strong>(Not available with API key - session authentication only)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>PUT https://cei326-omada1.cut.ac.cy/api/users/123</code>
                            </div>
                            
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                            <pre><code class="language-json">{
  "username": "updated_username",
  "email": "updated_email@example.com"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "message": "User updated successfully"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-delete me-2">DELETE</span>
                                <h4 class="mb-0">/users/{id}</h4>
                            </div>
                            <p>Deletes a user account. <strong>(Not available with API key - session authentication only)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/users/123</code>
                            </div>
                        
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "message": "User deleted successfully"
}</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div id="endpoint-data" class="endpoint">
                        <h3 class="mb-3">Data Endpoints</h3>
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/data</h4>
                            </div>
                            <p>Returns a list of data entries for the authenticated user.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data</code>
                            </div>
                            
                            <h5>Query Parameters</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered param-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>page</code></td>
                                            <td>Integer</td>
                                            <td>No</td>
                                            <td>Page number (default: 1)</td>
                                        </tr>
                                        <tr>
                                            <td><code>limit</code></td>
                                            <td>Integer</td>
                                            <td>No</td>
                                            <td>Results per page (default: 20, max: 100)</td>
                                        </tr>
                                        <tr>
                                            <td><code>sort</code></td>
                                            <td>String</td>
                                            <td>No</td>
                                            <td>Field to sort by (default: dateCreated)</td>
                                        </tr>
                                        <tr>
                                            <td><code>order</code></td>
                                            <td>String</td>
                                            <td>No</td>
                                            <td>Sort order: asc or desc (default: desc)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "data": [
    {
      "userId": 1,
      "username": "sample user",
      "email": "sample@example.com",
      "role": "user",
      "dateCreated": "2023-06-01T10:00:00Z"
    },
    {
      "userId": 2,
      "username": "another user",
      "email": "another@example.com",
      "role": "user",
      "dateCreated": "2023-06-02T14:30:00Z"
    }
  ],
  "pagination": {
    "total": 50,
    "count": 2,
    "per_page": 2,
    "current_page": 1,
    "total_pages": 25,
    "links": {
      "next": "/api/data?page=2&limit=2",
      "prev": null
    }
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Returns a specific data entry.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data&id=42</code>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "userId": 42,
  "username": "specific user",
  "email": "specific@example.com",
  "role": "user",
  "dateCreated": "2023-06-05T09:15:00Z"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/data</h4>
                            </div>
                            <p>Creates a new user record. <strong>(Requires API key with POST permission)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data</code>
                            </div>

                            <h5>Headers</h5>
                            <div class="table-responsive">
                                <pre><code class="language-http">X-API-Key: YOUR_API_KEY</code></pre>
                            </div>
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "username": "new_user",
  "email": "new@example.com",
  "password": "secure_password"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Data created successfully",
  "data": {
    "userId": 51,
    "username": "new_user",
    "email": "new@example.com",
    "role": "user"
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-put me-2">PUT</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Updates a user record. <strong>(Requires API key with PUT permission)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>PUT https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data</code>
                            </div>

                            <h5>Headers</h5>
                            <div class="table-responsive">
                                <pre><code class="language-http">X-API-Key: YOUR_API_KEY</code></pre>
                            </div>
                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "id": 51,
  "username": "updated_username",
  "email": "updated@example.com", 
  "password": "new_password",
  "role": "user"
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Data updated successfully",
  "data": {
    "userId": 51,
    "username": "updated_username",
    "email": "updated@example.com",
    "role": "user"
  }
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-delete me-2">DELETE</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Deletes a data entry. <strong>(Requires API key with DELETE permission)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=data&id=51</code>
                            </div>
            
                            <h5>Headers</h5>
                            <div class="table-responsive">
                                <pre><code class="language-http">X-API-Key: YOUR_API_KEY</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "Data deleted successfully",
  "id": 51
}</code></pre>
                            </div>
                        </div>
                    </div>
                    
                    <div id="endpoint-api-keys" class="endpoint">
                        <h3 class="mb-3">API Keys Endpoints</h3>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/api-keys</h4>
                            </div>
                            <p>Returns a list of API keys for the authenticated user.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/api-keys</code>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "keys": [
    {
      "id": 1,
      "name": "Development Key",
      "masked_key": "abcdef12...34567890",
      "created_at": "2023-06-01T10:00:00Z",
      "expires_at": "2023-07-01T10:00:00Z",
      "last_used": "2023-06-10T15:30:00Z",
      "is_active": 1
    },
    {
      "id": 2,
      "name": "Production Key",
      "masked_key": "12345678...abcdef90",
      "created_at": "2023-06-05T14:00:00Z",
      "expires_at": "2023-07-05T14:00:00Z",
      "last_used": null,
      "is_active": 1
    }
  ]
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/api-keys</h4>
                            </div>
                            <p>Creates a new API key. <strong>(Not available with API key - session authentication only)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/api-keys</code>
                            </div>

                            
                            <h5>Request Body</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "name": "New API Key",
  "expires_in_days": 30
}</code></pre>
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "id": 3,
  "name": "New API Key",
  "api_key": "full_api_key_here_only_shown_once",
  "expires_at": "2023-07-15T09:12:34Z",
  "message": "API key created successfully"
}</code></pre>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> The full API key is only returned once upon creation. Store it securely.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-delete me-2">DELETE</span>
                                <h4 class="mb-0">/api-keys/{id}</h4>
                            </div>
                            <p>Revokes (deactivates) an API key. <strong>(Not available with API key - session authentication only)</strong></p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/api-keys/3</code>
                            </div>
             
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "message": "API key revoked successfully"
}</code></pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/verify</h4>
                            </div>
                            <p>Verifies that your API key is valid and returns information about your account.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/index.php?endpoint=verify</code>
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                Include your API key in the X-API-Key header for this request.
                            </div>
                            
                            <h5>Response</h5>
                            <div class="table-responsive">
                                <pre><code class="language-json">{
  "status": "success",
  "message": "API key is valid",
  "data": {
    "user_id": 123,
    "username": "username",
    "email": "user@example.com",
    "permissions": {
      "get": true,
      "post": false,
      "put": false,
      "delete": false
    }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </section>
                
                
                
                
                
                <div class="alert alert-secondary mt-5 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-question-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Need Help?</h5>
                            <p class="mb-0">If you have any questions or need assistance with our API, please contact our support team.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- Highlight.js for syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/http.min.js"></script>
    <script>
        // Initialize syntax highlighting
        document.addEventListener('DOMContentLoaded', () => {
            hljs.highlightAll();
            
            // Handle sidebar navigation
            const navLinks = document.querySelectorAll('.nav-pills .nav-link');
            
            function setActiveLink() {
                const scrollPosition = window.scrollY;
                
                document.querySelectorAll('section').forEach(section => {
                    const sectionTop = section.offsetTop - 100;
                    const sectionBottom = sectionTop + section.offsetHeight;
                    
                    if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                        const sectionId = section.getAttribute('id');
                        
                        navLinks.forEach(link => {
                            if (link.getAttribute('href') === `#${sectionId}`) {
                                link.classList.add('active');
                            } else {
                                link.classList.remove('active');
                            }
                        });
                    }
                });
            }
            
            // Set active link on scroll
            window.addEventListener('scroll', setActiveLink);
            
            // Set active link on page load
            setActiveLink();
            
            // Implementation of search functionality
            const searchInput = document.getElementById('searchDocs');
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                
                // If search term is empty, show all sections
                if (!searchTerm) {
                    document.querySelectorAll('section').forEach(section => {
                        section.style.display = 'block';
                    });
                    document.querySelectorAll('.endpoint').forEach(endpoint => {
                        endpoint.style.display = 'block';
                    });
                    return;
                }
                
                // Search in sections
                document.querySelectorAll('section').forEach(section => {
                    const sectionText = section.textContent.toLowerCase();
                    const hasMatch = sectionText.includes(searchTerm);
                    
                    section.style.display = hasMatch ? 'block' : 'none';
                });
                
                // Search in endpoints specifically
                document.querySelectorAll('.endpoint').forEach(endpoint => {
                    const endpointText = endpoint.textContent.toLowerCase();
                    const hasMatch = endpointText.includes(searchTerm);
                    
                    endpoint.style.display = hasMatch ? 'block' : 'none';
                });
            });
            
            // Mobile navigation toggle
            const toggleButton = document.getElementById('toggleSidebar');
            const sidebarContent = document.getElementById('sidebarContent');
            
            // Show sidebar content by default on larger screens
            if (window.innerWidth >= 992) {
                sidebarContent.classList.add('show');
            }
            
            toggleButton.addEventListener('click', () => {
                sidebarContent.classList.toggle('show');
                toggleButton.innerHTML = sidebarContent.classList.contains('show') 
                    ? '<i class="fas fa-times me-2"></i> Close Navigation' 
                    : '<i class="fas fa-bars me-2"></i> Navigation Menu';
            });
            
            // Ensure sidebar is visible when screen size changes to desktop
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 992) {
                    sidebarContent.classList.add('show');
                }
            });
            
            // Close sidebar when clicking on a link on mobile
            if (window.innerWidth < 992) {
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        sidebarContent.classList.remove('show');
                        toggleButton.innerHTML = '<i class="fas fa-bars me-2"></i> Navigation Menu';
                    });
                });
            }
        });
    </script>
</body>
</html>