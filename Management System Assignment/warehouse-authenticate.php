<?php
// Initialize the session
session_start();

// Include database connection
require_once 'db_connection.php';

// Initialize error variables
$email_err = $password_err = $login_err = "";

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['login_err'] = "Invalid CSRF token.";
        header("location: Retail System-Warehouse-Login.php");
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
        $conn = connectionToDatabase();
        
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, email, password, warehouse_id, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Check if user has appropriate role
            if (in_array($user['role'], ['warehouse', 'manager'])) {
                // Password is correct, start a new session
                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"] = $user['id']; // Changed from "id" to "user_id"
                $_SESSION["email"] = $user['email'];
                $_SESSION["role"] = $user['role'];
                $_SESSION["warehouse_id"] = $user['warehouse_id'];

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to warehouse dashboard
                header("location: warehouse/dashboard.php");
                $conn->close();
                exit;
            } else {
                $login_err = "You do not have permission to access the warehouse dashboard.";
            }
        } else {
            $login_err = "Invalid email or password.";
        }
        $conn->close();
    }

    // Store errors in session and redirect back to login page
    $_SESSION['email_err'] = $email_err;
    $_SESSION['password_err'] = $password_err;
    $_SESSION['login_err'] = $login_err;
    header("location: Retail System-Warehouse-Login.php");
    exit;
} else {
    // If not a POST request, redirect to login page
    header("location: Retail System-Warehouse-Login.php");
    exit;
}
?>