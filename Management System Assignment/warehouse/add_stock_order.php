<?php
session_start();
require_once '../db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION["role"] === 'warehouse') {
    $conn = connectionToDatabase();
    $action = $_POST['action'];

    if ($action === 'add_stock') {
        // Add new stock
        $part_name = trim($_POST['part_name']);
        $quantity = (int)$_POST['quantity'];
        $unit_price = (float)$_POST['unit_price'];
        $warehouse_id = (int)$_POST['warehouse_id'];

        // Get next part_id
        $result = $conn->query("SELECT MAX(part_id) AS max_id FROM inventory");
        $row = $result->fetch_assoc();
        $part_id = ($row['max_id'] ?? 0) + 1;

        // Insert into inventory
        $stmt = $conn->prepare("INSERT INTO inventory (part_id, warehouse_id, part_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisid", $part_id, $warehouse_id, $part_name, $quantity, $unit_price);
        if ($stmt->execute()) {
            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (warehouse_id, action, details) VALUES (?, 'Stock Added', ?)");
            $details = "Added $quantity $part_name (Part ID: $part_id) to warehouse $warehouse_id";
            $stmt->bind_param("is", $warehouse_id, $details);
            $stmt->execute();
            $_SESSION['success'] = "Stock added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add stock.";
        }
        $stmt->close();
    } elseif ($action === 'add_order') {
        // Add new order
        $customer_id = (int)$_POST['customer_id'];
        $part_id = (int)$_POST['part_id'];
        $quantity = (int)$_POST['quantity'];
        $warehouse_id = (int)$_POST['warehouse_id'];

        // Verify part_id exists
        $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE part_id = ? AND warehouse_id = ?");
        $stmt->bind_param("ii", $part_id, $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Invalid Part ID for this warehouse.";
            $stmt->close();
            $conn->close();
            header("location: dashboard.php");
            exit;
        }
        $stock = $result->fetch_assoc();
        if ($stock['quantity'] < $quantity) {
            $_SESSION['error'] = "Insufficient stock for Part ID $part_id.";
            $stmt->close();
            $conn->close();
            header("location: dashboard.php");
            exit;
        }
        $stmt->close();

        // Insert into dispatch_orders
        $stmt = $conn->prepare("INSERT INTO dispatch_orders (warehouse_id, customer_id, part_id, quantity, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiii", $warehouse_id, $customer_id, $part_id, $quantity);
        if ($stmt->execute()) {
            // Update inventory quantity
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE part_id = ? AND warehouse_id = ?");
            $stmt->bind_param("iii", $quantity, $part_id, $warehouse_id);
            $stmt->execute();

            // Log activity
            $stmt = $conn->prepare("INSERT INTO activity_logs (warehouse_id, action, details) VALUES (?, 'Order Added', ?)");
            $details = "Order for $quantity of Part ID $part_id for customer $customer_id in warehouse $warehouse_id";
            $stmt->bind_param("is", $warehouse_id, $details);
            $stmt->execute();
            $_SESSION['success'] = "Order added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add order.";
        }
        $stmt->close();
    }

    $conn->close();
    header("location: dashboard.php");
    exit;
}
header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
exit;
?>