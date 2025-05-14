<?php
// Initialize the session
session_start();

// Database connection (update with your credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zedauto_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['login_err'] = "Database connection failed. Please try again later.";
    header("location: warehouse-login.php");
    exit;
}

// Initialize error variables
$email_err = $password_err = $login_err = "";

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['login_err'] = "Invalid CSRF token.";
        header("location: Retail System-Warehouse-login.php");
        exit;
    }

    // Validate email
    $email = trim($_POST["email"]);
    if (empty($email)) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    }

    // Validate password
    $password = trim($_POST["password"]);
    if (empty($password)) {
        $password_err = "Please enter your password.";
    }

    // Proceed if no validation errors
    if (empty($email_err) && empty($password_err)) {
        try {
            $stmt = $conn->prepare("SELECT id, email, password, warehouse_id FROM warehouse_users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $user['id'];
                $_SESSION["email"] = $user['email'];
                $_SESSION["role"] = 'warehouse';
                $_SESSION["warehouse_id"] = $user['warehouse_id'];

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to warehouse dashboard
                header("location: warehouse/dashboard.php");
                exit;
            } else {
                $login_err = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $login_err = "An error occurred. Please try again later.";
        }
    }

    // Store errors in session and redirect back to login page
    $_SESSION['email_err'] = $email_err;
    $_SESSION['password_err'] = $password_err;
    $_SESSION['login_err'] = $login_err;
    header("location: Retail System-Warehouse-login.php");
    exit;
} else {
    // If not a POST request, redirect to login page
    header("location: warehouse-login.php");
    exit;
}
?>