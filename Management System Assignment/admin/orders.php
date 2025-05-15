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

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// Fetch orders data with status
$sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
        FROM sales s 
        LEFT JOIN users u ON s.employee_id = u.id 
        ORDER BY s.sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch summary data (total orders, total revenue, pending, and delivered counts)
$sql = "SELECT 
            COUNT(*) as total_orders, 
            COALESCE(SUM(total), 0) as total_revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
        FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$summary = $stmt->fetch();

// Fetch employees for the add/edit forms
$sql = "SELECT id, first_name, last_name FROM users WHERE role = 'sales'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_order'])) {
        // Add new order with status defaulting to 'pending'
        $employee_id = intval($_POST['employee_id']);
        $total = floatval($_POST['total']);
        $sale_date = $_POST['sale_date'];

        if ($total >= 0 && !empty($sale_date) && strtotime($sale_date)) {
            try {
                $sql = "INSERT INTO sales (employee_id, total, sale_date, status) 
                        VALUES (:employee_id, :total, :sale_date, 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
                $stmt->bindParam(":total", $total, PDO::PARAM_STR);
                $stmt->bindParam(":sale_date", $sale_date, PDO::PARAM_STR);
                $stmt->execute();
                $success_msg = "Order added successfully with status 'Pending'!";
                // Refresh orders data
                $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
                        FROM sales s 
                        LEFT JOIN users u ON s.employee_id = u.id 
                        ORDER BY s.sale_date DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Refresh summary
                $sql = "SELECT 
                        COUNT(*) as total_orders, 
                        COALESCE(SUM(total), 0) as total_revenue,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                    FROM sales";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $summary = $stmt->fetch();
            } catch (PDOException $e) {
                $error_msg = "Error adding order: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid order details.";
        }
    } elseif (isset($_POST['update_order'])) {
        // Update existing order, including status
        $order_id = intval($_POST['order_id']);
        $employee_id = intval($_POST['employee_id']);
        $total = floatval($_POST['total']);
        $status = $_POST['status'];

        if ($total >= 0 && in_array($status, ['pending', 'delivered'])) {
            try {
                $sql = "UPDATE sales SET employee_id = :employee_id, total = :total, status = :status WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
                $stmt->bindParam(":total", $total, PDO::PARAM_STR);
                $stmt->bindParam(":status", $status, PDO::PARAM_STR);
                $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Order updated successfully!";
                // Refresh orders data
                $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
                        FROM sales s 
                        LEFT JOIN users u ON s.employee_id = u.id 
                        ORDER BY s.sale_date DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Refresh summary
                $sql = "SELECT 
                        COUNT(*) as total_orders, 
                        COALESCE(SUM(total), 0) as total_revenue,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                    FROM sales";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $summary = $stmt->fetch();
            } catch (PDOException $e) {
                $error_msg = "Error updating order: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid order details.";
        }
    } elseif (isset($_POST['delete_order'])) {
        // Delete order
        $order_id = intval($_POST['order_id']);
        try {
            $sql = "DELETE FROM sales WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = "Order deleted successfully!";
            // Refresh orders data
            $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
                    FROM sales s 
                    LEFT JOIN users u ON s.employee_id = u.id 
                    ORDER BY s.sale_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Refresh summary
            $sql = "SELECT 
                    COUNT(*) as total_orders, 
                    COALESCE(SUM(total), 0) as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                FROM sales";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $summary = $stmt->fetch();
        } catch (PDOException $e) {
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
        .table-container, .metric-container {
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
        .delete-btn {
            background: #e74c3c;
            transition: background 0.2s ease;
        }
        .delete-btn:hover {
            background: #c0392b;
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
        .status-delivered {
            color: #27ae60;
            font-weight: bold;
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
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
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
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c3e50] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <!-- Top Bar -->
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
    <!-- Toggle Button for Mobile Top Bar -->
    <button id="topbar-toggle" class="md:hidden" aria-label="Toggle top bar menu">☰</button>
    <!-- Main Content -->
    <main class="content">
        <section class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50]">
                    Orders Management
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <img src="https://via.placeholder.com/40" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                    <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">
                        Add Order
                    </button>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-6 p-4 bg-[#e0f7fa] text-[#006064] rounded-md"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 p-4 bg-[#ffebee] text-[#b71c1c] rounded-md"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <!-- Add Order Form -->
            <div id="addForm" class="table-container mb-8 card hidden">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Add New Order</h2>
                <form method="post" class="flex flex-col sm:flex-row gap-4">
                    <select name="employee_id" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="total" placeholder="Total Amount" step="0.01" min="0" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <input type="datetime-local" name="sale_date" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <div class="flex gap-4">
                        <button type="submit" name="add_order" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Add Order</button>
                        <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition">Cancel</button>
                    </div>
                </form>
            </div>
            <!-- Summary -->
            <div class="metric-container mb-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 card">
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Orders</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $summary['total_orders']; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min($summary['total_orders'] * 5, 100); ?>%;"></div>
                    </div>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Total Revenue</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($summary['total_revenue'], 2); ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min(($summary['total_revenue'] / 10000) * 100, 100); ?>%;"></div>
                    </div>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Pending Orders</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $summary['pending_orders']; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min($summary['pending_orders'] * 5, 100); ?>%;"></div>
                    </div>
                </div>
                <div class="p-4 bg-[#f9f9f9] rounded-md shadow-sm">
                    <h3 class="text-lg font-medium text-[#2c3e50] mb-2">Delivered Orders</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $summary['delivered_orders']; ?></p>
                    <div class="progress h-2 bg-[#ecf0f1] rounded-full mt-2">
                        <div class="progress-bar h-full bg-[#2ecc71] rounded-full" style="width: <?php echo min($summary['delivered_orders'] * 5, 100); ?>%;"></div>
                    </div>
                </div>
            </div>
            <!-- Orders Table -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Orders</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Order ID</th>
                                <th class="px-4 py-2">Employee</th>
                                <th class="px-4 py-2">Total</th>
                                <th class="px-4 py-2">Sale Date</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2 rounded-tr-md">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo $order['id']; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td class="px-4 py-2">ZMK<?php echo number_format($order['total'], 2); ?></td>
                                    <td class="px-4 py-2"><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                    <td class="px-4 py-2 status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="hidden" id="editForm-<?php echo $order['id']; ?>">
                                            <form method="post" class="flex flex-col sm:flex-row gap-4">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="employee_id" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                                    <?php foreach ($employees as $emp): ?>
                                                        <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $order['employee_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="number" name="total" value="<?php echo $order['total']; ?>" step="0.01" min="0" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                                                <select name="status" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                </select>
                                                <div class="flex gap-4">
                                                    <button type="submit" name="update_order" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Save</button>
                                                    <button type="button" onclick="document.getElementById('editForm-<?php echo $order['id']; ?>').classList.add('hidden')" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="flex gap-4">
                                            <button onclick="document.getElementById('editForm-<?php echo $order['id']; ?>').classList.toggle('hidden')" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition action-btn">Edit</button>
                                            <form method="post">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition delete-btn" onclick="return confirm('Are you sure you want to delete this order?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
<?php
// Close connection
unset($conn);
?>