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
$error = '';

// Fetch all users
$sql = "SELECT id, first_name, last_name, email, phone, role FROM users ORDER BY role, first_name";
$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process edit user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role']);

    // Validate inputs
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required.";
    } elseif (!$email) {
        $error = "Invalid email address.";
    } elseif (empty($phone) || !preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Invalid phone number. Must be 10-15 digits.";
    } elseif (!in_array($role, ['manager', 'sales', 'customer'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            // Check if email is already used by another user
            $sql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email,   PDO::PARAM_STR);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                $error = "Email is already in use by another user.";
            } else {
                // Update user
                $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, role = :role WHERE id = :user_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
                $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);
                $stmt->bindParam(":role", $role, PDO::PARAM_STR);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $success_msg = "User updated successfully.";
            }
        } catch (Exception $e) {
            $error = "Failed to update user: " . $e->getMessage();
        }
    }
}

// Process delete user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    try {
        // Check if user has associated sales (prevent deletion if they do)
        $sql = "SELECT COUNT(*) FROM sales WHERE employee_id = :user_id OR customer_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $error = "Cannot delete user with associated sales.";
        } else {
            $sql = "DELETE FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $success_msg = "User deleted successfully.";
            // Refresh user list
            $sql = "SELECT id, first_name, last_name, email, phone, role FROM users ORDER BY role, first_name";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Failed to delete user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ZedAuto</title>
    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #2c3e50;
            padding-top: 20px;
            color: white;
            transition: transform 0.3s ease;
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
            transition: margin-left 0.3s ease;
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
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
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
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            display: inline-block;
        }
        .edit-btn {
            background-color: #3498db;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
        .delete-btn {
            background-color: #e74c3c;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
        .save-btn {
            background-color: #2ecc71;
        }
        .save-btn:hover {
            background-color: #27ae60;
        }
        .cancel-btn {
            background-color: #7f8c8d;
        }
        .cancel-btn:hover {
            background-color: #6c7a89;
        }
        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #toggleBtn {
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            display: none;
            z-index: 1000;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            #toggleBtn {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h2>ZedAuto Admin</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="customs.php">Customs</a>
        <a href="orders.php">Orders</a>
        <a href="inventory.php">Inventory</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Toggle Button for Mobile -->
    <button id="toggleBtn">☰</button>

    <!-- Main Content -->
    <div id="content" class="content">
        <div class="header">
            <h1>Manage Users</h1>
            <div class="user-info">
                <img src="../images/avatar.jpg" alt="User Avatar">
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
            </div>
        </div>
        <div class="container">
            <h2>User Management</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="success-message"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Users Table -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php if (isset($_POST['edit_mode']) && $_POST['user_id'] == $user['id']): ?>
                            <!-- Edit Form -->
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </td>
                                <td>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required pattern="[0-9]{10,15}">
                                </td>
                                <td>
                                    <select name="role" required>
                                        <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="sales" <?php echo $user['role'] == 'sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    </select>
                                </td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="edit_user" class="action-btn save-btn">Save</button>
                                        <button type="button" onclick="window.location.reload();" class="action-btn cancel-btn">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Display Row -->
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="edit_mode" value="1">
                                        <button type="submit" class="action-btn edit-btn">Edit</button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="action-btn delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html>

<?php
// Close connection
unset($conn);
?>