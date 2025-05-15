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
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
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
    <title>ZedAuto - Manage Users</title>
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
                    Manage Users
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <img src="../images/avatar.jpg" alt="User" class="w-10 h-10 rounded-full mr-2">
                        <span class="text-[#7f8c8d] text-lg">Hi, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</span>
                    </div>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="mb-6 p-4 bg-[#e0f7fa] text-[#006064] rounded-md"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-[#ffebee] text-[#b71c1c] rounded-md"><?php echo $error; ?></div>
            <?php endif; ?>
            <!-- Users Table -->
            <div class="table-container card">
                <h2 class="text-2xl font-medium text-[#2c3e50] mb-4">User Management</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 rounded-tl-md">ID</th>
                                <th class="px-4 py-2">First Name</th>
                                <th class="px-4 py-2">Last Name</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Phone</th>
                                <th class="px-4 py-2">Role</th>
                                <th class="px-4 py-2 rounded-tr-md">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php if (isset($_POST['edit_mode']) && $_POST['user_id'] == $user['id']): ?>
                                    <!-- Edit Form -->
                                    <tr>
                                        <td class="px-4 py-2"><?php echo $user['id']; ?></td>
                                        <td class="px-4 py-2">
                                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required pattern="[0-9]{10,15}">
                                        </td>
                                        <td class="px-4 py-2">
                                            <select name="role" class="px-3 py-2 border border-[#ddd] rounded-md focus:outline-none focus:ring-2 focus:ring-[#3498db]" required>
                                                <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                <option value="sales" <?php echo $user['role'] == 'sales' ? 'selected' : ''; ?>>Sales</option>
                                                <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2">
                                            <form method="post" class="flex gap-4">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="edit_user" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition">Save</button>
                                                <button type="button" onclick="window.location.reload();" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <!-- Display Row -->
                                    <tr>
                                        <td class="px-4 py-2"><?php echo $user['id']; ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['first_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['last_name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td class="px-4 py-2">
                                            <div class="flex gap-4">
                                                <form method="post">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="edit_mode" value="1">
                                                    <button type="submit" class="px-4 py-2 bg-[#2ecc71] text-white rounded-md hover:bg-[#27ae60] transition action-btn">Edit</button>
                                                </form>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="px-4 py-2 bg-[#e74c3c] text-white rounded-md hover:bg-[#c0392b] transition delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
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