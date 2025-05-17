<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in and has the customer role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer" || !isset($_SESSION["user_id"])) {
    echo json_encode([]);
    exit;
}

require_once "../config.php";

$customer_id = $_SESSION["id"];
$sql = "SELECT id, total, sale_date, status 
        FROM sales 
        WHERE customer_id = :customer_id 
        ORDER BY sale_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($orders);
unset($conn);
?>