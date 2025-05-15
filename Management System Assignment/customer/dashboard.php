<?php
// Start the session
session_start();

// Debug session
error_log("Session in dashboard.php: loggedin=" . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "unset") . 
          ", role=" . (isset($_SESSION["role"]) ? $_SESSION["role"] : "unset") . 
          ", user_id=" . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "unset"));

// Check if the user is logged in as a customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "customer" || !isset($_SESSION["user_id"])) {
    error_log("Redirecting to login: loggedin=" . (isset($_SESSION["loggedin"]) ? $_SESSION["loggedin"] : "unset") . 
              ", role=" . (isset($_SESSION["role"]) ? $_SESSION["role"] : "unset") . 
              ", user_id=" . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "unset"));
    header("location: ../RetailSystem-Customer-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    error_log("Config error: " . $e->getMessage());
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
$error = '';

// Handle review form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    $customer_id = $_SESSION['user_id'];

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
        } catch (PDOException $e) {
            $review_error_msg = "Error submitting review: " . $e->getMessage();
        }
    }
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
        $customer_email = filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL);
        $customer_phone = trim($_POST['customer_phone']);

        if (!$customer_email) {
            $error = "Invalid email address.";
        } elseif (empty($customer_phone) || !preg_match("/^[0-9]{10,15}$/", $customer_phone)) {
            $error = "Invalid phone number. Must be 10-15 digits.";
        } else {
            try {
                $conn->beginTransaction();

                $customer_id = $_SESSION['user_id'];

                $total = 0;
                foreach ($_SESSION['cart'] as $product_id => $item) {
                    $total += $item['price'] * $item['quantity'];
                }

                $sql = "INSERT INTO sales (customer_id, total, sale_date, status) VALUES (:customer_id, :total, NOW(), 'pending')";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
                $stmt->bindParam(":total", $total, PDO::PARAM_STR);
                $stmt->execute();
                $sale_id = $conn->lastInsertId();

                foreach ($_SESSION['cart'] as $product_id => $item) {
                    $sql = "INSERT INTO sales_items (sale_id, product_id, quantity, price) 
                            VALUES (:sale_id, :product_id, :quantity, :price)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
                    $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
                    $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                    $stmt->bindParam(":price", $item['price'], PDO::PARAM_STR);
                    $stmt->execute();

                    $sql = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                    $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
                    $stmt->execute();
                }

                $conn->commit();
                $_SESSION['cart'] = [];
                $success_msg = "Purchase completed successfully! Awaiting approval.";
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Failed to process purchase: " . $e->getMessage();
            }
        }
    } else {
        $error = "Cart is empty.";
    }
}

// Fetch customer profile data for overview
$sql = "SELECT first_name, last_name, email, phone, created_at FROM users WHERE id = :id AND role = 'customer'";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch();

// Fetch recent orders for overview
$sql = "SELECT id, total, sale_date 
        FROM sales 
        WHERE customer_id = :customer_id 
        ORDER BY sale_date DESC 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$last_order = $stmt->fetch();

// Fetch order count
$sql = "SELECT COUNT(*) as order_count 
        FROM sales 
        WHERE customer_id = :customer_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$order_count = $stmt->fetchColumn();

// Fetch total spent
$sql = "SELECT COALESCE(SUM(total), 0) as total_spent 
        FROM sales 
        WHERE customer_id = :customer_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$total_spent = $stmt->fetchColumn();
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
            <a href="../RetailSytsem-Home.html" class="text-2xl font-bold text-white">ZedAuto</a>
            <button id="hamburger" class="md:hidden text-white text-2xl focus:outline-none" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <nav id="nav-menu" class="hidden md:flex items-center space-x-6">
                <a href="../RetailSytsem-Home.html" class="text-white hover:text-[#f1c40f] transition">Home</a>
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
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
                <a href="../RetailSytsem-Home.html" class="text-white hover:text-[#f1c40f] transition">Home</a>
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
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
                    <a href="../RetailSytsem-Home.html">Home</a>
                    <a href="stock-form.php">Browse Cars</a>
                    <a href="orders.php">My Orders</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="../RetailSytsem-Home.html" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Home</a>
                <a href="stock-form.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Browse Cars</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">My Orders</a>
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
                        <img src="https://via.placeholder.com/40" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
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
                <?php if (!empty($success_msg)): ?>
                    <div class="message success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                <!-- Product List -->
                <h3 class="text-lg font-semibold text-[#2c3e50] mb-4">Available Products</h3>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">Product Name</th>
                                <th class="px-4 py-2">Price (ZMK)</th>
                                <th class="px-4 py-2">Stock</th>
                                <th class="px-4 py-2 rounded-tr-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-[#2c3e50]">No products available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="px-4 py-2"><?php echo number_format($product['price'], 2); ?></td>
                                        <td class="px-4 py-2"><?php echo $product['stock']; ?></td>
                                        <td class="px-4 py-2">
                                            <form method="post" class="flex items-center gap-2">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" class="px-2 py-1 rounded-md bg-[#f9f9f9] text-[#2c3e50] border border-[#ecf0f1] w-16 focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                                <button type="submit" name="add_to_cart" class="px-4 py-2 bg-[#3498db] text-white rounded-md hover:bg-[#2980b9] transition">Add to Cart</button>
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
                    <form method="post">
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
            <!-- Review Submission Form -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Submit a Review for ZedAuto</h2>
                <?php if (!empty($review_success_msg)): ?>
                    <div class="message success"><?php echo $review_success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($review_error_msg)): ?>
                    <div class="message error"><?php echo $review_error_msg; ?></div>
                <?php endif; ?>
                <form method="post" class="flex flex-col gap-4">
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
        const topbarLinks = topbarMobile.queryAll('a');
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
    </script>
<?php
// Close connection
unset($conn);
?>
</body>
</html>