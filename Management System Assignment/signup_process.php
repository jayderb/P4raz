<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Redirect user based on role
    redirectBasedOnRole($_SESSION["role"]);
    exit;
}

// Include config file with error handling
try {
    require_once "config.php";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define variables and initialize with empty values
$first_name = $last_name = $email = $password = $phone = "";
$signup_err = $email_err = $password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate first name
    if (empty(trim($_POST["first_name"]))) {
        $signup_err = "Please enter your first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // Validate last name
    if (empty(trim($_POST["last_name"]))) {
        $signup_err = "Please enter your last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $email = strtolower(trim($_POST["email"]));
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $email_err = "This email is already taken.";
                }
            } else {
                $signup_err = "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must be at least 6 characters long.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate phone (optional field)
    $phone = !empty(trim($_POST["phone"])) ? trim($_POST["phone"]) : null;

    // If there are no errors, proceed to insert the user into the database
    if (empty($signup_err) && empty($email_err) && empty($password_err)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare an insert statement
        $sql = "INSERT INTO users (email, password, first_name, last_name, role, phone, created_at) 
                VALUES (:email, :password, :first_name, :last_name, :role, :phone, NOW())";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
            $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
            $stmt->bindParam(":role", $param_role, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);

            // Set role to 'customer' by default
            $param_role = "customer";

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to login page after successful sign-up
                header("location: RetailSystem-LocalGarage-Login.php");
                exit;
            } else {
                $signup_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }

    // Close connection
    unset($conn);
}

// If there were errors, redirect back to sign-up page with error messages
if (!empty($signup_err) || !empty($email_err) || !empty($password_err)) {
    $_SESSION['signup_err'] = $signup_err;
    $_SESSION['email_err'] = $email_err;
    $_SESSION['password_err'] = $password_err;
    header("location: RetailSystem-SignUp.php");
    exit;
}

// Function to redirect based on role (same as in login_process.php)
function redirectBasedOnRole($role) {
    switch ($role) {
        case 'admin':
            header("location: admin/dashboard.php");
            break;
        case 'customer':
            header("location: customer/dashboard.php");
            break;
        case 'employee':
            header("location: sales/dashboard.php");
            break;
        default:
            header("location: index.php");
            break;
    }
    exit;
}
?>