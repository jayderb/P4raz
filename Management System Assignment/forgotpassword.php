<?php
// Initialize the session
session_start();

// Check if the user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    switch ($_SESSION["role"]) {
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

// Include config file
try {
    require_once "config.php";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define variables and initialize with empty values
$email = "";
$email_err = $success_msg = "";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = strtolower(trim($_POST["email"]));

        // Check if email exists in the database
        $sql = "SELECT id FROM users WHERE email = :email";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    // Email exists, generate a reset token (placeholder)
                    $reset_token = bin2hex(random_bytes(32)); // Generate a random token
                    // In a real application, store this token in the database with an expiration time
                    // For now, we'll simulate the process

                    // Simulate sending an email (replace this with actual email-sending logic)
                    $reset_link = "http://localhost/project/resetpassword.php?token=" . $reset_token;
                    // Example: mail($email, "Password Reset", "Click here to reset your password: " . $reset_link);

                    // Store success message in session and redirect to login page
                    $_SESSION['success_msg'] = "A password reset link has been sent to your email.";
                    header("location: RetailSystem-LocalGarage-Login.php");
                    exit;
                } else {
                    $email_err = "No account found with that email address.";
                }
            } else {
                $email_err = "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }

    // Close connection
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ZedAuto</title>
    <link rel="icon" type="image/ico" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            color: red;
            margin-bottom: 10px;
            text-align: center;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .container h1 {
            text-align: center;
            color: #333;
        }
        .container p {
            text-align: center;
            color: #666;
        }
        .container input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .container input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .container input[type="submit"]:hover {
            background-color: #45a049;
        }
        .container a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #007BFF;
            text-decoration: none;
        }
        .container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <form action="forgotpassword.php" method="post">
            <h1>Forgot Password</h1>
            <p>Enter your email to receive a password reset link.</p>

            <?php if (!empty($email_err)): ?>
                <div class="error-message"><?php echo $email_err; ?></div>
            <?php endif; ?>

            <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
            <input type="submit" value="Send Reset Link">
        </form>
        <a href="RetailSystem-LocalGarage-Login.php">Back to Login</a>
    </div>
</body>
</html>