<?php
// auth.php - Authentication & Authorization helper functions

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

/**
 * Check if the current user has a specific role
 * 
 * @param string|array $roles Single role or array of roles to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // If single role is passed as string, convert to array
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION["user_role"], $roles);
}

/**
 * Check if user has a specific permission
 * 
 * @param string $permission Permission name to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permission) {
    global $conn;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $sql = "SELECT COUNT(*) as has_perm FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role = ? AND p.name = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $_SESSION["user_role"], $permission);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $row['has_perm'] > 0;
    }
    
    return false;
}

/**
 * Require the user to be logged in to access the page
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirection after login
        $_SESSION["redirect_url"] = $_SERVER["REQUEST_URI"];
        header("Location: login.php");
        exit;
    }
}

/**
 * Require specific role(s) to access the page
 * 
 * @param string|array $roles Role or roles required
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        header("Location: access_denied.php");
        exit;
    }
}

/**
 * Require specific permission to access the page
 * 
 * @param string $permission Permission required
 */
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        header("Location: access_denied.php");
        exit;
    }
}

/**
 * Apply security headers
 */
function applySecurityHeaders() {
    // Prevent clickjacking
    header("X-Frame-Options: DENY");
    // Enable XSS protection
    header("X-XSS-Protection: 1; mode=block");
    // Prevent MIME type sniffing
    header("X-Content-Type-Options: nosniff");
    // Set Content Security Policy
    header("Content-Security-Policy: default-src 'self'");
    // HTTP Strict Transport Security
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
?>

<?php
// Example usage in a protected page (customer_dashboard.php)

// Start the session
session_start();

// Include authentication utilities
require_once 'auth.php';

// Include database connection
require_once 'config.php';

// Apply security headers
applySecurityHeaders();

// Require user to have customer role
requireRole('customer');

// Now this page is only accessible to customers
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard - Garage Management System</title>
    <!-- CSS and other head elements -->
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</h1>
        <nav>
            <ul>
                <?php if (hasPermission('view_orders')): ?>
                <li><a href="my_orders.php">My Orders</a></li>
                <?php endif; ?>
                
                <?php if (hasPermission('create_orders')): ?>
                <li><a href="place_order.php">Order Parts</a></li>
                <?php endif; ?>
                
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <!-- Dashboard content -->
        <section class="dashboard-summary">
            <h2>Your Account Summary</h2>
            <!-- Customer-specific dashboard content -->
        </section>
        
        <?php if (hasPermission('view_orders')): ?>
        <section class="recent-orders">
            <h2>Recent Orders</h2>
            <?php
            // Get recent orders for this customer
            $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC LIMIT 5";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    echo '<table>';
                    echo '<tr><th>Order ID</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr>';
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>' . $row['order_id'] . '</td>';
                        echo '<td>' . $row['order_date'] . '</td>';
                        echo '<td>' . $row['status'] . '</td>';
                        echo '<td>$' . $row['total_amount'] . '</td>';
                        echo '<td>';
                        echo '<a href="view_order.php?id=' . $row['order_id'] . '">View</a>';
                        
                        // Check if order can be cancelled
                        if ($row['status'] == 'pending' && hasPermission('cancel_orders')) {
                            // Only allow cancellation within 24 hours
                            $order_time = strtotime($row['order_date']);
                            $current_time = time();
                            $time_diff = $current_time - $order_time;
                            
                            if ($time_diff < 86400) { // 24 hours in seconds
                                echo ' | <a href="cancel_order.php?id=' . $row['order_id'] . '">Cancel</a>';
                            }
                        }
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>No recent orders found.</p>';
                }
                
                mysqli_stmt_close($stmt);
            }
            ?>
        </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <!-- Footer content -->
    </footer>
</body>
</html>