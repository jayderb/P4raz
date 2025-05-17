<?php
// Initialize the session
session_start();

// Check if the user is logged in and has the admin role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'warehouse') {
    session_unset();
    session_destroy();
    header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
    exit;
}

// Include database connection
require_once '../db_connection.php';

$conn = connectionToDatabase();

// Fetch all inventory data
$stmt = $conn->prepare("SELECT i.part_id, i.part_name, i.quantity, i.unit_price, i.warehouse_id, u.email AS warehouse_email 
                        FROM inventory i 
                        LEFT JOIN users u ON i.warehouse_id = u.warehouse_id 
                        WHERE u.role = 'warehouse' OR u.role IS NULL");
$stmt->execute();
$result = $stmt->get_result();
$inventory = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch inventory parts for dropdown
$stmt = $conn->prepare("SELECT part_id, part_name, warehouse_id FROM inventory");
$stmt->execute();
$result = $stmt->get_result();
$inventory_parts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all pending dispatch orders
$stmt = $conn->prepare("SELECT do.order_id, do.customer_id, do.part_id, do.quantity, do.status, do.order_date, do.warehouse_id, u.email AS warehouse_email 
                        FROM dispatch_orders do 
                        LEFT JOIN users u ON do.warehouse_id = u.warehouse_id 
                        WHERE do.status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$dispatchOrders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent activity logs
$stmt = $conn->prepare("SELECT al.log_id, al.action, al.details, al.timestamp, al.warehouse_id, u.email AS warehouse_email 
                        FROM activity_logs al 
                        LEFT JOIN users u ON al.warehouse_id = u.warehouse_id 
                        ORDER BY al.timestamp DESC LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
$activityLogs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch warehouse users (for warehouse_id selection)
$stmt = $conn->prepare("SELECT warehouse_id, email FROM users WHERE role = 'warehouse'");
$stmt->execute();
$result = $stmt->get_result();
$warehouseUsers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch customers (for customer_id selection)
$stmt = $conn->prepare("SELECT id, email FROM users WHERE role = 'customer'");
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>ZedAuto - Admin Warehouse Management</title>
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

        .form-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .form-container input, .form-container select {
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
        }

        .form-container button:hover {
            background: #34495e;
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
    <header class="navbar sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="../RetailSytsem-Home.html" class="text-2xl font-bold text-white">ZedAuto</a>
            <button id="hamburger" class="md:hidden text-white text-2xl focus:outline-none" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav id="nav-menu" class="hidden md:flex items-center space-x-6">
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex items-center">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c2f33] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </nav>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-[#2c2f33]">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-4">
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none w-full" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c2f33] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <nav id="topbar" class="topbar">
        <div class="container mx-auto px-4">
            <h2 class="sr-only">Manager Navigation</h2>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Users</a>
                <a href="customs.php">Customs</a>
                <a href="orders.php">Orders</a>
                <a href="inventory.php">Inventory</a>
                <a href="warehouse.php">Warehouse</a>
            </nav>
        </div>
    </nav>

    <!-- Mobile Top Bar Menu -->
    <div id="topbar-mobile" class="md:hidden">
        <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
            <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
            <a href="manage_users.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Users</a>
            <a href="customs.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Customs</a>
            <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Orders</a>
            <a href="inventory.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Inventory</a>
            <a href="warehouse.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Warehouse</a>
        </div>
    </div>

    </header>

    <main class="flex-1">
        <section class="dashboard-section">
            <div class="container">
                <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION['email']); ?>!</h2>

                <!-- Display Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Add New Stock Form -->
                <div class="form-container">
                    <h3 class="text-2xl font-semibold mb-4">Add New Stock</h3>
                    <form action="add_stock_order.php" method="post">
                        <input type="hidden" name="action" value="add_stock">
                        <input type="text" name="part_name" placeholder="Part Name" required>
                        <input type="number" name="quantity" placeholder="Quantity" min="1" required>
                        <input type="number" name="unit_price" placeholder="Unit Price (ZMW)" min="0" step="0.01" required>
                        <select name="warehouse_id" required>
                            <option value="">Select Warehouse</option>
                            <?php foreach ($warehouseUsers as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['warehouse_id']); ?>">
                                    <?php echo htmlspecialchars($user['warehouse_id'] . ' - ' . $user['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Add Stock</button>
                    </form>
                </div>

                <!-- Add New Order Form -->
                

                <!-- Inventory -->
                <div class="table-container">
                    <h3 class="text-2xl font-semibold mb-4">All Inventory</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Part ID</th>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Price (ZMW)</th>
                                <th>Warehouse ID</th>
                                <th>Warehouse Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['part_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['warehouse_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['warehouse_email'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pending Dispatch Orders -->
                <div class="table-container">
                    <h3 class="text-2xl font-semibold mb-4">Pending Dispatch Orders</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer ID</th>
                                <th>Part ID</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Warehouse ID</th>
                                <th>Warehouse Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dispatchOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['part_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                    <td><?php echo htmlspecialchars($order['warehouse_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['warehouse_email'] ?: 'N/A'); ?></td>
                                    <td>
                                        <form action="dispatch_order.php" method="post">
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
                    <h3 class="text-2xl font-semibold mb-4">Recent Activity Logs</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                                <th>Warehouse ID</th>
                                <th>Warehouse Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activityLogs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars($log['warehouse_id']); ?></td>
                                    <td><?php echo htmlspecialchars($log['warehouse_email'] ?: 'N/A'); ?></td>
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