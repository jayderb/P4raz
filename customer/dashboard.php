<?php
// Start the session
session_start();

// Check if the user is logged in and has the admin role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "manager") {
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch employee data
$sql = "SELECT id, first_name, last_name, role, last_login, phone FROM users WHERE role = 'sales'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total sales
$sql = "SELECT COALESCE(SUM(total), 0) as total_sales FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$total_revenue = $stmt->fetchColumn();

// Fetch today's income
$sql = "SELECT COALESCE(SUM(total), 0) as today_income 
        FROM sales 
        WHERE DATE(sale_date) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$today_income = $stmt->fetchColumn();

// Fetch today's sales by product for chart
$sql = "SELECT p.name, COALESCE(SUM(si.quantity * si.price), 0) as total_sales
        FROM sales s
        JOIN sales_items si ON s.id = si.sale_id
        JOIN products p ON si.product_id = p.id
        WHERE DATE(s.sale_date) = CURDATE()
        GROUP BY p.id, p.name
        ORDER BY total_sales DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->execute();
$todays_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$todays_sales_labels = array_column($todays_sales, 'name');
$todays_sales_values = array_column($todays_sales, 'total_sales');

// Fetch new orders (sales today)
$sql = "SELECT COUNT(*) as new_orders 
        FROM sales 
        WHERE DATE(sale_date) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$new_orders = $stmt->fetchColumn();

// Fetch new users (customers added today)
$sql = "SELECT COUNT(*) as new_users 
        FROM users 
        WHERE role = 'customer' AND DATE(created_at) = CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->execute();
$new_users = $stmt->fetchColumn();

// Fetch sales history (last 7 days)
$sql = "SELECT DATE(sale_date) as sale_date, COALESCE(SUM(total), 0) as daily_total 
        FROM sales 
        GROUP BY DATE(sale_date) 
        ORDER BY sale_date DESC 
        LIMIT 7";
$stmt = $conn->prepare($sql);
$stmt->execute();
$sales_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for charts
$dates = array_map(function($date) {
    return date('M d', strtotime($date));
}, array_column($sales_history, 'sale_date'));
$sales_values = array_column($sales_history, 'daily_total');

// User statistics (new customers over last 7 days)
$sql = "SELECT DATE(created_at) as reg_date, COUNT(*) as user_count 
        FROM users 
        WHERE role = 'customer' 
        GROUP BY DATE(created_at) 
        ORDER BY reg_date DESC 
        LIMIT 7";
$stmt = $conn->prepare($sql);
$stmt->execute();
$user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user_stats_dates = array_map(function($date) {
    return date('M d', strtotime($date));
}, array_column($user_stats, 'reg_date'));
$user_stats_values = array_column($user_stats, 'user_count');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ZedAuto</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #2c3e50;
            padding-top: 20px;
            color: white;
            transition: transform 0.3s ease;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        .sidebar a:hover {
            background-color: #34495e;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #2c3e50;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header .user-info {
            display: flex;
            align-items: center;
        }
        .header .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .header .user-info span {
            font-size: 16px;
            color: #7f8c8d;
        }
        .header .buttons button {
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .header .buttons button:hover {
            background-color: #2980b9;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .metric-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #ecf0f1;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }
        .metric-card h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .metric-card p {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 10px;
        }
        .metric-card canvas {
            max-width: 100%;
            height: 150px !important;
            border-radius: 8px;
            margin: 10px auto;
        }
        .change {
            font-size: 14px;
            color: #7f8c8d;
            display: block;
            margin-top: 10px;
        }
        .charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        .chart-container {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #ecf0f1;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .chart-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }
        .chart-container h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .chart-container canvas {
            max-width: 100%;
            height: 300px !important;
            border-radius: 8px;
        }
        #toggleBtn {
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            display: none;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            #toggleBtn {
                display: block;
            }
            .metrics {
                gap: 15px;
            }
            .metric-card {
                padding: 15px;
            }
            .metric-card canvas {
                height: 120px !important;
            }
            .charts {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .chart-container {
                padding: 15px;
            }
            .chart-container canvas {
                height: 250px !important;
            }
        }
        @media (max-width: 576px) {
            .metric-card h3 {
                font-size: 16px;
            }
            .metric-card p {
                font-size: 20px;
            }
            .metric-card canvas {
                height: 100px !important;
            }
            .chart-container h3 {
                font-size: 18px;
            }
            .chart-container canvas {
                height: 200px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="customs.php">Customs</a>
        <a href="orders.php">Orders</a>
        <a href="inventory.php">Inventory</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn">☰</button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="header-right">
                <div class="user-info">
                    <img src="../images/avatar.jpg" alt="User">
                    <span>Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                </div>
                <div class="buttons">
                    <button onclick="location.href='manage_users.php'">Manage</button>
                </div>
            </div>
        </div>

        <!-- Metrics -->
        <div class="metrics">
            <div class="metric-card metric-income">
                <h3>Today's Income</h3>
                <p>ZMK<?php echo number_format($today_income, 2); ?></p>
                <canvas id="todaySalesChart"></canvas>
                <span class="change">Change <span style="color: #2ecc71;">+75%</span></span>
            </div>
            <div class="metric-card metric-revenue">
                <h3>Total Revenue</h3>
                <p>ZMK<?php echo number_format($total_revenue, 2); ?></p>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo min(($total_revenue / 10000) * 100, 100); ?>%;"></div>
                </div>
                <span class="change">Change <span style="color: #2ecc71;">+40%</span></span>
            </div>
            <div class="metric-card metric-orders">
                <h3>New Orders</h3>
                <p><?php echo $new_orders; ?></p>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo min($new_orders * 5, 100); ?>%;"></div>
                </div>
                <span class="change">Change <span style="color: #e74c3c;">-50%</span></span>
            </div>
            <div class="metric-card metric-users">
                <h3>New Users</h3>
                <p><?php echo $new_users; ?></p>
                <div class="progress">
                    <div class="progress-bar" style="width: <?php echo min($new_users * 10, 100); ?>%;"></div>
                </div>
                <span class="change">Change <span style="color: #2ecc71;">+20%</span></span>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts">
            <div class="chart-container">
                <h3>Sales History (Last 7 Days)</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-container">
                <h3>New Users (Last 7 Days)</h3>
                <canvas id="usersChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Today's Sales Chart
        const todaySalesChart = document.getElementById('todaySalesChart').getContext('2d');
        new Chart(todaySalesChart, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($todays_sales_labels); ?>,
                datasets: [{
                    label: 'Sales by Product (ZMK)',
                    data: <?php echo json_encode($todays_sales_values); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: '#2ecc71',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 800,
                    easing: 'easeOutQuart'
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Product',
                            color: '#2c3e50',
                            font: { size: 12, weight: '600' }
                        },
                        grid: { display: false },
                        ticks: {
                            color: '#7f8c8d',
                            font: { size: 10 },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Sales (ZMK)',
                            color: '#2c3e50',
                            font: { size: 12, weight: '600' }
                        },
                        grid: {
                            color: '#ecf0f1',
                            borderDash: [5, 5]
                        },
                        ticks: {
                            color: '#7f8c8d',
                            font: { size: 10 },
                            callback: function(value) {
                                return 'ZMK' + value.toLocaleString();
                            }
                        },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#2c3e50',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        padding: 8,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                return 'ZMK' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false
                },
                hover: {
                    mode: 'nearest',
                    intersect: false
                }
            }
        });

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
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date',
                            color: '#2c3e50',
                            font: { size: 16, weight: '600' }
                        },
                        grid: { display: false },
                        ticks: {
                            color: '#7f8c8d',
                            font: { size: 12 }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Sales (ZMK)',
                            color: '#2c3e50',
                            font: { size: 16, weight: '600' }
                        },
                        grid: {
                            color: '#ecf0f1',
                            borderDash: [5, 5]
                        },
                        ticks: {
                            color: '#7f8c8d',
                            font: { size: 12 },
                            callback: function(value) {
                                return 'ZMK' + value.toLocaleString();
                            }
                        },
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#2c3e50',
                            font: { size: 14, weight: '500' },
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: '#2c3e50',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                return 'ZMK' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false
                },
                hover: {
                    mode: 'nearest',
                    intersect: false
                }
            }
        });

        // Placeholder for Users Chart (unimplemented)
        const usersChart = document.getElementById('usersChart').getContext('2d');
        const usersData = {
            labels: <?php echo json_encode(array_reverse($user_stats_dates)); ?>,
            datasets: [{
                label: 'New Users',
                data: <?php echo json_encode(array_reverse($user_stats_values)); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: '#3498db',
                borderWidth: 2
            }]
        };
        // Note: Uncomment to enable users chart
        // new Chart(usersChart, { type: 'line', data: usersData });
    </script>
</body>
</html>

<?php
// Close connection
unset($conn);
?>