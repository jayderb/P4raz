<?php
// Initialize the session
session_start();

// Check if the user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['warehouse', 'manager'])) {
    session_unset();
    session_destroy();
    header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
    exit;
}

// Include database connection
require_once dirname(__DIR__) . '/db_connection.php';

$conn = connectionToDatabase();

// Fetch inventory data
$stmt = $conn->prepare("SELECT part_id, part_name, quantity, unit_price FROM inventory WHERE warehouse_id = ?");
$stmt->bind_param("i", $_SESSION['warehouse_id']);
$stmt->execute();
$result = $stmt->get_result();
$inventory = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending dispatch orders
$stmt = $conn->prepare("SELECT order_id, customer_id, part_id, quantity, status, order_date FROM dispatch_orders WHERE status = 'pending' AND warehouse_id = ?");
$stmt->bind_param("i", $_SESSION['warehouse_id']);
$stmt->execute();
$result = $stmt->get_result();
$dispatchOrders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent activity logs
$stmt = $conn->prepare("SELECT action, details, timestamp FROM activity_logs WHERE warehouse_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt->bind_param("i", $_SESSION['warehouse_id']);
$stmt->execute();
$result = $stmt->get_result();
$activityLogs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Warehouse Dashboard</title>
    <!-- Favicon -->
    <link rel="icon" type="image/ico" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../Static/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../Static/images/favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
    <!-- Tailwind CSS CDN (v3) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts (Inter for clean typography) -->
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
        .sub-header {
            background: #40444b;
        }
        .table-container {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .table th {
            background: #2c3e50;
            color: #ffffff;
        }
        .table tr:hover {
            background: #ecf0f1;
        }
        .dispatch-btn {
            background: #2ecc71;
            transition: background 0.2s ease;
        }
        .dispatch-btn:hover {
            background: #27ae60;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none w-full" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c2f33] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8">
        <section class="container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50] mb-6 text-center">
                Welcome back, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!
            </h1>

            <!-- Inventory -->
            <div class="table-container p-6 mb-8 card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Current Inventory</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Part ID</th>
                                <th class="px-4 py-2">Part Name</th>
                                <th class="px-4 py-2">Quantity</th>
                                <th class="px-4 py-2 rounded-tr-md">Price (ZMW)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-[#2c3e50]">No inventory items found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($item['part_id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($item['part_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="px-4 py-2"><?php echo number_format($item['unit_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Dispatch Orders -->
            <div class="table-container p-6 mb-8 card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Pending Dispatch Orders</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Order ID</th>
                                <th class="px-4 py-2">Customer ID</th>
                                <th class="px-4 py-2">Part ID</th>
                                <th class="px-4 py-2">Quantity</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Order Date</th>
                                <th class="px-4 py-2 rounded-tr-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dispatchOrders)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-[#2c3e50]">No pending orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dispatchOrders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['customer_id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['part_id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['status']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td class="px-4 py-2">
                                            <form action="dispatch_order.php" method="post">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" class="dispatch-btn text-white px-4 py-2 rounded-md">Dispatch</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity Logs -->
            <div class="table-container p-6 card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Recent Activity Logs</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Action</th>
                                <th class="px-4 py-2">Details</th>
                                <th class="px-4 py-2 rounded-tr-md">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activityLogs)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-[#2c3e50]">No activity logs found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($activityLogs as $log): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($log['details']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($log['timestamp']); ?></td>
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
        // Hamburger Menu Toggle
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when a link is clicked
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
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
    </script>
</body>
</html>