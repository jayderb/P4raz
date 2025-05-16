<?php
// Initialize the session
session_start();

// Check if the user is logged in and has the correct access
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    ($_SESSION["role"] !== 'warehouse' || $_SESSION["email"] !== 'warehouse2@zedauto.com') && $_SESSION["role"] !== 'manager') {
    session_unset();
    session_destroy();
    header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
    exit;
}

// Include database connection
require_once '../db_connection.php';

$conn = connectionToDatabase();

// Hardcode warehouse_id for this dashboard
$warehouse_id = 2;

// Fetch inventory data for warehouse_id = 2
$stmt = $conn->prepare("SELECT part_id, part_name, quantity, unit_price 
                        FROM inventory 
                        WHERE warehouse_id = ?");
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();
$inventory = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending dispatch orders for warehouse_id = 2
$stmt = $conn->prepare("SELECT do.order_id, do.customer_id, do.part_id, i.part_name, do.quantity, do.status, do.order_date 
                        FROM dispatch_orders do 
                        LEFT JOIN inventory i ON do.part_id = i.part_id AND do.warehouse_id = i.warehouse_id 
                        WHERE do.warehouse_id = ? AND do.status = 'pending'");
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();
$dispatchOrders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent activity logs for warehouse_id = 2
$stmt = $conn->prepare("SELECT log_id, action, details, timestamp 
                        FROM activity_logs 
                        WHERE warehouse_id = ? 
                        ORDER BY timestamp DESC LIMIT 20");
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();
$activityLogs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Display success/error messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Warehouse 2 Dashboard</title>
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
            color: #2c2f33;
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

        /* Dashboard Sections */
        .dashboard-section {
            padding: 40px 0;
            background: #fff;
        }

        .dashboard-section h2 {
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #2c3e50;
            color: #ffffff;
            font-weight: 600;
        }

        tr:hover {
            background: #ecf0f1;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .dispatch-btn {
            background: #2ecc71;
            color: #fff;
        }

        .dispatch-btn:hover {
            background: #27ae60;
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

            .table-container {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }
        }

        @media (max-width: 576px) {
            .navbar .logo {
                font-size: 20px;
            }

            .navbar .hamburger {
                font-size: 20px;
            }

            .dashboard-section h2 {
                font-size: 24px;
            }

            .table-container {
                font-size: 12px;
            }

            th, td {
                padding: 6px;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 12px;
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
        <section class="dashboard-section">
            <div class="container">
                <h2>Welcome, Warehouse 2 Manager!</h2>

                <!-- Display Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Inventory -->
                <div class="table-container">
                    <h3 class="text-2xl font-semibold mb-4">Inventory (Warehouse 2)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Part ID</th>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Price (ZMW)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['part_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pending Dispatch Orders -->
                <div class="table-container">
                    <h3 class="text-2xl font-semibold mb-4">Pending Dispatch Orders (Warehouse 2)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer ID</th>
                                <th>Part ID</th>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispatchOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['part_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['part_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                    <td>
                                        <form action="../dispatch_order.php" method="post">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" class="action-btn dispatch-btn">Dispatch</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Activity Logs -->
                <div class="table-container">
                    <h3 class="text-2xl font-semibold mb-4">Recent Activity Logs (Warehouse 2)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activityLogs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                    <div class="cla social-links">
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