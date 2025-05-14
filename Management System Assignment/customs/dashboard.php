<?php
// Start the session
session_start();

// Check if the user is logged in and has the customs role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customs") {
    header("location: ../../RetailSystem-LocalGarage-Login.php");
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

// Fetch summary data (total orders, pending, and delivered counts)
$sql = "SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
        FROM sales";
$stmt = $conn->prepare($sql);
$stmt->execute();
$summary = $stmt->fetch();

// Calculate percentages for the progress bar
$pending_percentage = $summary['total_orders'] > 0 ? ($summary['pending_orders'] / $summary['total_orders']) * 100 : 0;
$delivered_percentage = $summary['total_orders'] > 0 ? ($summary['delivered_orders'] / $summary['total_orders']) * 100 : 0;

// Fetch recent orders (limit to 10 for dashboard)
$sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
        FROM sales s 
        LEFT JOIN users u ON s.employee_id = u.id 
        ORDER BY s.sale_date DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        // Refresh summary data
        $sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
            FROM sales";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $summary = $stmt->fetch();
        // Recalculate percentages
        $pending_percentage = $summary['total_orders'] > 0 ? ($summary['pending_orders'] / $summary['total_orders']) * 100 : 0;
        $delivered_percentage = $summary['total_orders'] > 0 ? ($summary['delivered_orders'] / $summary['total_orders']) * 100 : 0;
        // Refresh recent orders
        $sql = "SELECT s.id, s.employee_id, u.first_name, u.last_name, s.total, s.sale_date, s.status 
                FROM sales s 
                LEFT JOIN users u ON s.employee_id = u.id 
                ORDER BY s.sale_date DESC 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Customs Dashboard - ZedAuto</title>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Customs</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="customs.php">Delivery Management</a>
        <a href="../../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn" style="position: fixed; top: 15px; left: 15px; background-color: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; display: none;">
        ☰
    </button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Customs Dashboard</h1>
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

        <!-- Progress Bar for Order Status -->
        <div class="card">
            <h3>Order Status Overview</h3>
            <div class="progress-bar">
                <div class="progress-pending" style="width: <?php echo $pending_percentage; ?>%;"></div>
                <div class="progress-delivered" style="width: <?php echo $delivered_percentage; ?>%; left: <?php echo $pending_percentage; ?>%;"></div>
            </div>
            <div class="progress-label">
                Pending: <?php echo round($pending_percentage, 1); ?>% | Delivered: <?php echo round($delivered_percentage, 1); ?>%
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="card">
            <h3>Recent Orders (Last 10)</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Employee</th>
                            <th>Total</th>
                            <th>Sale Date</th>
                            <th>Status</th>
                            <th>Mark as Delivered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
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
                                    <script>
document.querySelectorAll('.mark-delivered-form').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const orderId = form.dataset.orderId;
        const formData = new FormData(form);
        
        const response = await fetch('mark_delivered.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            form.querySelector('button').textContent = 'Delivered';
            form.querySelector('button').disabled = true;
            form.closest('tr').querySelector('.status-pending').textContent = 'Delivered';
            form.closest('tr').querySelector('.status-pending').className = 'status-delivered';
            alert(result.message);
        } else {
            alert(result.message);
        }
    });
});
</script>
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