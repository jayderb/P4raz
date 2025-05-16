<?php
// Initialize the session
session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['customer'])) {
    session_unset();
    session_destroy();
    header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
    exit;
}

// Include database connection
require_once '../db_connection.php';

$conn = connectionToDatabase();

// Initialize variables
$first_name = $last_name = $phone = '';
$first_name_err = $last_name_err = $phone_err = $password_err = $confirm_password_err = '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Fetch current user details
$stmt = $conn->prepare("SELECT first_name, last_name, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$first_name = $user['first_name'] ?: '';
$last_name = $user['last_name'] ?: '';
$phone = $user['phone'] ?: '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate first name
    $first_name = trim($_POST['first_name']);
    if (empty($first_name)) {
        $first_name_err = "Please enter your first name.";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $first_name)) {
        $first_name_err = "First name can only contain letters and spaces.";
    }

    // Validate last name
    $last_name = trim($_POST['last_name']);
    if (empty($last_name)) {
        $last_name_err = "Please enter your last name.";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $last_name)) {
        $last_name_err = "Last name can only contain letters and spaces.";
    }

    // Validate phone
    $phone = trim($_POST['phone']);
    if (empty($phone)) {
        $phone_err = "Please enter your phone number.";
    } elseif (!preg_match("/^[0-9]{9,12}$/", $phone)) {
        $phone_err = "Phone number must be 9-12 digits.";
    }

    // Validate password
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $password_err = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm_password) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // If no errors, update user
    if (empty($first_name_err) && empty($last_name_err) && empty($phone_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?";
        $params = [$first_name, $last_name, $phone];
        $types = "sss";

        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_BCRYPT);
            $types .= "s";
        }

        $sql .= " WHERE id = ?";
        $params[] = $_SESSION['id'];
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (warehouse_id, action, details) VALUES (?, 'Profile Updated', ?)");
            $details = "User {$_SESSION['email']} updated profile (first_name: $first_name, last_name: $last_name, phone: $phone" . (!empty($password) ? ", password changed" : "") . ")";
            $stmt->bind_param("is", $_SESSION['warehouse_id'], $details);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] = "Profile updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
        $stmt->close();
        $conn->close();
        header("location: settings.php");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Warehouse Settings</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Static/images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Gill Sans', Calibri, 'Trebuchet MS', sans-serif;
            color: #2c3e50;
            background: #ecf0f1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navbar */
        .navbar {
            background: #2c2f33;
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background 0.3s ease;
        }

        .navbar.navbar-sticky {
            background: #23272a;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }

        .navbar .logo {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .navbar .hamburger {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: #ffffff;
        }

        .navbar .main-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .navbar .main-nav ul li {
            margin-left: 20px;
        }

        .navbar .main-nav ul li a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar .main-nav ul li a:hover {
            color: #f1c40f;
        }

        .navbar .search-container {
            display: flex;
            align-items: center;
        }

        .navbar .search {
            padding: 8px;
            border: none;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
            background: #40444b;
            color: #ffffff;
            outline: none;
        }

        .navbar .search-button {
            padding: 8px 15px;
            background: #ffcc00;
            color: #2c3e50;
            border: none;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .navbar .search-button:hover {
            background: #e6b800;
        }

        /* Sub-Header */
        .sub-header {
            background: #40444b;
            color: #ffffff;
            font-size: 14px;
            padding: 10px 0;
        }

        .sub-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sub-header .contact-info {
            display: flex;
            gap: 20px;
        }

        .sub-header .contact-info div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .sub-header .contact-info a {
            color: #ffffff;
            text-decoration: none;
        }

        .sub-header .quick-links {
            display: flex;
            gap: 15px;
        }

        .sub-header .quick-links a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .sub-header .quick-links a:hover {
            color: #f1c40f;
        }

        /* Settings Section */
        .settings-section {
            padding: 40px 0;
            background: #fff;
        }

        .settings-section h2 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            background: #2c3e50;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .form-container button:hover {
            background: #34495e;
        }

        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background: #2ecc71;
            color: #fff;
        }

        .alert-error {
            background: #e74c3c;
            color: #fff;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 40px 0;
            margin-top: auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        .footer-grid h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .footer-grid p {
            font-size: 14px;
            color: #7f8c8d;
        }

        .footer-grid ul {
            list-style: none;
            padding: 0;
        }

        .footer-grid ul li {
            margin-bottom: 10px;
        }

        .footer-grid ul li a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-grid ul li a:hover {
            color: #f1c40f;
        }

        .footer-grid .social-links {
            display: flex;
            gap: 15px;
        }

        .footer-grid .social-links a {
            color: #ecf0f1;
            font-size: 20px;
            transition: color 0.3s ease;
        }

        .footer-grid .social-links a:hover {
            color: #f1c40f;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar .hamburger {
                display: block;
            }

            .navbar .main-nav ul {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background: #2c2f33;
                padding: 20px;
            }

            .navbar .main-nav ul.active {
                display: flex;
            }

            .navbar .main-nav ul li {
                margin: 10px 0;
            }

            .sub-header .container {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .sub-header .contact-info {
                flex-direction: column;
                gap: 10px;
            }

            .form-container {
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            .navbar .logo {
                font-size: 20px;
            }

            .navbar .hamburger {
                font-size: 20px;
            }

            .settings-section h2 {
                font-size: 24px;
            }

            .form-container input {
                font-size: 14px;
            }

            .form-container button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <a href="../RetailSytsem-Home.html" class="logo">ZedAuto</a>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="../RetailSytsem-Home.html">HOME</a></li>
                    <li><a href="../maintenance.php">MAINTENANCE</a></li>
                    <li><a href="../auto-repair.php">AUTO REPAIR</a></li>
                    <li><a href="#">PRICE LIST</a></li>
                    <li><a href="#">REVIEWS</a></li>
                    <li><a href="../about.php">ABOUT</a></li>
                    <li><a href="../contact.php">CONTACT</a></li>
                    <li><a href="dashboard.php">DASHBOARD</a></li>
                    <li><a href="profile.php">PROFILE</a></li>
                    <li><a href="settings.php" class="text-yellow-400 hover:text-yellow-500">SETTINGS</a></li>
                    <li>
                        <div class="search-container">
                            <input type="text" class="search" placeholder="Search...">
                            <button type="submit" class="search-button">SEARCH</button>
                        </div>
                    </li>
                    <li><a href="../logout.php" class="text-red-400 hover:text-red-500">LOGOUT</a></li>
                </ul>
            </nav>
        </div>
        <div class="sub-header">
            <div class="container">
                <div class="contact-info">
                    <div><i class="fas fa-map-marker-alt"></i> Lusaka, Zambia</div>
                    <div><i class="fas fa-phone"></i> + (260) 987654321</div>
                    <div><i class="fas fa-envelope"></i> <a href="mailto:info@zedauto.com">info@zedauto.com</a></div>
                </div>
                <div class="quick-links">
                    <a href="../sell.html">Sell Car</a>
                    <a href="../RetailSystem-LocalGarage-Login.php">Buy Car</a>
                    <a href="../RetailSystem-Signup.php">Order Parts</a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">
        <section class="settings-section">
            <div class="container">
                <h2>Account Settings</h2>

                <!-- Display Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div>
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                            <span class="error"><?php echo $first_name_err; ?></span>
                        </div>
                        <div>
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                            <span class="error"><?php echo $last_name_err; ?></span>
                        </div>
                        <div>
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            <span class="error"><?php echo $phone_err; ?></span>
                        </div>
                        <div>
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" id="password" name="password">
                            <span class="error"><?php echo $password_err; ?></span>
                        </div>
                        <div>
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                            <span class="error"><?php echo $confirm_password_err; ?></span>
                        </div>
                        <button type="submit">Update Settings</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>ZedAuto</h3>
                    <p>Providing top-notch automotive services in Lusaka, Zambia.</p>
                </div>
                <div>
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../RetailSytsem-Home.html">Home</a></li>
                        <li><a href="../about.php">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="../contact.php">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span id="copyright">© 2025 ZedAuto. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <script>
        // Sticky Navbar
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-sticky');
            } else {
                navbar.classList.remove('navbar-sticky');
            }
        });

        // Hamburger Menu Toggle
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.main-nav ul');
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Close menu when a nav link is clicked
        const navLinks = document.querySelectorAll('.main-nav ul li a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        });

        // Update Copyright Year
        function updateCopyright() {
            const copyrightElement = document.getElementById('copyright');
            const currentYear = new Date().getFullYear();
            const baseYear = 2025;
            if (currentYear > baseYear) {
                copyrightElement.textContent = `© ${baseYear}-${currentYear} ZedAuto. All rights reserved.`;
            }
        }
        updateCopyright();
    </script>
</body>
</html>