<?php
// Start the session
session_start();

// Check if the user is logged in and has the sales role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "sales") {
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include database connection
try {
    require_once "../config.php";
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Fetch employee data
$employee_id = $_SESSION['user_id'];
try {
    // Total sales for the employee
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_sales FROM sales WHERE employee_id = :employee_id");
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_sales = $stmt->fetchColumn();

    // Today's sales for the employee
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as today_sales FROM sales WHERE employee_id = :employee_id AND DATE(sale_date) = CURDATE()");
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
    $stmt->execute();
    $today_sales = $stmt->fetchColumn();

    // New orders today for the employee
    $stmt = $conn->prepare("SELECT COUNT(*) as new_orders FROM sales WHERE employee_id = :employee_id AND DATE(sale_date) = CURDATE()");
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
    $stmt->execute();
    $new_orders = $stmt->fetchColumn();

    // Low stock items (quantity <= reorder_level)
    $stmt = $conn->prepare("SELECT COUNT(*) as low_stock FROM inventory WHERE quantity <= reorder_level");
    $stmt->execute();
    $low_stock = $stmt->fetchColumn();

    // Sales history (last 7 days) for the employee
    $stmt = $conn->prepare("SELECT DATE(sale_date) as sale_date, COALESCE(SUM(total), 0) as daily_total 
                            FROM sales 
                            WHERE employee_id = :employee_id 
                            GROUP BY DATE(sale_date) 
                            ORDER BY sale_date DESC 
                            LIMIT 7");
    $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
    $stmt->execute();
    $sales_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data
    $dates = array_map(function($row) {
        return date('M d', strtotime($row['sale_date']));
    }, $sales_history);
    $sales_values = array_column($sales_history, 'daily_total');
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_sales = $today_sales = $new_orders = $low_stock = 0;
    $sales_history = $dates = $sales_values = [];
}

// Close connection
unset($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Sales Dashboard</title>
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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .metric-container, .chart-container {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        canvas {
            max-width: 100%;
            height: 250px !important;
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
        @media (max-width: 576px) {
            canvas {
                height: 200px !important;
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
                <a href="../logout.php" class="text-red400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <!-- Top Bar -->
        <nav id="topbar" class="topbar">
            <div class="container mx-auto px-4">
                <h2 class="sr-only">Sales Navigation</h2>
                <nav>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="orders.php">Orders</a>
                    <a href="point_of_sale.php">Point of Sale</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Orders</a>
                <a href="point_of_sale.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Point of Sale</a>
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
                Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
            </h1>

            <!-- Metrics -->
            <div class="metric-container mb-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 card">
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Today's Sales</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($today_sales, 2); ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min(($today_sales / 1000) * 100, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#2ecc71]">+75%</span></span>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Sales</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($total_sales, 2); ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min(($total_sales / 10000) * 100, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#2ecc71]">+40%</span></span>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">New Orders</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $new_orders; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min($new_orders * 5, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#e74c3c]">-50%</span></span>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Low Stock Items</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $low_stock; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#e74c3c] rounded-full" style="width: <?php echo min($low_stock * 10, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#e74c3c]">+10%</span></span>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-container card mb-8">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4 text-center">Your Sales History (Last 7 Days)</h2>
                <canvas id="salesChart"></canvas>
            </div>

            <!-- Quick Links -->
            <div class="metric-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Quick Links</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="point_of_sale.php" class="p-4 bg-[#3498db] text-white rounded-md text-center hover:bg-[#2980b9] transition">Go to Point of Sale</a>
                    <a href="orders.php" class="p-4 bg-[#3498db] text-white rounded-md text-center hover:bg-[#2980b9] transition">View Orders</a>
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

        // Sales History Chart
        const salesChart = document.getElementById('salesChart').getContext('2d');
        new Chart(salesChart, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse($dates)); ?>,
                datasets: [{
                    label: 'Daily Sales (ZMK)',
                    data: <?php echo json_encode(array_reverse($sales_values)); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.3)',
                    borderColor: '#2ecc71',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: { display: true, text: 'Date', color: '#2c3e50', font: { size: 14 } },
                        grid: { display: false },
                        ticks: { color: '#7f8c8d' }
                    },
                    y: {
                        title: { display: true, text: 'Sales (ZMK)', color: '#2c3e50', font: { size: 14 } },
                        grid: { color: '#ecf0f1', borderDash: [5, 5] },
                        ticks: {
                            color: '#7f8c8d',
                            callback: function(value) { return 'ZMK' + value.toLocaleString(); }
                        },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top', labels: { color: '#2c3e50' } },
                    tooltip: {
                        backgroundColor: '#2c3e50',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        callbacks: {
                            label: function(context) { return 'ZMK' + context.parsed.y.toLocaleString(); }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>