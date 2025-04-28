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
        
        @media (max-width: 991.98px) {
            .sidebar {
                margin-bottom: 2rem;
            }
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
    ?>

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
            <!-- Sidebar Navigation -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <nav id="navbar-docs" class="navbar">
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
                            <li class="nav-item">
                                <a class="nav-link" href="#section-webhooks">Webhooks</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#section-changelog">Changelog</a>
                            </li>
                        </ul>
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
                        <li>Manage user accounts</li>
                        <li>Create and manipulate data entries</li>
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
                            <p>To authenticate API requests, include your API key in the request header:</p>
                            <pre><code class="language-bash">curl -X GET \
  'https://cei326-omada1.cut.ac.cy/api/data' \
  -H 'X-API-Key: YOUR_API_KEY_HERE'</code></pre>

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
                            <p>For web applications, you can use session-based authentication by first logging in via the login endpoint:</p>
                            <pre><code class="language-bash">curl -X POST \
  'https://cei326-omada1.cut.ac.cy/api/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "user@example.com", "password": "your_password"}'</code></pre>

                            <p>The response will include session cookies that will be automatically used for authentication in subsequent requests when using a browser or tools that preserve cookies.</p>
                        </div>
                    </div>
                </section>

                <section id="section-errors">
                    <h2 class="section-title">Error Handling</h2>
                    <p>Our API uses conventional HTTP response codes to indicate the success or failure of API requests.</p>
                    
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
                                <td><code>403 - Forbidden</code></td>
                                <td>The request is valid, but the server is refusing action.</td>
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
                    
                    <h5 class="mt-4">Error Response Format</h5>
                    <p>Error responses will include a JSON object with the following structure:</p>
                    <pre><code class="language-json">{
  "error": "A human-readable error message"
}</code></pre>
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
                                <code>POST https://cei326-omada1.cut.ac.cy/api/auth/login</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "your_password"
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "status": "success",
  "message": "Login successful",
  "user": {
    "id": 123,
    "email": "user@example.com",
    "role": "user"
  }
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/auth/register</h4>
                            </div>
                            <p>Creates a new user account.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/auth/register</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "username": "newuser",
  "email": "newuser@example.com",
  "password": "secure_password"
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "status": "success",
  "message": "Registration successful",
  "user": {
    "id": 124,
    "username": "newuser",
    "email": "newuser@example.com"
  }
}</code></pre>
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
                            <pre><code class="language-json">{
  "status": "success",
  "message": "Logout successful"
}</code></pre>
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
                            <pre><code class="language-json">{
  "email": "user@example.com"
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "status": "success",
  "message": "If your email is registered, you will receive password reset instructions"
}</code></pre>
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
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "created_at": "2023-01-01T00:00:00Z",
      "role": "admin"
    },
    {
      "id": 2,
      "username": "user",
      "email": "user@example.com",
      "created_at": "2023-01-02T00:00:00Z",
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
                            <pre><code class="language-json">{
  "id": 123,
  "username": "username",
  "email": "user@example.com",
  "created_at": "2023-06-01T10:00:00Z",
  "role": "user"
}</code></pre>
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
                            <pre><code class="language-json">{
  "id": 123,
  "username": "username",
  "email": "user@example.com",
  "created_at": "2023-06-01T10:00:00Z",
  "role": "user"
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-put me-2">PUT</span>
                                <h4 class="mb-0">/users/{id}</h4>
                            </div>
                            <p>Updates a user's information.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>PUT https://cei326-omada1.cut.ac.cy/api/users/123</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "username": "updated_username",
  "email": "updated_email@example.com"
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "User updated successfully"
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-delete me-2">DELETE</span>
                                <h4 class="mb-0">/users/{id}</h4>
                            </div>
                            <p>Deletes a user account.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/users/123</code>
                            </div>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "User deleted successfully"
}</code></pre>
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
                                <code>GET https://cei326-omada1.cut.ac.cy/api/data</code>
                            </div>
                            
                            <h5>Query Parameters</h5>
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
                                        <td>Field to sort by (default: created_at)</td>
                                    </tr>
                                    <tr>
                                        <td><code>order</code></td>
                                        <td>String</td>
                                        <td>No</td>
                                        <td>Sort order: asc or desc (default: desc)</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "data": [
    {
      "id": 1,
      "title": "Sample data entry",
      "description": "This is a sample entry",
      "status": "active",
      "is_public": 1,
      "tags": ["sample", "test"],
      "created_at": "2023-06-01T10:00:00Z",
      "updated_at": "2023-06-01T10:00:00Z"
    },
    {
      "id": 2,
      "title": "Another data entry",
      "description": "This is another sample entry",
      "status": "inactive",
      "is_public": 0,
      "tags": ["sample"],
      "created_at": "2023-06-02T14:30:00Z",
      "updated_at": "2023-06-02T14:30:00Z"
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
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Returns a specific data entry.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/data/42</code>
                            </div>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "id": 42,
  "title": "Specific data entry",
  "description": "This is a specific data entry",
  "status": "active",
  "is_public": 1,
  "tags": ["important", "featured"],
  "created_at": "2023-06-05T09:15:00Z",
  "updated_at": "2023-06-05T09:15:00Z"
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/data</h4>
                            </div>
                            <p>Creates a new data entry.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/data</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "title": "New data entry",
  "description": "This is a new data entry",
  "status": "active",
  "is_public": 1,
  "tags": ["new", "important"]
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "Data entry created successfully",
  "data": {
    "id": 51,
    "title": "New data entry",
    "description": "This is a new data entry",
    "status": "active",
    "is_public": 1,
    "tags": ["new", "important"],
    "created_at": "2023-06-15T09:12:34Z",
    "updated_at": "2023-06-15T09:12:34Z"
  }
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-put me-2">PUT</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Updates a data entry.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>PUT https://cei326-omada1.cut.ac.cy/api/data/51</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "title": "Updated title",
  "status": "inactive"
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "Data entry updated successfully",
  "data": {
    "id": 51,
    "title": "Updated title",
    "description": "This is a new data entry",
    "status": "inactive",
    "is_public": 1,
    "tags": ["new", "important"],
    "created_at": "2023-06-15T09:12:34Z",
    "updated_at": "2023-06-15T10:45:22Z"
  }
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-delete me-2">DELETE</span>
                                <h4 class="mb-0">/data/{id}</h4>
                            </div>
                            <p>Deletes a data entry.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/data/51</code>
                            </div>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "Data entry deleted successfully"
}</code></pre>
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
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-post me-2">POST</span>
                                <h4 class="mb-0">/api-keys</h4>
                            </div>
                            <p>Creates a new API key.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>POST https://cei326-omada1.cut.ac.cy/api/api-keys</code>
                            </div>
                            
                            <h5>Request Body</h5>
                            <pre><code class="language-json">{
  "name": "New API Key",
  "expires_in_days": 30
}</code></pre>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "id": 3,
  "name": "New API Key",
  "api_key": "full_api_key_here_only_shown_once",
  "expires_at": "2023-07-15T09:12:34Z",
  "message": "API key created successfully"
}</code></pre>
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
                            <p>Revokes (deactivates) an API key.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>DELETE https://cei326-omada1.cut.ac.cy/api/api-keys/3</code>
                            </div>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "message": "API key revoked successfully"
}</code></pre>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge method-badge badge-get me-2">GET</span>
                                <h4 class="mb-0">/verify</h4>
                            </div>
                            <p>Verifies that your API key is valid and returns information about your account.</p>
                            
                            <div class="endpoint-url mb-3">
                                <code>GET https://cei326-omada1.cut.ac.cy/api/verify</code>
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                Include your API key in the X-API-Key header for this request.
                            </div>
                            
                            <h5>Response</h5>
                            <pre><code class="language-json">{
  "status": "success",
  "message": "API key is valid",
  "data": {
    "user_id": 123,
    "username": "username",
    "role": "user",
    "key_name": "Development Key",
    "expires_at": "2023-07-01T10:00:00Z"
  }
}</code></pre>
                        </div>
                    </div>
                </section>
                
                <section id="section-webhooks">
                    <h2 class="section-title">Webhooks</h2>
                    <p>Webhooks allow you to receive real-time notifications when specific events occur in your account. Coming soon.</p>
                    
                    <div class="alert alert-secondary">
                        <i class="fas fa-info-circle me-2"></i>
                        Webhook functionality is currently in development and will be available in a future update.
                    </div>
                </section>
                
                <section id="section-changelog">
                    <h2 class="section-title">Changelog</h2>
                    <div class="timeline">
                        <div class="mb-4">
                            <h5><span class="badge bg-primary me-2">v1.0.0</span> June 2023</h5>
                            <ul>
                                <li>Initial API release</li>
                                <li>Authentication endpoints</li>
                                <li>User management</li>
                                <li>Data management</li>
                                <li>API key generation and management</li>
                            </ul>
                        </div>
                    </div>
                </section>
                
                <div class="alert alert-secondary mt-5 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-question-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Need Help?</h5>
                            <p class="mb-0">If you have any questions or need assistance with our API, please <a href="#">contact our support team</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">© 2023 WebEngineering. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-decoration-none me-3">Terms of Service</a>
                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>

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
        });
    </script>
</body>
</html>