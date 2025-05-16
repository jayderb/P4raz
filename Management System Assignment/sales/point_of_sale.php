<?php
// Start the session
session_start();

// Check if the user is logged in and has the employee role
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

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch products from the database
$sql = "SELECT id, name, price, stock FROM products WHERE stock > 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process adding items to the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Validate product ID and quantity
    $product_exists = false;
    foreach ($products as $product) {
        if ($product['id'] == $product_id && $product['stock'] >= $quantity && $quantity > 0) {
            $product_exists = true;
            break;
        }
    }

    if ($product_exists) {
        // Add to cart or update quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'quantity' => $quantity,
                'name' => $product['name'],
                'price' => $product['price']
            ];
        }
    } else {
        $error = "Invalid product or insufficient stock.";
    }
}

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        // Validate customer details
        $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
        $customer_phone = trim($_POST['customer_phone']);

        if (!$customer_email) {
            $error = "Invalid email address.";
        } elseif (empty($customer_phone) || !preg_match("/^[0-9]{10,15}$/", $customer_phone)) {
            $error = "Invalid phone number. Must be 10-15 digits.";
        } else {
            try {
                // Start a transaction
                $conn->beginTransaction();

                // Check if customer exists with exact email and role 'customer'
                $sql = "SELECT id FROM users WHERE email = :email AND role = 'customer'";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":email", $customer_email, PDO::PARAM_STR);
                $stmt->execute();
                $customer = $stmt->fetch();

                if ($customer) {
                    $customer_id = $customer['id'];
                    // Update phone number if it has changed
                    $sql = "UPDATE users SET phone = :phone WHERE id = :customer_id AND role = 'customer'";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":phone", $customer_phone, PDO::PARAM_STR);
                    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    // Check if email exists with a different role
                    $sql = "SELECT id FROM users WHERE email = :email";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":email", $customer_email, PDO::PARAM_STR);
                    $stmt->execute();
                    if ($stmt->fetch()) {
                        $error = "Email is registered with a different role. Please use a different email.";
                        $conn->rollBack();
                    } else {
                        // Create new customer
                        $sql = "INSERT INTO users (email, phone, role) VALUES (:email, :phone, 'customer')";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":email", $customer_email, PDO::PARAM_STR);
                        $stmt->bindParam(":phone", $customer_phone, PDO::PARAM_STR);
                        $stmt->execute();
                        $customer_id = $conn->lastInsertId();
                    }
                }

                if (!isset($error)) {
                    // Calculate total
                    $total = 0;
                    foreach ($_SESSION['cart'] as $product_id => $item) {
                        $total += $item['price'] * $item['quantity'];
                    }

                    // Insert into sales table with customer_id
                    $sql = "INSERT INTO sales (employee_id, customer_id, total, sale_date) 
                            VALUES (:employee_id, :customer_id, :total, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":employee_id", $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
                    $stmt->bindParam(":total", $total, PDO::PARAM_STR);
                    $stmt->execute();
                    $sale_id = $conn->lastInsertId();

                    // Insert into sale_items table and update stock
                    foreach ($_SESSION['cart'] as $product_id => $item) {
                        // Insert sale item
                        $sql = "INSERT INTO sales_items (sale_id, product_id, quantity, price) 
                                VALUES (:sale_id, :product_id, :quantity, :price)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
                        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
                        $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                        $stmt->bindParam(":price", $item['price'], PDO::PARAM_STR);
                        $stmt->execute();

                        // Update product stock
                        $sql = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    // Commit the transaction
                    $conn->commit();

                    // Clear the cart
                    $_SESSION['cart'] = [];
                    $_SESSION['success_msg'] = "Sale completed successfully!";
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Failed to process sale: " . $e->getMessage();
            }
        }
    } else {
        $error = "Cart is empty.";
    }
}

// Fetch success/error messages
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
$error = isset($error) ? $error : '';
unset($_SESSION['success_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - ZedAuto</title>
    <link rel="stylesheet" href="../style.css">
    <style>
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
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .cart-total {
            text-align: right;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        .action-btn {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .action-btn:hover {
            background-color: #45a049;
        }
        .checkout-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        .checkout-btn:hover {
            background-color: #0056b3;
        }
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007BFF;
            text-decoration: none;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
        .customer-details {
            margin-bottom: 20px;
        }
        .customer-details label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .customer-details input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>ZedAuto Sales</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <div class="header">
            <h1>Point of Sale - ZedAuto</h1>
            <div class="user-info">
                <img src="../images/avatar.jpg" alt="User Avatar">
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
            </div>
        </div>
        <div class="container">
            <h2>Products</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="success-message"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Product List -->
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>ZMK<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" style="width: 60px;">
                                    <button type="submit" name="add_to_cart" class="action-btn">Add to Cart</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Cart -->
            <h2>Cart</h2>
            <?php if (empty($_SESSION['cart'])): ?>
                <p style="text-align: center;">Your cart is empty.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
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
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>ZMK<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>ZMK<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-total">
                    <strong>Total: ZMK<?php echo number_format($cart_total, 2); ?></strong>
                </div>
                <form method="post">
                    <div class="customer-details">
                        <label for="customer_email">Customer Email:</label>
                        <input type="email" name="customer_email" id="customer_email" required>
                        <label for="customer_phone">Customer Phone Number:</label>
                        <input type="text" name="customer_phone" id="customer_phone" required pattern="[0-9]{10,15}" title="Phone number must be 10-15 digits">
                    </div>
                    <button type="submit" name="checkout" class="checkout-btn">Checkout</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// Close connection
unset($conn);
?>