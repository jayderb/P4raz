<?php
// Start the session
session_start();

// Check if the user is logged in and has the sales role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "sales") {
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch inventory items
$sql = "SELECT part_id, part_name, unit_price, quantity FROM inventory WHERE quantity > 0";
$stmt = $conn->prepare($sql);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process adding items to the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $part_id = (int)$_POST['part_id'];
    $quantity = (int)$_POST['quantity'];

    // Validate part ID and quantity
    $item_exists = false;
    $selected_item = null;
    foreach ($items as $item) {
        if ($item['part_id'] == $part_id && $item['quantity'] >= $quantity && $quantity > 0) {
            $item_exists = true;
            $selected_item = $item;
            break;
        }
    }

    if ($item_exists) {
        // Add to cart or update quantity
        if (isset($_SESSION['cart'][$part_id])) {
            $_SESSION['cart'][$part_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$part_id] = [
                'quantity' => $quantity,
                'name' => $selected_item['part_name'],
                'price' => $selected_item['unit_price']
            ];
        }
    } else {
        $error = "Invalid item or insufficient stock.";
    }
}

// Process removing items from the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_item'])) {
    $part_id = (int)$_POST['part_id'];
    if (isset($_SESSION['cart'][$part_id])) {
        unset($_SESSION['cart'][$part_id]);
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
                        $sql = "INSERT INTO users (email, phone, role, created_at) VALUES (:email, :phone, 'customer', NOW())";
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
                    foreach ($_SESSION['cart'] as $part_id => $item) {
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

                    // Insert into sales_items table and update inventory
                    foreach ($_SESSION['cart'] as $part_id => $item) {
                        // Insert sale item
                        $sql = "INSERT INTO sales_items (sale_id, part_id, quantity, price) 
                                VALUES (:sale_id, :part_id, :quantity, :price)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
                        $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
                        $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                        $stmt->bindParam(":price", $item['price'], PDO::PARAM_STR);
                        $stmt->execute();

                        // Update inventory quantity
                        $sql = "UPDATE inventory SET quantity = quantity - :quantity WHERE part_id = :part_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":quantity", $item['quantity'], PDO::PARAM_INT);
                        $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
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
    <title>ZedAuto - Point of Sale</title>
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
        .card {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        .item-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
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
        .collapsible-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .collapsible-content {
            display: none;
        }
        .collapsible-content.active {
            display: block;
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
            .item-grid {
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
                <a href="#" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex items-center">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c2f33] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </nav>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-[#2c2f33]">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-4">
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
                <div class="flex">
                    <input type="text" class="px-3 py-2 rounded-l-md bg-[#40444b] text-white placeholder-gray-300 focus:outline-none w-full" placeholder="Search...">
                    <button class="px-4 py-2 bg-[#ffcc00] text-[#2c2f33] rounded-r-md hover:bg-[#e6b800] transition">Search</button>
                </div>
                <a href="../logout.php" class="text-red-400 hover:text-red-500 transition">Logout</a>
            </div>
        </div>
        <!-- Top Bar -->
        <nav id="topbar" class="topbar">
            <div class="container mx-auto px-4">
                <h2 class="sr-only">Sales Navigation</h2>
                <nav>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="point_of_sale.php">Point of Sale</a>
                    <a href="orders.php">Orders</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
                <a href="point_of_sale.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Point of Sale</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Orders</a>
                <a href="../logout.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Logout</a>
            </div>
        </div>
    </header>

    <!-- Toggle Button for Mobile Top Bar -->
    <button id="topbar-toggle" class="md:hidden" aria-label="Toggle top bar menu">☰</button>

    <!-- Main Content -->
    <main class="content">
        <section class="container mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-semibold text-[#2c3e50] mb-6 text-center">
                Point of Sale
            </h1>
            <h2 class="text-xl md:text-2xl font-medium text-[#2c3e50] mb-4 text-center">
                Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
            </h2>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="card mb-6 bg-[#e0f7fa] text-[#006064] p-4">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="card mb-6 bg-[#ffebee] text-[#b71c1c] p-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Inventory Items -->
            <div class="card mb-8">
                <div class="collapsible-header" data-target="items-content">
                    <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Inventory Items</h2>
                    <i class="fas fa-chevron-down text-[#2c3e50]"></i>
                </div>
                <div id="items-content" class="collapsible-content active">
                    <div class="item-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($items as $item): ?>
                            <div class="item-card bg-white p-4 rounded-md shadow-sm">
                                <h3 class="text-lg font-semibold text-[#2c3e50] mb-2"><?php echo htmlspecialchars($item['part_name']); ?></h3>
                                <p class="text-[#7f8c8d] mb-1">Price: ZMK<?php echo number_format($item['unit_price'], 2); ?></p>
                                <p class="text-[#7f8c8d] mb-3">Stock: <?php echo $item['quantity']; ?></p>
                                <form method="post" class="flex items-center space-x-2" onsubmit="return validateQuantity(this, <?php echo $item['quantity']; ?>)">
                                    <input type="hidden" name="part_id" value="<?php echo $item['part_id']; ?>">
                                    <input type="number" name="quantity" min="1" max="<?php echo $item['quantity']; ?>" value="1" class="w-20 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                    <button type="submit" name="add_to_cart" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Add</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Cart -->
            <div class="card">
                <div class="collapsible-header" data-target="cart-content">
                    <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Cart</h2>
                    <i class="fas fa-chevron-down text-[#2c3e50]"></i>
                </div>
                <div id="cart-content" class="collapsible-content active">
                    <?php if (empty($_SESSION['cart'])): ?>
                        <p class="text-center text-[#2c3e50]">Your cart is empty.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php 
                            $cart_total = 0;
                            foreach ($_SESSION['cart'] as $part_id => $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $cart_total += $subtotal;
                            ?>
                                <div class="flex justify-between items-center p-4 bg-[#ecf0f1] rounded-md">
                                    <div>
                                        <h3 class="text-lg font-medium text-[#2c3e50]"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-[#7f8c8d]">Price: ZMK<?php echo number_format($item['price'], 2); ?> | Quantity: <?php echo $item['quantity']; ?></p>
                                        <p class="text-[#2c3e50] font-semibold">Subtotal: ZMK<?php echo number_format($subtotal, 2); ?></p>
                                    </div>
                                    <form method="post">
                                        <input type="hidden" name="part_id" value="<?php echo $part_id; ?>">
                                        <button type="submit" name="remove_item" class="px-3 py-1 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition">Remove</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-right">
                            <p class="text-xl font-semibold text-[#2c3e50]" id="cart-total">Total: ZMK<?php echo number_format($cart_total, 2); ?></p>
                        </div>
                        <div class="mt-6">
                            <button class="w-full sm:w-auto px-6 py-2 bg-[#3498db] text-white rounded-md hover:bg-[#2980b9] transition collapsible-header" data-target="checkout-content">Proceed to Checkout</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Checkout -->
            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="card mt-8">
                    <div class="collapsible-header" data-target="checkout-content">
                        <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Checkout</h2>
                        <i class="fas fa-chevron-down text-[#2c3e50]"></i>
                    </div>
                    <div id="checkout-content" class="collapsible-content">
                        <form method="post" onsubmit="return validateCheckout(this)">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="customer_email" class="block text-sm font-medium text-[#2c3e50] mb-1">Customer Email</label>
                                    <input type="email" name="customer_email" id="customer_email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                </div>
                                <div>
                                    <label for="customer_phone" class="block text-sm font-medium text-[#2c3e50] mb-1">Customer Phone Number</label>
                                    <input type="text" name="customer_phone" id="customer_phone" required pattern="[0-9]{10,15}" title="Phone number must be 10-15 digits" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                                </div>
                            </div>
                            <button type="submit" name="checkout" class="w-full sm:w-auto px-6 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Complete Sale</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
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

        // Collapsible Sections
        const collapsibleHeaders = document.querySelectorAll('.collapsible-header');
        collapsibleHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const targetId = header.getAttribute('data-target');
                const content = document.getElementById(targetId);
                const icon = header.querySelector('i');
                content.classList.toggle('active');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });

        // Validate Quantity
        function validateQuantity(form, maxStock) {
            const quantity = form.querySelector('input[name="quantity"]').value;
            if (quantity < 1 || quantity > maxStock) {
                alert(`Please enter a quantity between 1 and ${maxStock}.`);
                return false;
            }
            return true;
        }

        // Validate Checkout
        function validateCheckout(form) {
            const email = form.querySelector('#customer_email').value;
            const phone = form.querySelector('#customer_phone').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^[0-9]{10,15}$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }
            if (!phoneRegex.test(phone)) {
                alert('Please enter a valid phone number (10-15 digits).');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

<?php
// Close connection
unset($conn);
?>