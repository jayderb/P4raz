<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["role"])) {
    redirectBasedOnRole($_SESSION["role"]);
    exit;
} else if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    session_unset();
    session_destroy();
    header("location: RetailSystem-LocalGarage-Login.php?error=invalid_role");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $login_err = "Invalid CSRF token.";
        $_SESSION['login_err'] = $login_err;
        header("location: RetailSystem-LocalGarage-Login.php");
        exit;
    }
    unset($_SESSION['csrf_token']);

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, email, password, role, first_name, last_name FROM users WHERE email = :email";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if email exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $hashed_password = $row["password"];
                        $role = $row["role"];
                        $first_name = $row["first_name"];
                        $last_name = $row["last_name"];
                        
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, regenerate session ID
                            session_regenerate_id(true);
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            $_SESSION["first_name"] = $first_name;
                            $_SESSION["last_name"] = $last_name;
                            
                            // Redirect user based on role
                            redirectBasedOnRole($role);
                        } else {
                            $login_err = "Incorrect password.";
                        }
                    }
                } else {
                    $login_err = "No account found with that email.";
                }
            } else {
                $login_err = "Database error. Please try again later.";
            }
            unset($stmt);
        } else {
            $login_err = "Database preparation error. Please try again later.";
        }
    }
    
    // Store errors and redirect
    if (!empty($email_err) || !empty($password_err) || !empty($login_err)) {
        $_SESSION['login_err'] = $login_err;
        $_SESSION['email_err'] = $email_err;
        $_SESSION['password_err'] = $password_err;
        header("location: RetailSystem-LocalGarage-Login.php");
        exit;
    }
    
    // Close connection
    unset($conn);
}

// Function to redirect based on role
function redirectBasedOnRole($role) {
    $location = "";
    switch ($role) {
        case 'manager':
            $location = "admin/dashboard.php";
            break;
        case 'customer':
            $location = "customer/dashboard.php";
            break;
        case 'customs':
            $location = "customs/dashboard.php";
            break;
        case 'employee':
            $location = "sales/dashboard.php";
            break;
        default:
            $location = "RetailSystem-LocalGarage-Login.php?error=invalid_role";
            break;
    }
    header("location: $location");
    exit;
}
?>