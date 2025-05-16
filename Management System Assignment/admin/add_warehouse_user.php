<?php
session_start();
require_once '../db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION["role"] === 'manager') {
    $email = trim($_POST['email']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $phone = trim($_POST['phone']);
    $warehouse_id = (int)$_POST['warehouse_id'];

    $conn = connectionToDatabase();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists.";
        $stmt->close();
        $conn->close();
        header("location: warehouse.php");
        exit;
    }
    $stmt->close();

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, phone, warehouse_id) VALUES (?, ?, ?, ?, 'warehouse', ?, ?)");
    $stmt->bind_param("sssssi", $email, $password, $first_name, $last_name, $phone, $warehouse_id);
    if ($stmt->execute()) {
        // Log activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (warehouse_id, action, details) VALUES (?, 'User Added', ?)");
        $details = "Admin added warehouse user: $email";
        $stmt->bind_param("is", $warehouse_id, $details);
        $stmt->execute();
        $_SESSION['success'] = "Warehouse user added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add user.";
    }
    $stmt->close();
    $conn->close();
    header("location: warehouse.php");
    exit;
}
header("location: ../Retail System-Warehouse-Login.php?error=unauthorized_access");
exit;
?>