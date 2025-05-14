<?php
// Start the session
session_start();

// Check if the user is logged in and has the admin role
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
$sql = "SELECT id, name, price, stock FROM products";
$stmt = $conn->prepare($sql);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define low stock threshold
$low_stock_threshold = 5;

// Check for low stock items
$low_stock_items = array_filter($inventory, function($item) use ($low_stock_threshold) {
    return $item['stock'] < $low_stock_threshold;
});

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);

        if (!empty($name) && $price > 0 && $stock >= 0) {
            try {
                $sql = "INSERT INTO products (name, price, stock, created_at) VALUES (:name, :price, :stock, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":name", $name, PDO::PARAM_STR);
                $stmt->bindParam(":price", $price, PDO::PARAM_STR);
                $stmt->bindParam(":stock", $stock, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Product added successfully!";
                // Refresh inventory data
                $sql = "SELECT id, name, price, stock FROM products";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $low_stock_items = array_filter($inventory, function($item) use ($low_stock_threshold) {
                    return $item['stock'] < $low_stock_threshold;
                });
            } catch (PDOException $e) {
                $error_msg = "Error adding product: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid product details.";
        }
    } elseif (isset($_POST['update_product'])) {
        // Update existing product
        $product_id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);

        if (!empty($name) && $price > 0 && $stock >= 0) {
            try {
                $sql = "UPDATE products SET name = :name, price = :price, stock = :stock WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":name", $name, PDO::PARAM_STR);
                $stmt->bindParam(":price", $price, PDO::PARAM_STR);
                $stmt->bindParam(":stock", $stock, PDO::PARAM_INT);
                $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "Product updated successfully!";
                // Refresh inventory data
                $sql = "SELECT id, name, price, stock FROM products";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $low_stock_items = array_filter($inventory, function($item) use ($low_stock_threshold) {
                    return $item['stock'] < $low_stock_threshold;
                });
            } catch (PDOException $e) {
                $error_msg = "Error updating product: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please provide valid product details.";
        }
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $product_id = intval($_POST['product_id']);
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = "Product deleted successfully!";
            // Refresh inventory data
            $sql = "SELECT id, name, price, stock FROM products";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $low_stock_items = array_filter($inventory, function($item) use ($low_stock_threshold) {
                return $item['stock'] < $low_stock_threshold;
            });
        } catch (PDOException $e) {
            $error_msg = "Error deleting product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ZedAuto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            font-size: 14px;
        }
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
        .header .buttons button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            background-color: #3498db;
            color: white;
            cursor: pointer;
        }
        .header .buttons button:hover {
            background-color: #2980b9;
        }
        .card {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .form-container {
            margin-bottom: 20px;
        }
        .form-container form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .form-container input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-container button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background-color: #3498db;
            color: white;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .action-buttons form {
            display: inline;
        }
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
        }
        .action-buttons .edit-btn {
            background-color: #2ecc71;
            color: white;
        }
        .action-buttons .edit-btn:hover {
            background-color: #27ae60;
        }
        .action-buttons .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .action-buttons .delete-btn:hover {
            background-color: #c0392b;
        }
        .notification-panel {
            background-color: #ffe6e6;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .notification-panel h3 {
            margin-top: 0;
            font-size: 16px;
            color: #e74c3c;
        }
        .notification-panel ul {
            list-style-type: disc;
            padding-left: 20px;
        }
        .notification-panel a {
            color: #3498db;
            text-decoration: underline;
        }
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #e0f7fa;
            color: #006064;
        }
        .message.error {
            background-color: #ffebee;
            color: #b71c1c;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            .content {
                margin-left: 0;
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="#">Users</a>
        <a href="./orders.php">Orders</a>
        <a href="inventory.php">Inventory</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn" style="position: fixed; top: 15px; left: 15px; background-color: #3498db; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; display: none;">
        ☰
    </button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Inventory Management</h1>
            <div class="header-right">
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span>Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                </div>
                <div class="buttons">
                    <button onclick="document.getElementById('addForm').style.display='block'">Add Product</button>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div id="addForm" class="card form-container" style="display: none;">
            <h3>Add New Product</h3>
            <form method="post">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="number" name="price" placeholder="Price" step="0.01" min="0" required>
                <input type="number" name="stock" placeholder="Stock" min="0" required>
                <button type="submit" name="add_product">Add Product</button>
                <button type="button" onclick="document.getElementById('addForm').style.display='none'" style="background-color: #e74c3c;">Cancel</button>
            </form>
        </div>

        <!-- Low Stock Notification -->
        <?php if (!empty($low_stock_items)): ?>
            <div class="notification-panel">
                <h3>Low Stock Alert</h3>
                <p>Please order new parts for the following items:</p>
                <ul>
                    <?php foreach ($low_stock_items as $item): ?>
                        <li><?php echo htmlspecialchars($item['name']); ?> (Stock: <?php echo $item['stock']; ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="#">Order Now</a></p>
            </div>
        <?php endif; ?>

        <!-- Inventory Table -->
        <div class="card">
            <h3>Inventory</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['stock']; ?></td>
                                <td class="action-buttons">
                                    <form method="post" style="display: none;" id="editForm-<?php echo $item['id']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                        <input type="number" name="price" value="<?php echo $item['price']; ?>" step="0.01" min="0" required>
                                        <input type="number" name="stock" value="<?php echo $item['stock']; ?>" min="0" required>
                                        <button type="submit" name="update_product" class="edit-btn">Save</button>
                                        <button type="button" onclick="document.getElementById('editForm-<?php echo $item['id']; ?>').style.display='none'" style="background-color: #e74c3c;">Cancel</button>
                                    </form>
                                    <button class="edit-btn" onclick="document.getElementById('editForm-<?php echo $item['id']; ?>').style.display='block'">Edit</button>
                                    <form method="post">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_product" class="delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript for Sidebar Toggle -->
    <script>
        // Sidebar Toggle for Mobile
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const content = document.getElementById('content');

        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'block';
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                toggleBtn.style.display = 'block';
            } else {
                toggleBtn.style.display = 'none';
                sidebar.classList.remove('active');
            }
        });

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>

<?php
// Close connection
unset($conn);
?>