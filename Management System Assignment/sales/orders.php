<?php
// Start the session
session_start();

// Check if the user is logged in and has the sales role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "sales") {
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// Fetch orders data for the logged-in employee
$employee_id = $_SESSION['user_id'];
$sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date 
        FROM sales s 
        LEFT JOIN users u ON s.employee_id = u.id 
        WHERE s.employee_id = :employee_id 
        ORDER BY s.sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total orders and revenue for the employee
$sql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue 
        FROM sales WHERE employee_id = :employee_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
$stmt->execute();
$summary = $stmt->fetch();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Delete sales_items
            $sql = "DELETE FROM sales_items WHERE sale_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Delete sale
            $sql = "DELETE FROM sales WHERE id = :id AND employee_id = :employee_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
            $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $conn->commit();
            $success_msg = "Order deleted successfully!";
            
            // Refresh orders data
            $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date 
                    FROM sales s 
                    LEFT JOIN users u ON s.employee_id = u.id 
                    WHERE s.employee_id = :employee_id 
                    ORDER BY s.sale_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Refresh summary
            $sql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue 
                    FROM sales WHERE employee_id = :employee_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
            $stmt->execute();
            $summary = $stmt->fetch();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_msg = "Error deleting order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Orders Management</title>
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
        .card {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .table th {
            background: #2c3e50;
            color: #ffffff;
        }
        .table tr:hover {
            background: #ecf0f1;
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
        <!-- Top Bar -->
        <nav id="topbar" class="topbar">
            <div class="container mx-auto px-4">
                <h2 class="sr-only">Sales Navigation</h2>
                <nav>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="point_of_sale.php">Point of Sale</a>
                    <a href="orders.php">Orders</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
                <a href="point_of_sale.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Point of Sale</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Orders</a>
                <a href="../logout.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Logout</a>
            </div>
        </div>
    </header>

    <!-- Toggle Button for Mobile Top Bar -->
    <button id="topbar-toggle" class="md:hidden" aria-label="Toggle top bar menu">☰</button>

    <!-- Main Content -->
    <main class="content">
        <section class="container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50] mb-6 text-center">
                Orders Management
            </1>
            <h1 class="text-xl md:text-2xl font-medium text-[#2c3e50] mb-4 text-center">
                Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
            </h1>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="card mb-6 bg-[#e0f7fa] text-[#006064] p-4">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="card mb-6 bg-[#ffebee] text-[#b71c1c] p-4">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Summary -->
            <div class="card mb-8">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Order Summary</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm text-center">
                        <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Orders</h3>
                        <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $summary['total_orders']; ?></p>
                    </div>
                    <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm text-center">
                        <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Revenue</h3>
                        <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($summary['total_revenue'], 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Your Orders</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Order ID</th>
                                <th class="px-4 py-2">Employee</th>
                                <th class="px-4 py-2">Total</th>
                                <th class="px-4 py-2 rounded-tr-md">Sale Date</th>
                                <th class="px-4 py-2 rounded-tr-md">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-[#2c3e50]">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td class="px-4 py-2">ZMK<?php echo number_format($order['total'], 2); ?></td>
                                        <td class="px-4 py-2"><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                        <td class="px-4 py-2">
                                            <form method="post">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="px-3 py-1 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition" onclick="return confirm('Are you sure you want to delete this order?')">Delete</button>
                                            </form>
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
    </script>
</body>
</html>

<?php
// Close connection
unset($conn);
?>