<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes redirect to appropriate dashboard
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
            break;
    }
    exit;
}

// Check for error messages
$signup_err = isset($_SESSION['signup_err']) ? $_SESSION['signup_err'] : '';
$email_err = isset($_SESSION['email_err']) ? $_SESSION['email_err'] : '';
$password_err = isset($_SESSION['password_err']) ? $_SESSION['password_err'] : '';

// Clear session error variables
unset($_SESSION['signup_err']);
unset($_SESSION['email_err']);
unset($_SESSION['password_err']);
?>

<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Sign Up</title>
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
            min-height: 100vh;
            font-family: 'Poppins', 'Gill Sans', Calibri, 'Trebuchet MS', sans-serif;
            color: #2c3e50;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            min-height: 100vh;
        }

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

        .signup-container {
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

        .signup-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
        }

        .signup-container h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .signup-container p {
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

        .signup-container label {
            display: block;
            text-align: left;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .signup-container input[type="text"],
        .signup-container input[type="email"],
        .signup-container input[type="password"],
        .signup-container input[type="tel"] {
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

        .signup-container input[type="text"]:focus,
        .signup-container input[type="email"]:focus,
        .signup-container input[type="password"]:focus,
        .signup-container input[type="tel"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
            background-color: #fff;
        }

        .signup-container input[type="text"]::placeholder,
        .signup-container input[type="email"]::placeholder,
        .signup-container input[type="password"]::placeholder,
        .signup-container input[type="tel"]::placeholder {
            color: #7f8c8d;
            opacity: 0.7;
        }

        .signup-container .signup-btn {
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

        .signup-container .signup-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .signup-container .signup-btn:active {
            transform: translateY(0);
        }

        .signup-container center p {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 20px;
        }

        .signup-container center p a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-container center p a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .signup-container {
                padding: 20px;
                max-width: 90%;
            }

            .signup-container h1 {
                font-size: 24px;
            }

            .signup-container p {
                font-size: 16px;
            }

            .signup-container label {
                font-size: 14px;
            }

            .signup-container input[type="text"],
            .signup-container input[type="email"],
            .signup-container input[type="password"],
            .signup-container input[type="tel"] {
                padding: 8px;
                font-size: 13px;
            }

            .signup-container .signup-btn {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .signup-container {
                padding: 15px;
            }

            .signup-container h1 {
                font-size: 20px;
            }

            .signup-container p {
                font-size: 14px;
            }

            .error-message {
                font-size: 12px;
                padding: 8px;
            }

            .signup-container label {
                font-size: 13px;
            }

            .signup-container input[type="text"],
            .signup-container input[type="email"],
            .signup-container input[type="password"],
            .signup-container input[type="tel"] {
                padding: 7px;
                font-size: 12px;
            }

            .signup-container .signup-btn {
                padding: 8px;
                font-size: 13px;
            }

            .signup-container center p {
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

        <div class="signup-container">
            <form id="signupForm" action="signup_process.php" method="post">
                <h1>Sign Up</h1>
                <p>Create Your Account!</p>

                <?php if (!empty($signup_err)): ?>
                    <div class="error-message"><?php echo $signup_err; ?></div>
                <?php endif; ?>

                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required>
                <br><br>

                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required>
                <br><br>

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

                <label for="phone">Phone Number:</label>
                <input type="tel" name="phone" id="phone" placeholder="Enter your phone number">
                <br><br>

                <input type="submit" class="signup-btn" value="Sign Up">
            </form>
            <center><p>Already have an account? <a href="RetailSystem-LocalGarage-Login.php">Log in!</a></p></center>
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