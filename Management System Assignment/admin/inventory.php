<?php
// Start the session
session_start();

// Check if the user is logged in and has the manager role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "manager") {
    header("location: ../RetailSystem-LocalGarage-Login.php");
    exit;
}

// Include config file
try {
    require_once "../config.php";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables for messages
$success_msg = '';
$error_msg = '';

// Fetch inventory data
$sql = "SELECT part_id, warehouse_id, part_name, description, category, quantity, unit_price, reorder_level, supplier_id, last_updated FROM inventory";
$stmt = $conn->prepare($sql);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for low stock items
$low_stock_items = array_filter($inventory, function($item) {
    return $item['quantity'] < $item['reorder_level'] && $item['reorder_level'] > 0;
});

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_product'])) {
        // Add new product
        $part_name = trim($_POST['part_name']);
        $warehouse_id = intval($_POST['warehouse_id']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $reorder_level = intval($_POST['reorder_level']);
        $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;

        if (!empty($part_name) && $unit_price > 0 && $quantity >= 0) {
            try {
                $sql = "INSERT INTO inventory (warehouse_id, part_name, description, category, quantity, unit_price, reorder_level, supplier_id) 
                        VALUES (:warehouse_id, :part_name, :description, :category, :quantity, :unit_price, :reorder_level, :supplier_id)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":warehouse_id", $warehouse_id, PDO::PARAM_INT);
                $stmt->bindParam(":part_name", $part_name, PDO::PARAM_STR);
                $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                $stmt->bindParam(":category", $category, PDO::PARAM_STR);
                $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
                $stmt->bindParam(":unit_price", $unit_price, PDO::PARAM_STR);
                $stmt->bindParam(":reorder_level", $reorder_level, PDO::PARAM_INT);
                $stmt->bindParam(":supplier_id", $supplier_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Part added successfully!";
                
                // Refresh inventory data
                $sql = "SELECT part_id, warehouse_id, part_name, description, category, quantity, unit_price, reorder_level, supplier_id, last_updated FROM inventory";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $low_stock_items = array_filter($inventory, function($item) {
                    return $item['quantity'] < $item['reorder_level'] && $item['reorder_level'] > 0;
                });
            } catch (PDOException $e) {
                $error_msg = "Error adding part: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid part details.";
        }
    } elseif (isset($_POST['update_inventory'])) {
        // Update existing part
        $part_id = intval($_POST['part_id']);
        $part_name = trim($_POST['part_name']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $reorder_level = intval($_POST['reorder_level']);
        $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;

        if (!empty($part_name) && $unit_price > 0 && $quantity >= 0) {
            try {
                $sql = "UPDATE inventory 
                        SET part_name = :part_name, 
                            description = :description, 
                            category = :category, 
                            quantity = :quantity, 
                            unit_price = :unit_price, 
                            reorder_level = :reorder_level, 
                            supplier_id = :supplier_id 
                        WHERE part_id = :part_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":part_name", $part_name, PDO::PARAM_STR);
                $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                $stmt->bindParam(":category", $category, PDO::PARAM_STR);
                $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
                $stmt->bindParam(":unit_price", $unit_price, PDO::PARAM_STR);
                $stmt->bindParam(":reorder_level", $reorder_level, PDO::PARAM_INT);
                $stmt->bindParam(":supplier_id", $supplier_id, PDO::PARAM_INT);
                $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Part updated successfully!";
                
                // Refresh inventory data
                $sql = "SELECT part_id, warehouse_id, part_name, description, category, quantity, unit_price, reorder_level, supplier_id, last_updated FROM inventory";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $low_stock_items = array_filter($inventory, function($item) {
                    return $item['quantity'] < $item['reorder_level'] && $item['reorder_level'] > 0;
                });
            } catch (PDOException $e) {
                $error_msg = "Error updating part: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid part details.";
        }
    } elseif (isset($_POST['delete_product'])) {
        // Delete part
        $part_id = intval($_POST['part_id']);
        try {
            $sql = "DELETE FROM inventory WHERE part_id = :part_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":part_id", $part_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = "Part deleted successfully!";
            
            // Refresh inventory data
            $sql = "SELECT part_id, warehouse_id, part_name, description, category, quantity, unit_price, reorder_level, supplier_id, last_updated FROM inventory";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $low_stock_items = array_filter($inventory, function($item) {
                return $item['quantity'] < $item['reorder_level'] && $item['reorder_level'] > 0;
            });
        } catch (PDOException $e) {
            $error_msg = "Error deleting part: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZedAuto - Inventory Management</title>
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
        .action-btn {
            background: #2ecc71;
            transition: background 0.2s ease;
        }
        .action-btn:hover {
            background: #27ae60;
        }
        .delete-btn {
            background: #e74c3c;
            transition: background 0.2s ease;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
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
                <a href="reviews.php" class="text-white hover:text-[#f1c40f] transition">Reviews</a>
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
                <h2 class="sr-only">Manager Navigation</h2>
                <nav>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_users.php">Users</a>
                    <a href="customs.php">Customs</a>
                    <a href="orders.php">Orders</a>
                    <a href="inventory.php">Inventory</a>
                    <a href="warehouse.php">Warehouse</a>
                </nav>
            </div>
        </nav>
        <!-- Mobile Top Bar Menu -->
        <div id="topbar-mobile" class="md:hidden">
            <div class="container mx-auto px-4 py-4 flex flex-col space-y-2">
                <a href="dashboard.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Dashboard</a>
                <a href="manage_users.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Users</a>
                <a href="customs.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Customs</a>
                <a href="orders.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Orders</a>
                <a href="inventory.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Inventory</a>
                <a href="warehouse.php" class="text-white hover:text-[#f1c40f] transition px-4 py-2">Warehouse</a>
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
                    Inventory Management
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <img src="https://via.placeholder.com/40" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                    <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">
                        Add Part
                    </button>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-6 p-4 bg-[#e0f7fa] text-[#006064] rounded-md"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 p-4 bg-[#ffebee] text-[#b71c1c] rounded-md"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <!-- Add Part Form -->
            <div id="addForm" class="table-container mb-8 card hidden">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Add New Part</h2>
                <form method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="number" name="warehouse_id" placeholder="Warehouse ID" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required value="1">
                    <input type="text" name="part_name" placeholder="Part Name" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <input type="text" name="description" placeholder="Description" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                    <input type="text" name="category" placeholder="Category" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                    <input type="number" name="quantity" placeholder="Quantity" min="0" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <input type="number" name="unit_price" placeholder="Unit Price" step="0.01" min="0" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <input type="number" name="reorder_level" placeholder="Reorder Level" min="0" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                    <input type="number" name="supplier_id" placeholder="Supplier ID (optional)" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]">
                    <div class="flex gap-4 col-span-1 md:col-span-2 lg:col-span-3">
                        <button type="submit" name="add_product" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Add Part</button>
                        <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition">Cancel</button>
                    </div>
                </form>
            </div>
            <!-- Low Stock Notification -->
            <?php if (!empty($low_stock_items)): ?>
                <div class="mb-8 p-4 bg-[#ffe6e6] border-l-4 border-[#e74c3c] rounded-md card">
                    <h3 class="text-lg font-medium text-[#e74c3c] mb-2">Low Stock Alert</h3>
                    <p class="text-[#2c3e50] mb-2">Please order new parts for the following items:</p>
                    <ul class="list-disc pl-5 text-[#2c3e50]">
                        <?php foreach ($low_stock_items as $item): ?>
                            <li><?php echo htmlspecialchars($item['part_name']); ?> (Stock: <?php echo $item['quantity']; ?>, Reorder Level: <?php echo $item['reorder_level']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-2"><a href="#" class="text-[#3498db] hover:text-[#2980b9] underline">Order Now</a></p>
                </div>
            <?php endif; ?>
            <!-- Inventory Table -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">Inventory</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">ID</th>
                                <th class="px-4 py-2">Part Name</th>
                                <th class="px-4 py-2">Category</th>
                                <th class="px-4 py-2">Quantity</th>
                                <th class="px-4 py-2">Unit Price</th>
                                <th class="px-4 py-2">Reorder Level</th>
                                <th class="px-4 py-2">Last Updated</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo $item['part_id']; ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['part_name']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?php echo $item['quantity']; ?></td>
                                    <td class="px-4 py-2">ZMK<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="px-4 py-2"><?php echo $item['reorder_level']; ?></td>
                                    <td class="px-4 py-2"><?php echo date('Y-m-d H:i', strtotime($item['last_updated'])); ?></td>
                                    <td class="px-4 py-2">
                                        <div class="hidden" id="editForm-<?php echo $item['part_id']; ?>">
                                            <form method="post" class="grid grid-cols-1 gap-2">
                                                <input type="hidden" name="part_id" value="<?php echo $item['part_id']; ?>">
                                                <div class="flex gap-2">
                                                    <input type="text" name="part_name" value="<?php echo htmlspecialchars($item['part_name']); ?>" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]" required>
                                                    <input type="text" name="category" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]">
                                                </div>
                                                <div class="flex gap-2">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]" required>
                                                    <input type="number" name="unit_price" value="<?php echo $item['unit_price']; ?>" step="0.01" min="0" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]" required>
                                                </div>
                                                <div class="flex gap-2">
                                                    <input type="number" name="reorder_level" value="<?php echo $item['reorder_level']; ?>" min="0" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]" required>
                                                    <input type="text" name="description" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]">
                                                    <input type="number" name="supplier_id" value="<?php echo $item['supplier_id']; ?>" class="w-full px-2 py-1 border border-[#ddd] rounded-md focus:outline-none focus:ring-1 focus:ring-[#3498db]">
                                                </div>
                                                <div class="flex gap-2 justify-end mt-2">
                                                    <button type="submit" name="update_inventory" class="px-3 py-1 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition text-sm">Save</button>
                                                    <button type="button" onclick="document.getElementById('editForm-<?php echo $item['part_id']; ?>').classList.add('hidden')" class="px-3 py-1 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition text-sm">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="document.getElementById('editForm-<?php echo $item['part_id']; ?>').classList.toggle('hidden')" class="px-3 py-1 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition action-btn text-sm">Edit</button>
                                            <form method="post" class="inline">
                                                <input type="hidden" name="part_id" value="<?php echo $item['part_id']; ?>">
                                                <button type="submit" name="delete_product" class="px-3 py-1 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition delete-btn text-sm" onclick="return confirm('Are you sure you want to delete this part?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
    </script>
<?php
// Close connection
unset($conn);
?>