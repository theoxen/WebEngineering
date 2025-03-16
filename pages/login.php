<?php
session_start();
require_once '../database/db_connect.php'; // include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and trim input values
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if fields are not empty
    if (!empty($email) && !empty($password)) {
        // Prepare a statement to avoid SQL injection
        $stmt = $mysqli->prepare("SELECT userId, email, password FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            // Check if a user exists with that email
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($userId, $userEmail, $hashedPassword);
                $stmt->fetch();
                
                // Verify the password against the hashed password in the database
                // if (password_verify($password, $hashedPassword)) {
                if ($password == $hashedPassword) {
                    // Successful login: set session variables
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['email'] = $userEmail;
                    
                    // Redirect to a protected page (change to your destination)
                    echo 'SUCCESS';
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $error = "Database query error.";
        }
    } else {
        $error = "Please fill in both fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Login</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        if (isset($error)) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
                        }
                        ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <small>Don't have an account? <a href="register.php">Register here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>