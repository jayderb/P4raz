<?php
// Start output buffering
ob_start();

// Enable debug mode (set to false in production)
$debug_mode = true;

// Set error reporting (disable display for AJAX to prevent HTML output)
ini_set('display_errors', $debug_mode && empty($_SERVER['HTTP_X_REQUESTED_WITH']) ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log'); // Adjust path as needed
error_reporting(E_ALL);

// Start the session
session_start();

// Debug session
error_log("[" . date('Y-m-d H:i:s') . "] Session in dashboard.php: " . print_r($_SESSION, true));

// Check if the user is logged in as a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer" || !isset($_SESSION["user_id"]) || !is_numeric($_SESSION["user_id"])) {
    error_log("[" . date('Y-m-d H:i:s') . "] Redirecting to login due to invalid session");
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        ob_end_flush();
        exit;
    }
    header("location: ../RetailSystem-Customer-Login.php");
    ob_end_flush();
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Config error: " . $e->getMessage());
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        ob_end_flush();
        exit;
    }
    die("Connection failed: " . $e->getMessage());
}

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize variables for messages
$review_success_msg = '';
$review_error_msg = '';
$success_msg = '';
$error_msg = '';
$profile_success_msg = '';
$profile_error_msg = '';
$settings_success_msg = '';
$settings_error_msg = '';

// Handle review form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $customer_id = $_SESSION['user_id'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    // Validate input
    if ($rating < 1 || $rating > 5) {
        $review_error_msg = "Please select a valid rating (1-5 stars).";
    } elseif (empty($comment)) {
        $review_error_msg = "Please provide a comment for your review.";
    } else {
        try {
            $sql = "INSERT INTO reviews (customer_id, rating, comment) VALUES (:customer_id, :rating, :comment)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(":rating", $rating, PDO::PARAM_INT);
            $stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
            $stmt->execute();
            $review_success_msg = "Review submitted successfully!";
            $response = ['success' => true, 'message' => $review_success_msg];
        } catch (PDOException $e) {
            $review_error_msg = "Error submitting review: " . $e->getMessage();
            $response = ['success' => false, 'message' => $review_error_msg];
            error_log("[" . date('Y-m-d H:i:s') . "] Review error: " . $e->getMessage());
        }
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in review: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        }
    }
}

// Handle profile form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $customer_id = $_SESSION['user_id'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    // Validate input
    if (empty($first_name) || empty($last_name)) {
        $profile_error_msg = "First name and last name are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error_msg = "Invalid email address.";
    } elseif (empty($phone) || !preg_match("/^[0-9]{10,15}$/", $phone)) {
        $profile_error_msg = "Invalid phone number. Must be 10-15 digits.";
    } else {
        try {
            // Check if email is already in use by another user
            $sql = "SELECT id FROM users WHERE email = :email AND id != :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                $profile_error_msg = "Email is already in use by another account.";
            } else {
                $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone WHERE id = :id AND role = 'customer'";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
                $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);
                $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    // Update session variables
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['phone'] = $phone;
                    $profile_success_msg = "Profile updated successfully!";
                    $response = ['success' => true, 'message' => $profile_success_msg, 'first_name' => $first_name];
                    error_log("[" . date('Y-m-d H:i:s') . "] Profile updated for user_id=$customer_id");
                } else {
                    $profile_error_msg = "No changes made to profile.";
                    $response = ['success' => false, 'message' => $profile_error_msg];
                }
            }
        } catch (PDOException $e) {
            $profile_error_msg = "Error updating profile: " . $e->getMessage();
            $response = ['success' => false, 'message' => $profile_error_msg];
            error_log("[" . date('Y-m-d H:i:s') . "] Profile update error: " . $e->getMessage());
        }
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in update_profile: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        }
    }
}

// Handle settings form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $receive_notifications = isset($_POST['receive_notifications']) ? 1 : 0;
    $customer_id = $_SESSION['user_id'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    try {
        // Fetch current user data
        $sql = "SELECT password FROM users WHERE id = :id AND role = 'customer'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        // Validate inputs
        $update_needed = false;
        if (!empty($new_password) || !empty($current_password)) {
            if (empty($current_password)) {
                $settings_error_msg = "Current password is required to change password.";
            } elseif (!password_verify($current_password, $user['password'])) {
                $settings_error_msg = "Current password is incorrect.";
            } elseif (strlen($new_password) < 8) {
                $settings_error_msg = "New password must be at least 8 characters long.";
            } elseif ($new_password !== $confirm_password) {
                $settings_error_msg = "New password and confirmation do not match.";
            } else {
                $update_needed = true;
            }
        }

        // Always allow notification preference update
        $sql = "UPDATE users SET receive_notifications = :receive_notifications";
        $params = [":receive_notifications" => $receive_notifications, ":id" => $customer_id];
        if ($update_needed) {
            $sql .= ", password = :password";
            $params[":password"] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE id = :id AND role = 'customer'";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $settings_success_msg = "Settings updated successfully!";
            $response = ['success' => true, 'message' => $settings_success_msg];
            error_log("[" . date('Y-m-d H:i:s') . "] Settings updated for user_id=$customer_id");
        } else {
            $settings_error_msg = "No changes made to settings.";
            $response = ['success' => false, 'message' => $settings_error_msg];
        }
    } catch (PDOException $e) {
        $settings_error_msg = "Error updating settings: " . $e->getMessage();
        $response = ['success' => false, 'message' => $settings_error_msg];
        error_log("[" . date('Y-m-d H:i:s') . "] Settings update error: " . $e->getMessage());
    }
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        if (ob_get_length()) {
            error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in update_settings: " . ob_get_contents());
            ob_clean();
        }
        echo json_encode($response);
        ob_end_flush();
        exit;
    }
}

// Fetch products from the database
try {
    $sql = "SELECT part_id as id, part_name as name, unit_price as price, quantity as stock, 
                   description, category 
            FROM inventory 
            WHERE quantity > 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("[" . date('Y-m-d H:i:s') . "] Fetched " . count($products) . " inventory items");
} catch (PDOException $e) {
    $error_msg = "Error fetching inventory: " . $e->getMessage();
    error_log("[" . date('Y-m-d H:i:s') . "] Fetch inventory error: " . $e->getMessage());
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $error_msg]);
        ob_end_flush();
        exit;
    }
}

// Process adding items to the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    error_log("[" . date('Y-m-d H:i:s') . "] Adding to cart: user_id={$_SESSION['user_id']}, product_id=$product_id, quantity=$quantity, isAjax=$isAjax");

    try {
        // Validate product ID and quantity
       $product_exists = false;
$selected_product = null;
foreach ($products as $product) {
    if ($product['id'] == $product_id && $product['stock'] >= $quantity && $quantity > 0) {
        $product_exists = true;
        $selected_product = [
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price'],
            'part_id' => $product['id'] // Add this line
        ];
        break;
    }
}


        if ($product_exists) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'quantity' => $quantity,
            'name' => $selected_product['name'],
            'price' => $selected_product['price'],
            'part_id' => $selected_product['part_id']
        ];
    }
    
    // Debug output
    error_log("Cart after addition: " . print_r($_SESSION['cart'], true));
    
    $success_msg = "Part added to cart successfully!";
    $response = [
        'success' => true,
        'message' => $success_msg,
        'cart' => $_SESSION['cart']
    ];
}

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in add_to_cart: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: dashboard.php?" . ($product_exists ? "success=" . urlencode($success_msg) : "error=" . urlencode($error_msg)));
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        $error_msg = "Error adding to cart: " . $e->getMessage();
        $response = ['success' => false, 'message' => $error_msg];
        error_log("[" . date('Y-m-d H:i:s') . "] Cart error: " . $e->getMessage());
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in add_to_cart error: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: dashboard.php?error=" . urlencode($error_msg));
            ob_end_flush();
            exit;
        }
    }
}

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    error_log("[" . date('Y-m-d H:i:s') . "] Starting checkout: user_id={$_SESSION['user_id']}, isAjax=$isAjax");

    try {
        if (empty($_SESSION['cart'])) {
            throw new Exception("Cart is empty.");
        }

        $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
        $customer_phone = trim($_POST['customer_phone']);

        if (!$customer_email) {
            throw new Exception("Invalid email address.");
        }
        if (empty($customer_phone) || !preg_match("/^[0-9]{10,15}$/", $customer_phone)) {
            throw new Exception("Invalid phone number. Must be 10-15 digits.");
        }

        $conn->beginTransaction();

        $customer_id = $_SESSION['user_id'];
        $total = 0;
        foreach ($_SESSION['cart'] as $part_id => $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Insert sale
        $sql = "INSERT INTO sales (customer_id, total, sale_date, status) VALUES (:customer_id, :total, NOW(), 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(":total", $total, PDO::PARAM_STR);
        $stmt->execute();
        $sale_id = $conn->lastInsertId();
        error_log("[" . date('Y-m-d H:i:s') . "] Sale created: sale_id=$sale_id");

        // Insert sale items
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $sql = "INSERT INTO sales_items (sale_id, product_id, quantity, price) 
                    VALUES (:sale_id, :product_id, :quantity, :price)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
            $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
            $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(":price", $item['price'], PDO::PARAM_STR);
            $stmt->execute();
            error_log("[" . date('Y-m-d H:i:s') . "] Sales item added: sale_id=$sale_id, product_id=$product_id");

            // Update stock
            $sql = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $rows_affected = $stmt->rowCount();
            error_log("[" . date('Y-m-d H:i:s') . "] Stock updated: product_id=$product_id, rows_affected=$rows_affected");
        }

        $conn->commit();
        $_SESSION['cart'] = [];
        $success_msg = "Purchase completed successfully! Awaiting approval.";
        $response = ['success' => true, 'message' => $success_msg];

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in checkout: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: dashboard.php?success=" . urlencode($success_msg));
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error_msg = "Failed to process purchase: " . $e->getMessage();
        $response = ['success' => false, 'message' => $error_msg];
        error_log("[" . date('Y-m-d H:i:s') . "] Checkout error: " . $e->getMessage());
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in checkout error: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: dashboard.php?error=" . urlencode($error_msg));
            ob_end_flush();
            exit;
        }
    }
}

// Fetch customer profile data for overview and profile section
try {
    $sql = "SELECT first_name, last_name, email, phone, created_at, receive_notifications FROM users WHERE id = :id AND role = 'customer'";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch();
    if (!$profile) {
        throw new Exception("User profile not found.");
    }
    // Set session email and phone if not already set
    $_SESSION['email'] = $_SESSION['email'] ?? $profile['email'];
    $_SESSION['phone'] = $_SESSION['phone'] ?? $profile['phone'];
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Profile error: " . $e->getMessage());
    $error_msg = "Error fetching profile: " . $e->getMessage();
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $error_msg]);
        ob_end_flush();
        exit;
    }
}

// Fetch recent orders for overview
try {
    $sql = "SELECT id, total, sale_date 
            FROM sales 
            WHERE customer_id = :customer_id 
            ORDER BY sale_date DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $last_order = $stmt->fetch();
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Last order error: " . $e->getMessage());
    $error_msg = "Error fetching last order: " . $e->getMessage();
}

// Fetch order count
try {
    $sql = "SELECT COUNT(*) as order_count 
            FROM sales 
            WHERE customer_id = :customer_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $order_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Order count error: " . $e->getMessage());
    $error_msg = "Error fetching order count: " . $e->getMessage();
}

// Fetch total spent
try {
    $sql = "SELECT COALESCE(SUM(total), 0) as total_spent 
            FROM sales 
            WHERE customer_id = :customer_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $total_spent = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Total spent error: " . $e->getMessage());
    $error_msg = "Error fetching total spent: " . $e->getMessage();
}

// Flush output buffer before HTML if no AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    ob_end_flush();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - ZedAuto</title>
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
        .table-container {
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
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .metric-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border: 1px solid #ecf0f1;
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message.success {
            background: #e0f7fa;
            color: #006064;
        }
        .message.error {
            background: #ffebee;
            color: #b71c1c;
        }
        .rating-stars input {
            display: none;
        }
        .rating-stars label {
            font-size: 1.5rem;
            color: #d3d3d3;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #f1c40f;
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
        .tab {
            cursor: pointer;
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }
        .tab.active {
            border-bottom: 2px solid #3498db;
            color: #3498db;
            font-weight: 600;
        }
        .tab:hover {
            color: #3498db;
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
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="dashboard.php" class="text-2xl font-bold text-white">ZedAuto</a>
            <button id="hamburger" class="md:hidden text-white text-2xl focus:outline-none" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav id="nav-menu" class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition">Home</a>
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
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition">Home</a>
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
                <h2 class="sr-only">Customer Navigation</h2>
                <nav>
                    <a href="dashboard.php">Home</a>
                    <a href="stock-form.php">Browse Products</a>
                    <a href="orders.php">My Orders</a>
                    <a href="dashboard.php" class="text-yellow-400 hover:text-yellow-500">Dashboard</a>
                    <a href="profile.php">Profile</a>
                    <a href="settings.php">Settings</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Home</a>
                <a href="stock-form.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Browse Products</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">My Orders</a>
                <a href="#profile-settings" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Profile & Settings</a>
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
                    Customer Dashboard
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <span id="welcome-name" class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                </div>
            </div>
            <!-- Metrics -->
            <div class="metrics grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="metric-card card p-6 text-center">
                    <h3 class="text-lg font-semibold text-[#2c3e50] mb-2">Total Orders</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $order_count; ?></p>
                    <span class="text-sm text-[#7f8c8d]">Your total purchases</span>
                </div>
                <div class="metric-card card p-6 text-center">
                    <h3 class="text-lg font-semibold text-[#2c3e50] mb-2">Total Spent</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]">ZMK<?php echo number_format($total_spent, 2); ?></p>
                    <span class="text-sm text-[#7f8c8d]">Lifetime spending</span>
                </div>
                <div class="metric-card card p-6 text-center">
                    <h3 class="text-lg font-semibold text-[#2c3e50] mb-2">Last Order</h3>
                    <p class="text-2xl font-bold text-[#2c3e50]"><?php echo $last_order ? date('M d, Y', strtotime($last_order['sale_date'])) : 'None'; ?></p>
                    <span class="text-sm text-[#7f8c8d]">Most recent order</span>
                </div>
            </div>
            <!-- Purchase Section -->
            <div class="table-container card mb-8">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Make a Purchase</h2>
                <!-- Success/Error Messages -->
                <div id="message-container">
                    <?php if (!empty($success_msg)): ?>
                        <div class="message success"><?php echo htmlspecialchars($success_msg); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_msg)): ?>
                        <div class="message error"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php endif; ?>
                </div>
                <!-- Product List -->
                <h3 class="text-lg font-semibold text-[#2c3e50] mb-4">Available Products</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full">
    <thead>
        <tr>
            <th class="px-4 py-2 rounded-tl-md">Part Name</th>
            <th class="px-4 py-2">Category</th>
            <th class="px-4 py-2">Description</th>
            <th class="px-4 py-2">Price (ZMK)</th>
            <th class="px-4 py-2">In Stock</th>
            <th class="px-4 py-2 rounded-tr-md">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="6" class="text-center py-4 text-[#2c3e50]">No inventory items available.</td></tr>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($product['name']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($product['description'] ?? 'No description'); ?></td>
                    <td class="px-4 py-2"><?php echo number_format($product['price'], 2); ?></td>
                    <td class="px-4 py-2"><?php echo $product['stock']; ?></td>
                    <td class="px-4 py-2">
                        <form method="post" class="add-to-cart-form flex items-center gap-2" data-product-id="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="quantity" min="1" max="<?php echo $product['stock']; ?>" 
                                   value="1" class="px-2 py-1 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] w-16 focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                            <button type="submit" name="add_to_cart" class="px-4 py-2 bg-[#3498db] text-white rounded-md hover:bg-[#2980b9] transition">
                                Add to Cart
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
                </div>
                <!-- Cart -->
                <h3 class="text-lg font-semibold text-[#2c3e50] mt-6 mb-4">Your Cart</h3>
                <div id="cart-container">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <p class="text-[#2c3e50] text-center">Your cart is empty.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table w-full mb-4">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 rounded-tl-md">Product Name</th>
                                        <th class="px-4 py-2">Price (ZMK)</th>
                                        <th class="px-4 py-2">Quantity</th>
                                        <th class="px-4 py-2 rounded-tr-md">Subtotal (ZMK)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $cart_total = 0;
                                    foreach ($_SESSION['cart'] as $product_id => $item): 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $cart_total += $subtotal;
                                    ?>
                                        <tr>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td class="px-4 py-2"><?php echo number_format($item['price'], 2); ?></td>
                                            <td class="px-4 py-2"><?php echo $item['quantity']; ?></td>
                                            <td class="px-4 py-2"><?php echo number_format($subtotal, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[#2c3e50] font-bold mb-4">Total: ZMK<?php echo number_format($cart_total, 2); ?></p>
                        <form method="post" id="checkout-form">
                            <div class="mb-4">
                                <label for="customer_email" class="block text-[#2c3e50] mb-1">Confirm Email:</label>
                                <input type="email" name="customer_email" id="customer_email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required class="w-full px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                <label for="customer_phone" class="block text-[#2c3e50] mb-1 mt-2">Confirm Phone Number:</label>
                                <input type="text" name="customer_phone" id="customer_phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required pattern="[0-9]{10,15}" title="Please enter a phone number with 10 to 15 digits" class="w-full px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                            </div>
                            <button type="submit" name="checkout" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Checkout</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Review Submission Form -->
            <div class="table-container card mb-8">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Submit a Review for ZedAuto</h2>
                <div id="review-message-container">
                    <?php if (!empty($review_success_msg)): ?>
                        <div class="message success"><?php echo htmlspecialchars($review_success_msg); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($review_error_msg)): ?>
                        <div class="message error"><?php echo htmlspecialchars($review_error_msg); ?></div>
                    <?php endif; ?>
                </div>
                <form method="post" id="review-form" class="flex flex-col gap-4">
                    <div class="rating-stars flex justify-center gap-2">
                        <input type="radio" name="rating" id="star5" value="5" required>
                        <label for="star5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star4" value="4">
                        <label for="star4"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star3" value="3">
                        <label for="star3"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star2" value="2">
                        <label for="star2"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star1" value="1">
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                    <textarea name="comment" rows="4" placeholder="Write your review here..." class="px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none focus:ring-2 focus:ring-[#3498db] w-full" required></textarea>
                    <button type="submit" name="submit_review" class="px-4 py-2 bg-[#3498db] text-white rounded-md hover:bg-[#2980b9] transition">Submit Review</button>
                </form>
            </div>
        </section>
        </div>
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
        // Handle Add to Cart Form Submission
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.add-to-cart-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const productId = form.querySelector('input[name="product_id"]').value;
                    const quantity = form.querySelector('input[name="quantity"]').value;
                    if (!confirm(`Are you sure you want to add ${quantity} product(s) (ID: ${productId}) to your cart?`)) {
                        return;
                    }
                    const formData = new FormData(form);
                    console.log(`Submitting add to cart for product_id: ${productId}, quantity: ${quantity}`);
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            const text = await response.text();
                            console.error('Non-JSON response:', text.substring(0, 500));
                            alert('Error: Unable to add to cart. Check console for details.');
                            return;
                        }
                        const result = await response.json();
                        console.log('Add to cart response:', result);
                        const messageContainer = document.getElementById('message-container');
                        messageContainer.innerHTML = `<div class="message ${result.success ? 'success' : 'error'}">${result.message}</div>`;
                        if (result.success) {
                            const button = form.querySelector('button');
                            button.textContent = 'Added';
                            button.classList.remove('bg-[#3498db]', 'hover:bg-[#2980b9]');
                            button.classList.add('bg-[#7f8c8d]', 'cursor-not-allowed');
                            button.disabled = true;
                            // Update cart UI
                            const cartContainer = document.getElementById('cart-container');
                            if (result.cart && Object.keys(result.cart).length > 0) {
                                let cartHtml = `
                                    <div class="overflow-x-auto">
                                        <table class="table w-full mb-4">
                                            <thead>
                                                <tr>
                                                    <th class="px-4 py-2 rounded-tl-md">Product Name</th>
                                                    <th class="px-4 py-2">Price (ZMK)</th>
                                                    <th class="px-4 py-2">Quantity</th>
                                                    <th class="px-4 py-2 rounded-tr-md">Subtotal (ZMK)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                `;
                                let cartTotal = 0;
                                for (const [productId, item] of Object.entries(result.cart)) {
                                    const subtotal = item.price * item.quantity;
                                    cartTotal += subtotal;
                                    cartHtml += `
                                        <tr>
                                            <td class="px-4 py-2">${item.name}</td>
                                            <td class="px-4 py-2">${parseFloat(item.price).toFixed(2)}</td>
                                            <td class="px-4 py-2">${item.quantity}</td>
                                            <td class="px-4 py-2">${subtotal.toFixed(2)}</td>
                                        </tr>
                                    `;
                                }
                                cartHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-[#2c3e50] font-bold mb-4">Total: ZMK${cartTotal.toFixed(2)}</p>
                                    <form method="post" id="checkout-form">
                                        <div class="mb-4">
                                            <label for="customer_email" class="block text-[#2c3e50] mb-1">Confirm Email:</label>
                                            <input type="email" name="customer_email" id="customer_email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required class="w-full px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                            <label for="customer_phone" class="block text-[#2c3e50] mb-1 mt-2">Confirm Phone Number:</label>
                                            <input type="text" name="customer_phone" id="customer_phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required pattern="[0-9]{10,15}" title="Please enter a phone number with 10 to 15 digits" class="w-full px-3 py-2 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                        </div>
                                        <button type="submit" name="checkout" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Checkout</button>
                                    </form>
                                `;
                                cartContainer.innerHTML = cartHtml;
                            } else {
                                cartContainer.innerHTML = '<p class="text-[#2c3e50] text-center">Your cart is empty.</p>';
                            }
                            // Reattach checkout form listener
                            attachCheckoutListener();
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Error: Unable to add to cart. Please try again or contact support.');
                    }
                });
            });
            // Handle Checkout Form Submission
            function attachCheckoutListener() {
                const checkoutForm = document.getElementById('checkout-form');
                if (checkoutForm) {
                    checkoutForm.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        if (!confirm('Are you sure you want to complete this purchase?')) {
                            return;
                        }
                        const formData = new FormData(checkoutForm);
                        console.log('Submitting checkout');
                        try {
                            const response = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (!response.ok) {
                                const text = await response.text();
                                console.error('Non-JSON response:', text.substring(0, 500));
                                alert('Error: Unable to process checkout. Check console for details.');
                                return;
                            }
                            const result = await response.json();
                            console.log('Checkout response:', result);
                            const messageContainer = document.getElementById('message-container');
                            messageContainer.innerHTML = `<div class="message ${result.success ? 'success' : 'error'}">${result.message}</div>`;
                            if (result.success) {
                                const cartContainer = document.getElementById('cart-container');
                                cartContainer.innerHTML = '<p class="text-[#2c3e50] text-center">Your cart is empty.</p>';
                                alert(result.message);
                                location.reload(); // Refresh to update metrics
                            } else {
                                alert(result.message);
                            }
                        } catch (error) {
                            console.error('Fetch error:', error);
                            alert('Error: Unable to process checkout. Please try again or contact support.');
                        }
                    });
                }
            }
            attachCheckoutListener();
            // Handle Review Form Submission
            const reviewForm = document.getElementById('review-form');
            if (reviewForm) {
                reviewForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const rating = reviewForm.querySelector('input[name="rating"]:checked')?.value;
                    const comment = reviewForm.querySelector('textarea[name="comment"]').value;
                    if (!rating || !comment) {
                        alert('Please select a rating and provide a comment.');
                        return;
                    }
                    const formData = new FormData(reviewForm);
                    console.log('Submitting review');
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            const text = await response.text();
                            console.error('Non-JSON response:', text.substring(0, 500));
                            alert('Error: Unable to submit review. Check console for details.');
                            return;
                        }
                        const result = await response.json();
                        console.log('Review response:', result);
                        const reviewMessageContainer = document.getElementById('review-message-container');
                        reviewMessageContainer.innerHTML = `<div class="message ${result.success ? 'success' : 'error'}">${result.message}</div>`;
                        if (result.success) {
                            reviewForm.reset();
                            alert(result.message);
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Error: Unable to submit review. Please try again or contact support.');
                    }
                });
            }
            // Handle Profile Form Submission
            const profileForm = document.getElementById('profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to update your profile?')) {
                        return;
                    }
                    const formData = new FormData(profileForm);
                    console.log('Submitting profile update');
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            const text = await response.text();
                            console.error('Non-JSON response:', text.substring(0, 500));
                            alert('Error: Unable to update profile. Check console for details.');
                            return;
                        }
                        const result = await response.json();
                        console.log('Profile update response:', result);
                        const profileMessageContainer = document.getElementById('profile-message-container');
                        profileMessageContainer.innerHTML = `<div class="message ${result.success ? 'success' : 'error'}">${result.message}</div>`;
                        if (result.success) {
                            // Update welcome message
                            const welcomeName = document.getElementById('welcome-name');
                            welcomeName.textContent = `Hi, ${result.first_name}!`;
                            alert(result.message);
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Error: Unable to update profile. Please try again or contact support.');
                    }
                });
            }
            // Handle Settings Form Submission
            const settingsForm = document.getElementById('settings-form');
            if (settingsForm) {
                settingsForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to update your settings?')) {
                        return;
                    }
                    const formData = new FormData(settingsForm);
                    console.log('Submitting settings update');
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) {
                            const text = await response.text();
                            console.error('Non-JSON response:', text.substring(0, 500));
                            alert('Error: Unable to update settings. Check console for details.');
                            return;
                        }
                        const result = await response.json();
                        console.log('Settings update response:', result);
                        const settingsMessageContainer = document.getElementById('settings-message-container');
                        settingsMessageContainer.innerHTML = `<div class="message ${result.success ? 'success' : 'error'}">${result.message}</div>`;
                        if (result.success) {
                            settingsForm.reset();
                            alert(result.message);
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Error: Unable to update settings. Please try again or contact support.');
                    }
                });
            }
            // Tab Switching
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.add('hidden'));
                    tab.classList.add('active');
                    document.getElementById(`${tab.dataset.tab}-tab`).classList.remove('hidden');
                });
            });
        });
    </script>
<?php
// Close connection
unset($conn);
?>
</body>
</html>