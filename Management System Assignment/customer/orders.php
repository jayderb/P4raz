<?php
// Start the session
session_start();

// Debug session (log to error log for troubleshooting)
error_log("Session in orders.php: " . print_r($_SESSION, true));

// Check if the user is logged in and has the customer role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== "customer" || !isset($_SESSION["id"])) {
    error_log("Redirecting to login due to: loggedin=" . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "unset") . ", role=" . (isset($_SESSION["role"]) ? $_SESSION["role"] : "unset") . ", id=" . (isset($_SESSION["id"]) ? $_SESSION["id"] : "unset"));
    header("location: ../../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../../config.php";
} catch (PDOException $e) {
    error_log("Config error: " . $e->getMessage());
    die("Connection failed. Please contact support.");
}

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// Fetch customer email for notifications
$customer_id = $_SESSION["id"];
$sql = "SELECT email FROM users WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
$stmt->execute();
$customer = $stmt->fetch();
$customer_email = $customer['email'] ?? '';

// Fetch available parts from inventory (with stock > 0)
$sql = "SELECT id, name, price, stock FROM inventory WHERE stock > 0 ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->execute();
$parts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customer's orders
$sql = "SELECT id, total, sale_date, status 
        FROM sales 
        WHERE customer_id = :customer_id 
        ORDER BY sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for status changes and send email if approved
foreach ($orders as $order) {
    if ($order['status'] === 'approved' && !isset($_SESSION['notified_' . $order['id']])) {
        $to = $customer_email;
        $subject = "Order Approved - ZedAuto";
        $message = "Dear " . htmlspecialchars($_SESSION["first_name"]) . ",\n\nYour order (ID: " . $order['id'] . ") has been approved. Total: $" . number_format($order['total'], 2) . "\n\nThank you for choosing ZedAuto!";
        $headers = "From: no-reply@zedauto.com";
        mail($to, $subject, $message, $headers);
        $_SESSION['notified_' . $order['id']] = true; // Mark as notified
    }
}

// Handle new order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'];
    $items = [];
    $total = 0;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'quantity_') === 0 && $value > 0) {
            $part_id = substr($key, 9);
            $quantity = intval($value);
            $items[$part_id] = $quantity;

            $sql = "SELECT price, stock FROM inventory WHERE id = :part_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
            $stmt->execute();
            $part = $stmt->fetch();

            if ($part && $part['stock'] >= $quantity) {
                $total += $part['price'] * $quantity;
            } else {
                $error_msg = "Insufficient stock for part: " . $part['name'];
                break;
            }
        }
    }

    if (empty($error_msg) && $total > 0) {
        try {
            $conn->beginTransaction();
            $sale_date = date('Y-m-d H:i:s');
            $sql = "INSERT INTO sales (customer_id, total, sale_date, status) 
                    VALUES (:customer_id, :total, :sale_date, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(":total", $total, PDO::PARAM_STR);
            $stmt->bindParam(":sale_date", $sale_date, PDO::PARAM_STR);
            $stmt->execute();
            $sale_id = $conn->lastInsertId();

            foreach ($items as $part_id => $quantity) {
                if ($quantity > 0) {
                    $sql = "SELECT price, stock FROM inventory WHERE id = :part_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $part = $stmt->fetch();

                    if ($part && $part['stock'] >= $quantity) {
                        $price = $part['price'];
                        $sql = "INSERT INTO sales_items (sale_id, part_id, quantity, price) 
                                VALUES (:sale_id, :part_id, :quantity, :price)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
                        $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
                        $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
                        $stmt->bindParam(":price", $price, PDO::PARAM_STR);
                        $stmt->execute();

                        $sql = "UPDATE inventory SET stock = stock - :quantity WHERE id = :part_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
                        $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
                        $stmt->execute();
                    } else {
                        throw new Exception("Insufficient stock for part ID: $part_id");
                    }
                }
            }

            $conn->commit();
            $success_msg = "Order placed successfully with $payment_method! Status: Pending.";

            // Refresh orders data
            $sql = "SELECT id, total, sale_date, status 
                    FROM sales 
                    WHERE customer_id = :customer_id 
                    ORDER BY sale_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Refresh parts data
            $sql = "SELECT id, name, price, stock FROM inventory WHERE stock > 0 ORDER BY name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $conn->rollBack();
            $error_msg = "Error placing order: " . $e->getMessage();
        }
    } elseif (empty($error_msg)) {
        $error_msg = "Please select at least one part to order.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ZedAuto</title>
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
        .notifications {
            position: relative;
            margin-left: 20px;
        }
        .notification-bell {
            cursor: pointer;
            font-size: 20px;
            color: #3498db;
        }
        .notification-panel {
            display: none;
            position: absolute;
            top: 30px;
            right: 0;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 300px;
            z-index: 1000;
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-panel.active {
            display: block;
        }
        .notification {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .notification.approved {
            color: #3498db;
        }
        .notification.delivered {
            color: #27ae60;
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
        .parts-table, .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .parts-table th, .parts-table td, .orders-table th, .orders-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .parts-table th, .orders-table th {
            background-color: #3498db;
            color: white;
        }
        .parts-table tr:hover, .orders-table tr:hover {
            background-color: #f1f1f1;
        }
        .order-form select, .order-form button, .quantity-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .order-form button {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }
        .order-form button:hover {
            background-color: #2980b9;
        }
        .status-pending {
            color: #e67e22;
            font-weight: bold;
        }
        .status-approved {
            color: #3498db;
            font-weight: bold;
        }
        .status-delivered {
            color: #27ae60;
            font-weight: bold;
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
        <h2>ZedAuto Customer</h2>
        <a href="orders.php">My Orders</a>
        <a href="../../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn" style="position: fixed; top: 15px; left: 15px; background-color: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; display: none;">
        ☰
    </button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>My Orders</h1>
            <div class="header-right">
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span>Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                </div>
                <div class="notifications">
                    <span class="notification-bell" onclick="toggleNotifications()">🔔</span>
                    <div id="notification-panel" class="notification-panel">
                        <?php
                        $sql = "SELECT id, status FROM sales WHERE customer_id = :customer_id AND status IN ('approved', 'delivered') ORDER BY sale_date DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
                        $stmt->execute();
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($notifications as $notification) {
                            $class = $notification['status'];
                            echo "<div class='notification $class'>Order ID " . $notification['id'] . " is " . $notification['status'] . "!</div>";
                        }
                        ?>
                    </div>
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

        <!-- Parts Selection and Order Placement -->
        <div class="card">
            <h3>Place a New Order</h3>
            <form method="post" class="order-form">
                <table class="parts-table">
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parts as $part): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($part['name']); ?></td>
                                <td>$<?php echo number_format($part['price'], 2); ?></td>
                                <td><?php echo $part['stock']; ?></td>
                                <td>
                                    <input type="number" name="quantity_<?php echo $part['id']; ?>" class="quantity-input" min="0" max="<?php echo $part['stock']; ?>" value="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cod">Cash on Delivery</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                    <button type="submit" name="place_order">Place Order</button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <h3>My Orders</h3>
            <div style="overflow-x: auto;">
                <table id="orders-table" class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Sale Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($order['sale_date'])); ?></td>
                                <td class="status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript for Notifications and Real-Time Updates -->
    <script>
        // Toggle Notification Panel
        function toggleNotifications() {
            const panel = document.getElementById('notification-panel');
            panel.classList.toggle('active');
        }

        // Real-Time Orders Update via AJAX
        function updateOrders() {
            const customerId = <?php echo json_encode($_SESSION["id"]); ?>;
            fetch(`orders_update.php?customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.querySelector('#orders-table tbody');
                    tbody.innerHTML = '';
                    data.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${order.id}</td>
                            <td>$${Number(order.total).toFixed(2)}</td>
                            <td>${order.sale_date}</td>
                            <td class="status-${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Update notifications
                    fetch(`notifications_update.php?customer_id=${customerId}`)
                        .then(response => response.json())
                        .then(notifications => {
                            const panel = document.getElementById('notification-panel');
                            panel.innerHTML = '';
                            notifications.forEach(notif => {
                                const div = document.createElement('div');
                                div.className = `notification ${notif.status}`;
                                div.textContent = `Order ID ${notif.id} is ${notif.status}!`;
                                panel.appendChild(div);
                            });
                        });
                })
                .catch(error => console.error('Error updating orders:', error));
        }

        // Poll every 10 seconds
        setInterval(updateOrders, 10000);
        updateOrders(); // Initial call

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