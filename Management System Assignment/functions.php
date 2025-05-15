<?

// start session on all pages that need authentication
session_start();


require_once 'db_connection.php';

// Define connectToDatabase if not already defined in db_connection.php
if (!function_exists('connectToDatabase')) {
    function connectToDatabase() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "zedauto_db"; 

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    }
}

function isLoggedIn() {
    return isset($_SESSION["user_id"]) && isset($_SESSION["role"]);    
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requirePermission($requiredRole) {
    requireLogin();

    $roleHierarchy = [
        'admin' => ['admin', 'manager', 'sales', 'customer'],
        'manager' => ['manager', 'sales', 'customer'],
        'sales' => ['sales', 'customer'],
        'customer' => ['customer']
    ];

    $userRole = $_SESSION["role"];

    if (isset($roleHierarchy[$userRole]) && in_array($requiredRole, $roleHierarchy[$userRole])) {
        return true;
}

    header("Location: access_denied.php");
    exit();
}

function getUserInfo($userId) {
    $conn = connectToDatabase();
    $stmt = $conn->prepare("SELECT id, email, fname, lname, role FROM users WHERE id =?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user;
    }

    $stmt->close();
    $conn->close();
    return false;
}

function placeOrder($userId, $items) {
    $conn = connectToDatabase();
    $conn->begin_transaction();

    try {
        //calculate total price
        $totalAmount = 0;
        foreach ($items as $item) {
            $stmt = $conn->prepare("SELECT price, stock_quantity FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Product unavailable or insufficient stock");
            }

            $totalAmount += $product['price'] * $item['quantity'];
            $stmt->close();
        }

        //create order
        $stmt = $conn->prepare("INSERT INTO order (user_id, total_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $userId, $totalAmount);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        //Add order details and update inventory
        foreach ($items as $item2) {
            // get current price
            $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product + $result->fetch_assoc();
            $stmt->close();

            //add to order details
            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price) VALUEs (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $product['price']);
            $stmt->execute();
            $stmt->close();

            //update inventory
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        //commit transaction
        $conn->commit();
        $conn->close();
        return $orderId;

    } catch (Exception $e) {
        //rollback transaction on error
        $conn->rollback();
        $conn->close();
        return false;
    }
    
}

// function for customer to cancel orders (within 24 hours)
function cancelOrder($orderId, $userId) {
    $conn = connectToDatabase();
    $stmt = $conn->prepare("SELECT order_id, user_id, order_date, staus FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Order not found'];
    }

    $order = $result->fetch_assoc();
    $stmt->close();

    //check if user is customer and order belongs to customer, or if user is admin, sales, manager
    $isSalesOrManager = ($_SESSION['role'] === 'sales' || $_SESSION['role'] === 'manager' || $_SESSION['role'] === 'admin');
    $isOrderOwner = ($order['user_id'] === $userId);

    if (!$isSalesOrManager && !$isOrderOwner) {
        $conn->close();
        return ['success' => false, 'message' => 'Not authorized to cancel this order'];
    }

    // check if order is within 24hours for customers.
    if ($_SESSION['role'] === 'customer') {
        $orderTime = strtotime($order['order_date']);
        $currentTime = time();
        $timeDiff = $currentTime - $orderTime;

        if ($timeDiff > 86400) { // 24hours in seconds
            $conn->close();
            return ['success' => false, 'message' => 'Order cannot be cancelled after 24 hours of placement'];
        }
    }
}

function processInStorePurchase($employeeId, $items) {
    $conn = connectToDatabase();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Calculate total amount and check inventory
        $totalAmount = 0;
        foreach ($items as $item) {
            $stmt = $conn->prepare("SELECT price, stock_quantity FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            // Check if product exists and is in stock
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Product unavailable or insufficient stock");
            }
            
            $totalAmount += $product['price'] * $item['quantity'];
            $stmt->close();
        }
        
        // Record sales transaction
        $stmt = $conn->prepare("INSERT INTO sales (employee_id, payment_method, total_amount) VALUES (?, ?, ?)");
        $paymentMethod = $items[0]['payment_method']; // Assuming payment method is passed with items
        $stmt->bind_param("isd", $employeeId, $paymentMethod, $totalAmount);
        $stmt->execute();
        $saleId = $conn->insert_id;
        $stmt->close();
        
        // Update inventory
        foreach ($items as $item) {
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        return $saleId;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->close();
        return false;
    }
}

// Function for inventory management (manager/sales)
function updateInventory($productId, $quantity) {
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
    $stmt->bind_param("ii", $quantity, $productId);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Add product function (manager only)
function addProduct($name, $description, $price, $stockQuantity, $category) {
    // Check if user is manager
    if ($_SESSION['role'] !== 'manager') {
        return false;
    }
    
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock_quantity, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $name, $description, $price, $stockQuantity, $category);
    $result = $stmt->execute();
    $productId = $result ? $conn->insert_id : false;
    
    $stmt->close();
    $conn->close();
    
    return $productId;
}

// Function to get sales report (manager only)
function getSalesReport($startDate, $endDate) {
    // Check if user is manager
    if ($_SESSION['role'] !== 'manager') {
        return false;
    }
    
    $conn = connectToDatabase();
    
    $stmt = $conn->prepare("
        SELECT 
            s.sale_id, 
            s.transaction_date, 
            s.payment_method, 
            s.total_amount,
            CONCAT(u.first_name, ' ', u.last_name) as employee_name,
            o.order_id
        FROM 
            sales s
        JOIN 
            users u ON s.employee_id = u.id
        LEFT JOIN 
            orders o ON s.order_id = o.order_id
        WHERE 
            s.transaction_date BETWEEN ? AND ?
        ORDER BY 
            s.transaction_date DESC
    ");
    
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $salesReport = [];
    while ($row = $result->fetch_assoc()) {
        $salesReport[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $salesReport;
}
?>
