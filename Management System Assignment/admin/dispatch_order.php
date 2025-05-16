<?php
session_start();
require_once 'db_connection.php';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && $_SESSION["role"] === 'warehouse') {
    $conn = connectionToDatabase();
    $stmt = $conn->prepare("UPDATE dispatch_orders SET status = 'dispatched' WHERE order_id = ? AND warehouse_id = ?");
    $stmt->bind_param("ii", $_POST['order_id'], $_SESSION['warehouse_id']);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO activity_logs (warehouse_id, action, details) VALUES (?, 'Order Dispatched', ?)");
    $details = "Order ID " . $_POST['order_id'] . " dispatched";
    $stmt->bind_param("is", $_SESSION['warehouse_id'], $details);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    header("location: dashboard.php");
    exit;
}
header("location: Retail System-Warehouse-Login.php?error=unauthorized_access");
exit;
?>