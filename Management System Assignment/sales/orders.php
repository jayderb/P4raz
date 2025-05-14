<?php
// Start the session
session_start();

// Check if the user is logged in and has the admin role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "sales") {
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

// Fetch orders data
$sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date 
        FROM sales s 
        LEFT JOIN users u ON s.employee_id = u.id 
        ORDER BY s.sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total orders and revenue
$sql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$summary = $stmt->fetch();

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_order'])) {
        $order_id = intval($_POST['order_id']);
        $employee_id = intval($_POST['employee_id']);
        $total = floatval($_POST['total']);

        if ($total >= 0) {
            try {
                $sql = "UPDATE sales SET employee_id = :employee_id, total = :total WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
                $stmt->bindParam(":total", $total, PDO::PARAM_STR);
                $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Order updated successfully!";
                // Refresh orders data
                $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date 
                        FROM sales s 
                        LEFT JOIN users u ON s.employee_id = u.id 
                        ORDER BY s.sale_date DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error_msg = "Error updating order: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide a valid total.";
        }
    } elseif (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        try {
            $sql = "DELETE FROM sales WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = "Order deleted successfully!";
            // Refresh orders data
            $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date 
                    FROM sales s 
                    LEFT JOIN users u ON s.employee_id = u.id 
                    ORDER BY s.sale_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Refresh summary
            $sql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_revenue FROM sales";
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
    <title>Orders Management - ZedAuto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            font-size: 14px;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #2c3e50;
            padding-top: 20px;
            color: white;
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
        <h2>ZedAuto Sales</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="point_of_sale.php">Point Of Sale</a>
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
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

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
                                <td class="action-buttons">
                                    <form method="post" style="display: none;" id="editForm-<?php echo $order['id']; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="employee_id">
                                            <?php
                                            $sql = "SELECT id, first_name, last_name FROM users WHERE role = 'employee'";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->execute();
                                            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($employees as $emp) {
                                                $selected = $emp['id'] == $order['employee_id'] ? 'selected' : '';
                                                echo "<option value='{$emp['id']}' $selected>" . htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                        <input type="number" name="total" value="<?php echo $order['total']; ?>" step="0.01" min="0" required>
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