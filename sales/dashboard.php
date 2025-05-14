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

// Fetch total sales for the logged-in employee
$employee_id = $_SESSION['user_id'];
$sql = "SELECT COALESCE(SUM(total), 0) as total_sales FROM sales WHERE employee_id = :employee_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":employee_id", $employee_id, PDO::PARAM_INT);
$stmt->execute();
$total_sales = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - ZedAuto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
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
        .content{
            margin-left: 250px;
            padding: 20px;
        }
        .container {
            align-items: center;
            max-width: 1200px;
            margin-left: 250px;
            padding: 20px;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
            border-bottom: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .welcome {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .welcome p {
            margin: 5px 0;
            font-size: 16px;
        }
        .dashboard-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 300px;
            text-align: center;
        }
        .card h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 24px;
            color: #3498db;
            font-weight: bold;
        }
        .pos-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .pos-link:hover {
            background-color: #2980b9;
        }
        .logout-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .dashboard-content {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Dashboard - ZedAuto</h1>
    </div>
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Sales</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome">
            <p>Hello, <?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?>!</p>
            <p>Welcome to your sales dashboard. Manage your sales and process transactions.</p>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Total Sales Card -->
            <div class="card">
                <h3>Total Sales</h3>
                <p>$<?php echo number_format($total_sales, 2); ?></p>
            </div>

            <!-- POS Link Card -->
            <div class="card">
                <h3>Point of Sale</h3>
                <a href="point_of_sale.php" class="pos-link">Go to POS</a>
            </div>

            <!-- Orders Link Card -->
            <div class="card">
                <h3>Check Pending orders</h3>
                <a href="orders.php" class="pos-link">Orders</a>
            </div>
        </div>

        <!-- Logout Link -->
        <a href="../logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>

<?php
// Close connection
unset($conn);
?>