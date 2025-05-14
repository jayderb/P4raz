<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Redirect user based on role
    redirectBasedOnRole($_SESSION["role"]);
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["Password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["Password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, email, password, role, first_name, last_name FROM users WHERE email = :email";
        
        if($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()) {
                // Check if email exists, if yes then verify password
                if($stmt->rowCount() == 1) {
                    if($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $email = $row["email"];
                        $hashed_password = $row["password"];
                        $role = $row["role"];
                        $first_name = $row["first_name"];
                        $last_name = $row["last_name"];
                        
                        if(password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            $_SESSION["first_name"] = $first_name;
                            $_SESSION["last_name"] = $last_name;

                            //debugging
                            echo "Role: " . $role . "<br>";
                            echo "Redirecting to dashboard...<br>";
                            
                            // Redirect user based on role
                            redirectBasedOnRole($role);
                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
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
        case 'sales':
            $location = "sales/dashboard.php";
            break;
        default:
            $location = "index.php";
            break;
    }
    // Debugging
    echo "Attempting to redirect to: " . $location . "<br>";
    echo "Full URL: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/" . $location . "<br>";
    // Uncomment the next line to stop execution and inspect the output
    // exit;
    header("location: $location");
    exit;
}

// If there were errors, redirect back to login page with error message
if(!empty($email_err) || !empty($password_err) || !empty($login_err)) {
    $_SESSION['login_err'] = $login_err;
    $_SESSION['email_err'] = $email_err;
    $_SESSION['password_err'] = $password_err;
    header("location: RetailSystem-LocalGarage-Login.php");
    exit;
}
?>