<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes redirect to appropriate dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    switch ($_SESSION["role"]) {
        case 'manager':
            header("location: admin/dashboard.php");
            break;
        case 'customer':
            header("location: customer/dashboard.php");
            break;
        case 'employee':
            header("location: sales/dashboard.php");
            break;
        case 'customs':
            header("location: customs/dashboard.php");
            break;
        default:
            break;
    }
    exit;
}

// Check for error messages
$login_err = isset($_SESSION['login_err']) ? $_SESSION['login_err'] : '';
$email_err = isset($_SESSION['email_err']) ? $_SESSION['email_err'] : '';
$password_err = isset($_SESSION['password_err']) ? $_SESSION['password_err'] : '';

// Clear session error variables
unset($_SESSION['login_err']);
unset($_SESSION['email_err']);
unset($_SESSION['password_err']);
?>

<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Login</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./Static/images/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', 'Gill Sans', Calibri, 'Trebuchet MS', sans-serif;
    color: #2c3e50;
    background: #ecf0f1;
    display: flex;
    flex-direction: column;
}

.container {
    max-width: 1200px;
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
    color: #ffffff; /* No hover effect in image */
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
    color: #2c2f33;
    border: none;
    border-radius: 0 4px 4px 0;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.navbar .search-button:hover {
    background: #e6b800; /* Slightly darker yellow for hover */
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
    color: #ffffff; /* No hover effect in image */
}

/* Video and Login Container (unchanged) */
.video-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.video-container video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.login-container {
    background: rgba(255, 255, 255, 0.9);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(236, 240, 241, 0.5);
    max-width: 400px;
    width: 100%;
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    backdrop-filter: blur(5px);
}

.login-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.login-container h1 {
    color: #2c3e50;
    font-size: 28px;
    margin-bottom: 10px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.login-container p {
    font-size: 18px;
    font-weight: bold;
    color: #7f8c8d;
    margin-bottom: 20px;
}

.error-message {
    background-color: #e74c3c;
    color: #fff;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
}

.login-container label {
    display: block;
    text-align: left;
    font-size: 16px;
    color: #2c3e50;
    margin-bottom: 5px;
    font-weight: 500;
}

.login-container input[type="email"],
.login-container input[type="password"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    color: #2c3e50;
    background-color: rgba(249, 249, 249, 0.8);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
}

.login-container input[type="email"]:focus,
.login-container input[type="password"]:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
    background-color: #fff;
}

.login-container input[type="email"]::placeholder,
.login-container input[type="password"]::placeholder {
    color: #7f8c8d;
    opacity: 0.7;
}

.login-container .login-btn {
    width: 100%;
    padding: 12px;
    background-color: #2ecc71;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 10px;
}

.login-container .login-btn:hover {
    background-color: #27ae60;
    transform: translateY(-2px);
}

.login-container .login-btn:active {
    transform: translateY(0);
}

.login-container center p {
    font-size: 14px;
    color: #7f8c8d;
    margin-top: 20px;
}

.login-container center p a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.login-container center p a:hover {
    color: #2980b9;
    text-decoration: underline;
}

/* Services Section (unchanged) */
.services-section {
    padding: 60px 0;
    background: #fff;
}

.services-section h2 {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 20px;
    color: #2c3e50;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.service-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.service-card i {
    font-size: 40px;
    color: #f1c40f;
    margin-bottom: 15px;
}

.service-card h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #2c3e50;
}

.service-card img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

.service-card p {
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 15px;
}

.service-card .read-more {
    color: #f1c40f;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.service-card .read-more:hover {
    color: #e1b12c;
    text-decoration: underline;
}

/* Logo Selector Section (unchanged) */
.logo-selector-section {
    padding: 60px 0;
    background: #ecf0f1;
}

.logo-selector-section h2 {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 15px;
    color: #2c3e50;
}

.logo-selector-section p {
    font-size: 16px;
    color: #7f8c8d;
    text-align: center;
    margin-bottom: 30px;
}

.logo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.logo-item {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    text-decoration: none;
    color: #2c3e50;
}

.logo-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.logo-item img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    margin-bottom: 10px;
}

.logo-item span {
    font-size: 16px;
    font-weight: 500;
}

.logo-item .dropdown {
    position: relative;
}

.logo-item .dropbtn {
    background: none;
    border: none;
    font-size: 16px;
    font-weight: 500;
    color: #2c3e50;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.logo-item .dropdown-content {
    display: none;
    position: absolute;
    background: #fff;
    min-width: 120px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    z-index: 1;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
}

.logo-item .dropdown-content a {
    display: block;
    padding: 10px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s ease;
}

.logo-item .dropdown-content a:hover {
    background: #f9f9f9;
}

.logo-item:hover .dropdown-content {
    display: block;
}

/* Management Section (unchanged) */
.management-section {
    padding: 60px 0;
    background: #fff;
}

.management-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.management-card {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.management-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.management-card h2 {
    font-size: 24px;
    font-weight: 700;
    color: #f1c40f;
    margin-bottom: 10px;
}

.management-card p {
    font-size: 14px;
    color: #7f8c8d;
    margin-bottom: 15px;
}

.management-card img {
    width: 100%;
    height: 192px;
    object-fit: cover;
    border-radius: 8px;
}

/* Footer (unchanged) */
footer {
    background: #2c3e50;
    color: #ecf0f1;
    padding: 40px 0;
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

/* Responsive Design (unchanged) */
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

    .login-container {
        padding: 20px;
        max-width: 90%;
    }

    .login-container h1 {
        font-size: 24px;
    }

    .login-container p {
        font-size: 16px;
    }

    .login-container label {
        font-size: 14px;
    }

    .login-container input[type="email"],
    .login-container input[type="password"] {
        padding: 8px;
        font-size: 13px;
    }

    .login-container .login-btn {
        padding: 10px;
        font-size: 14px;
    }
}

@media (max-width: 576px) {
    .navbar .logo {
        font-size: 20px;
    }

    .navbar .hamburger {
        font-size: 20px;
    }

    .login-container {
        padding: 15px;
    }

    .login-container h1 {
        font-size: 20px;
    }

    .login-container p {
        font-size: 14px;
    }

    .error-message {
        font-size: 12px;
        padding: 8px;
    }

    .login-container label {
        font-size: 13px;
    }

    .login-container input[type="email"],
    .login-container input[type="password"] {
        padding: 7px;
        font-size: 12px;
    }

    .login-container .login-btn {
        padding: 8px;
        font-size: 13px;
    }

    .login-container center p {
        font-size: 12px;
    }
}
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container">
            <a href="RetailSystem-Home.php" class="logo">ZedAuto</a>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="RetailSystem-Home.php">HOME</a></li>
                    <li><a href="#">MAINTENANCE</a></li>
                    <li><a href="#">AUTO REPAIR</a></li>
                    <li><a href="#">PRICE LIST</a></li>
                    <li><a href="#">REVIEWS</a></li>
                    <li><a href="RetailSystem-About.html">ABOUT</a></li>
                    <li><a href="RetailSystem-Contact.html">CONTACT</a></li>
                    <li>
                        <div class="search-container">
                            <input type="text" class="search" placeholder="Search...">
                            <button type="submit" class="search-button">SEARCH</button>
                        </div>
                    </li>
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
                    <a href="sell.html">Sell Car</a>
                    <a href="RetailSystem-LocalGarage-Login.php">Buy Car</a>
                    <a href="signup.php">Order Parts</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-content">
        <div class="video-container">
            <video autoplay muted loop id="video">
                <source src="./Static/Videos/2025 Toyota Land Cruiser - Toyota.com.mp4" type="video/mp4">
                Your browser does not support HTML5 video.
            </video>
        </div>

        <div class="login-container">
            <form id="loginForm" action="login_process.php" method="post">
                <h1>Login</h1>
                <p>Access Your ZedAuto Account</p>

                <?php if (!empty($login_err)): ?>
                    <div class="error-message"><?php echo $login_err; ?></div>
                <?php endif; ?>

                <?php if (!empty($email_err)): ?>
                    <div class="error-message"><?php echo $email_err; ?></div>
                <?php endif; ?>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
                <br><br>

                <?php if (!empty($password_err)): ?>
                    <div class="error-message"><?php echo $password_err; ?></div>
                <?php endif; ?>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
                <br><br>

                <input type="submit" class="login-btn" value="Login">
            </form>
            <center><p>Don't have an account? <a href="RetailSystem-SignUp.php">Sign up!</a></p></center>
        </div>
    </div>

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
                        <li><a href="RetailSystem-Home.php">Home</a></li>
                        <li><a href="RetailSystem-About.html">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="RetailSystem-Contact.html">Contact</a></li>
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
                © 2025 ZedAuto. All rights reserved.
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
    </script>
</body>
</html>