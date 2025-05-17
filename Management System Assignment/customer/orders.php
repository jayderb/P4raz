<?php
// Start the session
session_start();

// Debug session
error_log("Attempting to access orders.php: " . $_SERVER['REQUEST_URI']);
error_log("Session in orders.php: loggedin=" . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "unset") . 
          ", role=" . (isset($_SESSION["role"]) ? $_SESSION["role"] : "unset") . 
          ", user_id=" . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "unset") . 
          ", id=" . (isset($_SESSION["id"]) ? $_SESSION["id"] : "unset"));

// Check if the user is logged in and has the customer role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== "customer" || !isset($_SESSION["user_id"])) {
    echo "Debug: Redirecting due to: loggedin=" . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "unset") . 
         ", role=" . (isset($_SESSION["role"]) ? $_SESSION["role"] : "unset") . 
         ", user_id=" . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "unset");
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    error_log("Config error: " . $e->getMessage());
    die("Connection failed. Please contact support.");
}

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// Fetch customer email for notifications
$customer_id = $_SESSION["user_id"];
$sql = "SELECT email FROM users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
$stmt->execute();
$customer = $stmt->fetch();
$customer_email = $customer['email'] ?? '';

// Fetch customer's orders
$sql = "SELECT id, total, sale_date, status 
        FROM sales 
        WHERE customer_id = :customer_id 
        ORDER BY sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for status changes and send email if approved
foreach ($orders as $order) {
    if ($order['status'] === 'approved' && !isset($_SESSION['notified_' . $order['id']])) {
        $to = $customer_email;
        $subject = "Order Approved - ZedAuto";
        $message = "Dear " . htmlspecialchars($_SESSION["first_name"]) . ",\n\nYour order (ID: " . $order['id'] . ") has been approved. Total: ZMK" . number_format($order['total'], 2) . "\n\nThank you for choosing ZedAuto!";
        $headers = "From: no-reply@zedauto.com";
        mail($to, $subject, $message, $headers);
        $_SESSION['notified_' . $order['id']] = true; // Mark as notified
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - My Orders</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Static/images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #ecf0f1;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #2c2f33;
            transition: background 0.3s ease;
        }
        .navbar.sticky {
            background: #23272a;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .topbar {
            background: #2c2f33;
            color: #ffffff;
            padding: 0.75rem 0;
            z-index: 900;
        }
        .topbar .container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .topbar nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        .topbar a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            padding: 0.5rem 1rem;
            transition: background 0.2s ease;
        }
        .topbar a:hover {
            background: #34495e;
            border-radius: 4px;
        }
        .content {
            padding: 2rem;
            flex: 1;
        }
        .table-container {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .table th {
            background: #2c3e50;
            color: #ffffff;
        }
        .table tr:hover {
            background: #ecf0f1;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }
        .status-approved {
            color: #3498db;
            font-weight: bold;
        }
        .status-delivered {
            color: #27ae60;
            font-weight: bold;
        }
        .notification-bell {
            cursor: pointer;
            font-size: 1.25rem;
            color: #3498db;
        }
        .notification-panel {
            display: none;
            position: absolute;
            top: 2.5rem;
            right: 0;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 300px;
            z-index: 1000;
            padding: 0.5rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-panel.active {
            display: block;
        }
        .notification {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        .notification.approved {
            color: #3498db;
        }
        .notification.delivered {
            color: #27ae60;
        }
        #topbar-mobile {
            display: none;
            background: #2c2f33;
        }
        #topbar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #2c2f33;
            color: #ffffff;
            padding: 0.5rem;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .topbar nav {
                display: none;
            }
            #topbar-mobile {
                display: none;
            }
            #topbar-mobile.active {
                display: block;
            }
            #topbar-toggle {
                display: block;
            }
            .content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="dashboard.php" class="text-2xl font-bold text-white">ZedAuto</a>
            <button id="hamburger" class="md:hidden text-white text-2xl focus:outline-none" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav id="nav-menu" class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition">Home</a>
                <div class="flex items-center">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </nav>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-[#2c2f33]">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-4">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition">Home</a>
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none w-full" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <!-- Top Bar -->
        <nav id="topbar" class="topbar">
            <div class="container mx-auto px-4">
                <h2 class="sr-only">Customer Navigation</h2>
                <nav>
                    <a href="dashboard.php">Home</a>
                    <a href="stock-form.php">Browse Cars</a>
                    <a href="orders.php">My Orders</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="../RetailSytsem-Home.html" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Home</a>
                <a href="stock-form.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Browse Cars</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">My Orders</a>
            </div>
        </div>
    </header>
    <!-- Toggle Button for Mobile Top Bar -->
    <button id="topbar-toggle" class="md:hidden" aria-label="Toggle top bar menu">☰</button>
    <!-- Main Content -->
    <main class="content">
        <section class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50]">
                    My Orders
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="notifications relative">
                        <span class="notification-bell" onclick="toggleNotifications()"><i class="fas fa-bell"></i></span>
                        <div id="notification-panel" class="notification-panel">
                            <?php
                            $sql = "SELECT id, status FROM sales WHERE customer_id = :customer_id AND status IN ('approved', 'delivered') ORDER BY sale_date DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
                            $stmt->execute();
                            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($notifications)) {
                                echo "<div class='notification'>No new notifications.</div>";
                            } else {
                                foreach ($notifications as $notification) {
                                    $class = $notification['status'];
                                    echo "<div class='notification $class'>Order ID " . $notification['id'] . " is " . ucfirst($notification['status']) . "!</div>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <img src="https://via.placeholder.com/40" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-6 p-4 bg-[#e0f7fa] text-[#006064] rounded-md"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 p-4 bg-[#ffebee] text-[#b71c1c] rounded-md"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <!-- Orders Table -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Order History</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Order ID</th>
                                <th class="px-4 py-2">Total (ZMK)</th>
                                <th class="px-4 py-2">Sale Date</th>
                                <th class="px-4 py-2 rounded-tr-md">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo $order['id']; ?></td>
                                        <td class="px-4 py-2"><?php echo number_format($order['total'], 2); ?></td>
                                        <td class="px-4 py-2"><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                        <td class="px-4 py-2 status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
    <!-- Footer -->
    <footer class="bg-[#2c3e50] text-[#ecf0f1] py-10">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">ZedAuto</h3>
                    <p class="text-[#7f8c8d]">Providing top-notch automotive services in Lusaka, Zambia.</p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="../RetailSytsem-Home.html" class="hover:text-[#f1c40f] transition">Home</a></li>
                        <li><a href="../about.php" class="hover:text-[#f1c40f] transition">About Us</a></li>
                        <li><a href="#" class="hover:text-[#f1c40f] transition">Services</a></li>
                        <li><a href="../contact.php" class="hover:text-[#f1c40f] transition">Contact</a></li>
                        <li><a href="#" class="hover:text-[#f1c40f] transition">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-2xl hover:text-[#f1c40f] transition"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-8 text-[#7f8c8d]">
                <span id="copyright">© 2025 ZedAuto. All rights reserved.</span>
            </div>
        </div>
    </footer>
    <!-- JavaScript -->
    <script>
        // Navbar Hamburger Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
        // Close navbar mobile menu when a link is clicked
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
        // Top Bar Toggle
        const topbarToggle = document.getElementById('topbar-toggle');
        const topbarMobile = document.getElementById('topbar-mobile');
        topbarToggle.addEventListener('click', () => {
            topbarMobile.classList.toggle('active');
        });
        // Close top bar mobile menu when a link is clicked
        const topbarLinks = topbarMobile.querySelectorAll('a');
        topbarLinks.forEach(link => {
            link.addEventListener('click', () => {
                topbarMobile.classList.remove('active');
            });
        });
        // Sticky Navbar
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('sticky', window.scrollY > 50);
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
        // Toggle Notification Panel
        function toggleNotifications() {
            const panel = document.getElementById('notification-panel');
            panel.classList.toggle('active');
        }
    </script>
<?php
// Close connection
unset($conn);
?>