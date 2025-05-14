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
    <link rel="stylesheet" href="style.css">
    <title>Orders Management - ZedAuto</title>
    <style>
        .card {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .form-container {
            margin-bottom: 20px;
        }
        .form-container form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .form-container input, .form-container select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-container button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background-color: #3498db;
            color: white;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #2980b9;
        }
        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-item {
            flex: 1;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }
        .status-delivered {
            color: #27ae60;
            font-weight: bold;
        }
        .action-buttons form {
            display: inline;
        }
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
        }
        .action-buttons .edit-btn {
            background-color: #2ecc71;
            color: white;
        }
        .action-buttons .edit-btn:hover {
            background-color: #27ae60;
        }
        .action-buttons .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .action-buttons .delete-btn:hover {
            background-color: #c0392b;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #e0f7fa;
            color: #006064;
        }
        .message.error {
            background-color: #ffebee;
            color: #b71c1c;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            .content {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="inventory.php">Inventory</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn" style="position: fixed; top: 15px; left: 15px; background-color: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; display: none;">
        ☰
    </button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Orders Management</h1>
            <div class="header-right">
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span>Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                </div>
                <div class="buttons">
                    <button onclick="document.getElementById('addForm').style.display='block'">Add Order</button>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Add Order Form -->
        <div id="addForm" class="card form-container" style="display: none;">
            <h3>Add New Order</h3>
            <form method="post">
                <select name="employee_id" required>
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="total" placeholder="Total Amount" step="0.01" min="0" required>
                <input type="datetime-local" name="sale_date" required>
                <button type="submit" name="add_order">Add Order</button>
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" style="background-color: #e74c3c;">Cancel</button>
            </form>
        </div>

        <!-- Summary -->
        <div class="card">
            <h3>Order Summary</h3>
            <div class="summary">
                <div class="summary-item">
                    <h4>Total Orders</h4>
                    <p><?php echo $summary['total_orders']; ?></p>
                </div>
                <div class="summary-item">
                    <h4>Total Revenue</h4>
                    <p>$<?php echo number_format($summary['total_revenue'], 2); ?></p>
                </div>
                <div class="summary-item">
                    <h4>Pending Orders</h4>
                    <p><?php echo $summary['pending_orders']; ?></p>
                </div>
                <div class="summary-item">
                    <h4>Delivered Orders</h4>
                    <p><?php echo $summary['delivered_orders']; ?></p>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <h3>Orders</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Employee</th>
                            <th>Total</th>
                            <th>Sale Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                <td class="status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </td>
                                <td class="action-buttons">
                                    <form method="post" style="display: none;" id="editForm-<?php echo $order['id']; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="employee_id">
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $order['employee_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="total" value="<?php echo $order['total']; ?>" step="0.01" min="0" required>
                                        <select name="status">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        </select>
                                        <button type="submit" name="update_order" class="edit-btn">Save</button>
                                        <button type="button" onclick="document.getElementById('editForm-<?php echo $order['id']; ?>').style.display='none'" style="background-color: #e74c3c;">Cancel</button>
                                    </form>
                                    <button class="edit-btn" onclick="document.getElementById('editForm-<?php echo $order['id']; ?>').style.display='block'">Edit</button>
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="delete_order" class="delete-btn" onclick="return confirm('Are you sure you want to delete this order?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript for Sidebar Toggle -->
    <script>
        // Sidebar Toggle for Mobile
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const content = document.getElementById('content');

        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'block';
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                toggleBtn.style.display = 'block';
            } else {
                toggleBtn.style.display = 'none';
                sidebar.classList.remove('active');
            }
        });

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>

<?php
// Close connection
unset($conn);
?>