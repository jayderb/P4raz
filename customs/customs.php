<?php
// Start the session
session_start();

// Check if the user is logged in and has the customs role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customs") {
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

// Fetch orders data with status, part names, and customer contact details
$sql = "SELECT s.id, s.employee_id, s.customer_id, u.first_name, u.last_name, cu.email as customer_email, s.total, s.sale_date, s.status,
        GROUP_CONCAT(i.part_name SEPARATOR ', ') as part_names
        FROM sales s 
        LEFT JOIN users u ON s.employee_id = u.id 
        LEFT JOIN users cu ON s.customer_id = cu.id
        LEFT JOIN sales_items si ON s.id = si.sale_id
        LEFT JOIN inventory i ON si.product_id = i.part_id
        GROUP BY s.id, s.employee_id, s.customer_id, u.first_name, u.last_name, cu.email, s.total, s.sale_date, s.status
        ORDER BY s.sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch summary data (total orders, pending, and delivered counts)
$sql = "SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
        FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$summary = $stmt->fetch();

// Handle form submissions for marking orders as delivered
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_delivered'])) {
    $order_id = intval($_POST['order_id']);
    try {
        $sql = "UPDATE sales SET status = 'delivered' WHERE id = :id AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $success_msg = "Order marked as delivered successfully!";
        } else {
            $error_msg = "Order already delivered or not found.";
        }
        // Refresh orders data
        $sql = "SELECT s.id, s.employee_id, s.customer_id, cu.email as customer_email, u.first_name, u.last_name, s.total, s.sale_date, s.status,
                GROuP_CONCAT(i.name SEPARATOR ', ') as part_names
                FROM sales s 
                LEFT JOIN users u ON s.employee_id = u.id
                LEFT JOIN users cu ON s.customer_id = cu.id
                LEFT JOIN sales_items si ON s.id = si.sale_id
                LEFT JOIN inventory i ON si.part_id = i.id
                GROUP BY s.id, s.employee_id, s.customer_id, u.first_name, u.last_name, cu.email, s.total, s.sale_date, s.status 
                ORDER BY s.sale_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Refresh summary
        $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
            FROM sales";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $summary = $stmt->fetch();
    } catch (PDOException $e) {
        $error_msg = "Error marking order as delivered: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Customs Delivery Management - ZedAuto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            font-size: 14px;
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
            background-color: #2ecc71;
            color: white;
            cursor: pointer;
        }
        .action-buttons button:hover {
            background-color: #27ae60;
        }
        .action-buttons button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
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
        <h2>ZedAuto Customs</h2>
        <a href="customs.php">Delivery Management</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn" style="position: fixed; top: 15px; left: 15px; background-color: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; display: none;">
        ☰
    </button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Delivery Management</h1>
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
                            <th>Part Names</th>
                            <th>Customer Contact</th>
                            <th>Status</th>
                            <th>Mark as Delivered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                <td><?php echo htmlspecialchars($order['part_names'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></td>
                                <!-- Status Column with Conditional Class -->
                                <td class="status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </td>
                                <td class="action-buttons">
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="mark_delivered" <?php echo $order['status'] == 'delivered' ? 'disabled' : ''; ?>>
                                            <?php echo $order['status'] == 'delivered' ? 'Delivered' : 'Mark Delivered'; ?>
                                        </button>
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