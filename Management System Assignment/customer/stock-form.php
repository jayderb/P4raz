<?php
// Start output buffering to capture any unintended output
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
error_log("[" . date('Y-m-d H:i:s') . "] Session in stock-form.php: " . print_r($_SESSION, true));

// Check if the user is logged in and has the customer role
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

// Initialize variables
$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$valid_brands = [
    'toyota' => ['name' => 'Toyota', 'logo' => '../Static/images/toyota-logo.jpg'],
    'mercedes' => ['name' => 'Mercedes', 'logo' => '../Static/images/mercedes-logo.png'],
    'bmw' => ['name' => 'BMW', 'logo' => '../Static/images/bmw-logo.png'],
    'nissan' => ['name' => 'Nissan', 'logo' => '../Static/images/nissan-logo.png'],
    'honda' => ['name' => 'Honda', 'logo' => '../Static/images/honda-logo.png'],
    'ford' => ['name' => 'Ford', 'logo' => '../Static/images/ford-logo.png'],
    'chevrolet' => ['name' => 'Chevrolet', 'logo' => '../Static/images/chevrolet-logo.png']
];

// Validate brand
if ($brand && !array_key_exists(strtolower($brand), $valid_brands)) {
    $brand = '';
    $error_msg = "Invalid brand selected.";
}

// Fetch car models for the selected brand
$cars = [];
if ($brand) {
    try {
        $sql = "SELECT id, model, price, stock_quantity 
                FROM cars 
                WHERE LOWER(brand) = :brand";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":brand", $brand, PDO::PARAM_STR);
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("[" . date('Y-m-d H:i:s') . "] Fetched " . count($cars) . " cars for brand: $brand");
    } catch (PDOException $e) {
        $error_msg = "Error fetching car models: " . $e->getMessage();
        error_log("[" . date('Y-m-d H:i:s') . "] Fetch cars error: " . $e->getMessage());
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            ob_end_flush();
            exit;
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['purchase'])) {
    $car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    error_log("[" . date('Y-m-d H:i:s') . "] Starting purchase: user_id={$_SESSION['user_id']}, car_id=$car_id, isAjax=$isAjax");
    
    try {
        // Validate inputs
        if ($car_id <= 0) {
            throw new Exception("Invalid car ID.");
        }
        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            throw new Exception("Session expired or invalid user ID. Please log in again.");
        }

        // Start transaction
        $conn->beginTransaction();

        // Verify car exists and has stock
        $sql = "SELECT price, stock_quantity FROM cars WHERE id = :id AND stock_quantity > 0";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $car_id, PDO::PARAM_INT);
        $stmt->execute();
        $car = $stmt->fetch();
        
        if ($car) {
            error_log("[" . date('Y-m-d H:i:s') . "] Car found: id=$car_id, price={$car['price']}, stock={$car['stock_quantity']}");

            // Create sale record
            $sql = "INSERT INTO sales (customer_id, total, sale_date, status) 
                    VALUES (:customer_id, :total, NOW(), 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":customer_id", $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(":total", $car['price'], PDO::PARAM_STR);
            $stmt->execute();
            $sale_id = $conn->lastInsertId();
            error_log("[" . date('Y-m-d H:i:s') . "] Sale created: sale_id=$sale_id");

            // Insert into sales_cars
            $sql = "INSERT INTO sales_cars (sale_id, car_id, quantity, price) 
                    VALUES (:sale_id, :car_id, 1, :price)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":sale_id", $sale_id, PDO::PARAM_INT);
            $stmt->bindParam(":car_id", $car_id, PDO::PARAM_INT);
            $stmt->bindParam(":price", $car['price'], PDO::PARAM_STR);
            $stmt->execute();
            error_log("[" . date('Y-m-d H:i:s') . "] Sales car added: sale_id=$sale_id, car_id=$car_id");

            // Decrease stock quantity
            $sql = "UPDATE cars SET stock_quantity = stock_quantity - 1 WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $car_id, PDO::PARAM_INT);
            $stmt->execute();
            $rows_affected = $stmt->rowCount();
            error_log("[" . date('Y-m-d H:i:s') . "] Stock updated: car_id=$car_id, rows_affected=$rows_affected");

            $conn->commit();
            
            $success_msg = "Purchase request submitted successfully! Your order is pending.";
            $response = ['success' => true, 'message' => $success_msg];

            // Refresh car list
            $sql = "SELECT id, model, price, stock_quantity 
                    FROM cars 
                    WHERE LOWER(brand) = :brand";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":brand", $brand, PDO::PARAM_STR);
            $stmt->execute();
            $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_msg = "Selected car is out of stock or not found.";
            $response = ['success' => false, 'message' => $error_msg];
            error_log("[" . date('Y-m-d H:i:s') . "] Car not found or out of stock: car_id=$car_id");
        }
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            // Check for buffered output
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer: " . ob_get_contents());
                ob_clean(); // Clear any unexpected output
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: stock-form.php?brand=$brand&" . ($car ? "success=" . urlencode($success_msg) : "error=" . urlencode($error_msg)));
            ob_end_flush();
            exit;
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error_msg = "Error processing purchase: " . $e->getMessage();
        $response = ['success' => false, 'message' => $error_msg];
        error_log("[" . date('Y-m-d H:i:s') . "] Purchase error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            // Check for buffered output
            if (ob_get_length()) {
                error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in error: " . ob_get_contents());
                ob_clean();
            }
            echo json_encode($response);
            ob_end_flush();
            exit;
        } else {
            header("Location: stock-form.php?brand=$brand&error=" . urlencode($error_msg));
            ob_end_flush();
            exit;
        }
    }
}

// Debug endpoint for testing purchase
if (isset($_GET['debug_purchase']) && $debug_mode) {
    $car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
    $response = ['success' => false, 'message' => 'Debug purchase failed'];
    try {
        if ($car_id <= 0 || !isset($_SESSION['user_id'])) {
            throw new Exception("Invalid car_id or session.");
        }
        $sql = "SELECT price, stock_quantity FROM cars WHERE id = :id AND stock_quantity > 0";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":id", $car_id, PDO::PARAM_INT);
        $stmt->execute();
        $car = $stmt->fetch();
        $response = ['success' => !!$car, 'message' => $car ? "Car found: price={$car['price']}, stock={$car['stock_quantity']}" : "Car not found or out of stock"];
    } catch (Exception $e) {
        $response['message'] = "Debug error: " . $e->getMessage();
    }
    header('Content-Type: application/json; charset=utf-8');
    // Check for buffered output
    if (ob_get_length()) {
        error_log("[" . date('Y-m-d H:i:s') . "] Unexpected output buffer in debug: " . ob_get_contents());
        ob_clean();
    }
    echo json_encode($response);
    ob_end_flush();
    exit;
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
    <title>ZedAuto - Purchase Car</title>
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
        .table-container, .form-container {
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 12 zichtbaar rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .table th {
            background: #2c3e50;
            color: #ffffff;
        }
        .table tr:hover {
            background: #ecf0f1;
        }
        .action-btn {
            background: #2ecc71;
            transition: background 0.2s ease;
        }
        .action-btn:hover {
            background: #27ae60;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
            border-color: #3498db;
        }
        .message.success {
            background: #e0f7fa;
            color: #006064;
        }
        .message.error {
            background: #ffebee;
            color: #b71c1c;
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
            .brand-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition">Home</a>
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
                    <a href="dashboard.php">Dashboard</a>
                    <a href="stock-form.php">Browse Cars</a>
                    <a href="orders.php">My Orders</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
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
                    Purchase Car - <?php echo $brand ? ucfirst(htmlspecialchars($valid_brands[strtolower($brand)]['name'])) : 'Select a Brand'; ?>
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <img src="https://via.placeholder.com/40" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="message success mb-6 p-4 rounded-md"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="message error mb-6 p-4 rounded-md"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <!-- Brand Selection -->
            <div class="form-container card mb-6">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Select a Car Brand</h2>
                <div class="brand-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($valid_brands as $key => $brand_info): ?>
                        <a href="stock-form.php?brand=<?php echo htmlspecialchars($key); ?>" class="card flex flex-col items-center p-4 bg-[#f9f9f9] border border-[#ecf0f1] rounded-md hover:border-[#3498db] transition">
                            <img src="<?php echo htmlspecialchars($brand_info['logo']); ?>" alt="<?php echo htmlspecialchars($brand_info['name']); ?> Logo" class="w-10 h-10 rounded-full mb-2 object-contain">
                            <span class="text-[#2c3e50] font-medium"><?php echo htmlspecialchars($brand_info['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Car Selection Form -->
            <div class="form-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Available Car Models</h2>
                <?php if ($brand && !empty($cars)): ?>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 rounded-tl-md">Model</th>
                                    <th class="px-4 py-2">Price (ZMK)</th>
                                    <th class="px-4 py-2">Stock Quantity</th>
                                    <th class="px-4 py-2 rounded-tr-md">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cars as $car): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($car['model']); ?></td>
                                        <td class="px-4 py-2"><?php echo number_format($car['price'], 2); ?></td>
                                        <td class="px-4 py-2"><?php echo $car['stock_quantity']; ?></td>
                                        <td class="px-4 py-2">
                                            <?php if ($car['stock_quantity'] > 0): ?>
                                                <form method="post" class="purchase-form" data-car-id="<?php echo $car['id']; ?>">
                                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                    <button type="submit" name="purchase" class="px-4 py-2 text-white rounded-md bg-[#2ecc71] hover:bg-[#27ae60] action-btn transition">
                                                        Purchase
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-[#b71c1c]">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($brand): ?>
                    <p class="text-[#b71c1c]">No cars available for <?php echo ucfirst(htmlspecialchars($valid_brands[strtolower($brand)]['name'])); ?>.</p>
                    <a href="stock-form.php" class="inline-block mt-4 px-4 py-2 bg-[#3498db] text-white rounded-md hover:bg-[#2980b9] transition">Select Another Brand</a>
                <?php else: ?>
                    <p class="text-[#2c3e50]">Please select a brand to view available cars.</p>
                <?php endif; ?>
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
        // Handle Purchase Form Submission
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.purchase-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const carId = form.querySelector('input[name="car_id"]').value;
                    if (!confirm(`Are you sure you want to purchase this car (ID: ${carId})?`)) {
                        return;
                    }
                    const formData = new FormData(form);
                    console.log('Submitting purchase for car_id:', carId);
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
                            alert('Error: Unable to process purchase. Check console for details.');
                            return;
                        }
                        const result = await response.json();
                        console.log('Purchase response:', result);
                        if (result.success) {
                            const button = form.querySelector('button');
                            button.textContent = 'Purchased';
                            button.classList.remove('bg-[#2ecc71]', 'hover:bg-[#27ae60]', 'action-btn');
                            button.classList.add('bg-[#7f8c8d]', 'cursor-not-allowed');
                            button.disabled = true;
                            alert(result.message);
                            location.reload();
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        console.error('Fetch error:', error);
                        alert('Error: Unable to process purchase. Please try again or contact support.');
                    }
                });
            });
        });
    </script>
</body>
</html>