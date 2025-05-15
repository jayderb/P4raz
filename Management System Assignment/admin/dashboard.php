<?php
// Start the session
session_start();

// Check if the user is logged in and has the manager role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "manager") {
    header("location: ../Retail System-LocalGarage-Login.php");
    exit;
}

// Include database connection
require_once dirname(__DIR__) . '/db_connection.php';

$conn = connectionToDatabase();

try {
    // Fetch employee data
    $stmt = $conn->prepare("SELECT id, first_name, last_name, role, last_login, phone FROM users WHERE role = 'sales'");
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch total sales
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_sales FROM sales");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_revenue = $result->fetch_assoc()['total_sales'];
    $stmt->close();

    // Fetch today's income
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as today_income FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $today_income = $result->fetch_assoc()['today_income'];
    $stmt->close();

    // Fetch new orders (sales today)
    $stmt = $conn->prepare("SELECT COUNT(*) as new_orders FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $new_orders = $result->fetch_assoc()['new_orders'];
    $stmt->close();

    // Fetch new users (customers added today)
    $stmt = $conn->prepare("SELECT COUNT(*) as new_users FROM users WHERE role = 'customer' AND DATE(created_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $new_users = $result->fetch_assoc()['new_users'];
    $stmt->close();

    // Fetch sales history (last 7 days)
    $stmt = $conn->prepare("SELECT DATE(sale_date) as sale_date, COALESCE(SUM(total), 0) as daily_total 
                            FROM sales 
                            GROUP BY DATE(sale_date) 
                            ORDER BY sale_date DESC 
                            LIMIT 7");
    $stmt->execute();
    $result = $stmt->get_result();
    $sales_history = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Prepare data for charts
    $dates = array_map(function($row) {
        return date('M d', strtotime($row['sale_date']));
    }, $sales_history);
    $sales_values = array_column($sales_history, 'daily_total');

    // User statistics (new customers over last 7 days)
    $stmt = $conn->prepare("SELECT DATE(created_at) as reg_date, COUNT(*) as user_count 
                            FROM users 
                            WHERE role = 'customer' 
                            GROUP BY DATE(created_at) 
                            ORDER BY reg_date DESC 
                            LIMIT 7");
    $stmt->execute();
    $result = $stmt->get_result();
    $user_stats = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $user_stats_dates = array_map(function($row) {
        return date('M d', strtotime($row['reg_date']));
    }, $user_stats);
    $user_stats_values = array_column($user_stats, 'user_count');
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $employees = $sales_history = $user_stats = [];
    $total_revenue = $today_income = $new_orders = $new_users = 0;
    $dates = $sales_values = $user_stats_dates = $user_stats_values = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Manager Dashboard</title>
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
        .sub-header {
            background: #40444b;
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
        .table-container, .chart-container, .metric-container {
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
        .action-btn {
            background: #2ecc71;
            transition: background 0.2s ease;
        }
        .action-btn:hover {
            background: #27ae60;
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
            .charts {
                grid-template-columns: 1fr;
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
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
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

    <!-- Top Bar (Former Sidebar) -->
    
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
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Today's Income</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($today_income, 2); ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min(($today_income / 1000) * 100, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#2ecc71]">+75%</span></span>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Revenue</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($total_revenue, 2); ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min(($total_revenue / 10000) * 100, 100); ?>%;"></div>
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
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">New Users</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $new_users; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min($new_users * 10, 100); ?>%;"></div>
                    </div>
                    <span class="text-sm text-[#7f8c8d] mt-2 block">Change <span class="text-[#2ecc71]">+20%</span></span>
                </div>
            </div>

            <!-- Employee Table -->
            <div class="table-container mb-8 card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Sales Employees</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">ID</th>
                                <th class="px-4 py-2">First Name</th>
                                <th class="px-4 py-2">Last Name</th>
                                <th class="px-4 py-2">Role</th>
                                <th class="px-4 py-2">Last Login</th>
                                <th class="px-4 py-2 rounded-tr-md">Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-[#2c3e50]">No sales employees found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['id']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['first_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['last_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['role']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['last_login'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($employee['phone']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="chart-container card">
                    <h2 class="text-2xl font-medium text-[#2c3e50] mb-4 text-center">Sales History (Last 7 Days)</h2>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-container card">
                    <h2 class="text-2xl font-medium text-[#2c3e50] mb-4 text-center">New Users (Last 7 Days)</h2>
                    <canvas id="usersChart"></canvas>
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

        // New Users Chart
        const usersChart = document.getElementById('usersChart').getContext('2d');
        new Chart(usersChart, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse($user_stats_dates)); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_reverse($user_stats_values)); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: '#3498db',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3498db',
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
                        title: { display: true, text: 'Users', color: '#2c3e50', font: { size: 14 } },
                        grid: { color: '#ecf0f1', borderDash: [5, 5] },
                        ticks: { color: '#7f8c8d', beginAtZero: true }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top', labels: { color: '#2c3e50' } },
                    tooltip: {
                        backgroundColor: '#2c3e50',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                }
            }
        });
    </script>
</body>
</html>