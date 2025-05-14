<?php
// Test file to verify PHP and database connection

// 1. Test if PHP is working properly
echo "<h2>PHP Test</h2>";
echo "PHP is working correctly if you can see this message.<br>";
echo "PHP Version: " . phpversion() . "<br><br>";

// 2. Test database connection
echo "<h2>Database Connection Test</h2>";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "zedauto_db";

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo "<span style='color: red;'>Connection failed: " . $conn->connect_error . "</span>";
    } else {
        echo "<span style='color: green;'>Database connection successful!</span><br>";
        
        // Check if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "<span style='color: green;'>The 'users' table exists.</span><br>";
            
            // Count users in the table
            $count = $conn->query("SELECT COUNT(*) as total FROM users");
            $data = $count->fetch_assoc();
            echo "There are " . $data['total'] . " users in the database.<br>";
        } else {
            echo "<span style='color: red;'>The 'users' table does not exist!</span><br>";
            echo "You need to create the 'users' table. See instructions below.";
        }
    }
    $conn->close();
} catch (Exception $e) {
    echo "<span style='color: red;'>An error occurred: " . $e->getMessage() . "</span>";
}
?>

<h2>Troubleshooting Instructions</h2>
<ol>
    <li>If you see "PHP is working correctly" but get a database error, make sure:
        <ul>
            <li>MySQL service is running in XAMPP</li>
            <li>The database "zedauto_db" exists</li>
            <li>Your username and password are correct (default for XAMPP is usually username "root" with empty password)</li>
        </ul>
    </li>
    <li>If the users table doesn't exist, go to phpMyAdmin (http://localhost/phpmyadmin) and run this SQL:
        <pre>
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'sales', 'customer') NOT NULL
);

-- Add a test user with password "password"
INSERT INTO users (email, password, role) 
VALUES ('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');
        </pre>
    </li>
    <li>If you don't see any PHP output (just the raw code), your server isn't processing PHP files correctly. Make sure:
        <ul>
            <li>Apache service is running in XAMPP</li>
            <li>You're accessing the file through http://localhost/ not as a file:/// URL</li>
            <li>The file has a .php extension</li>
        </ul>
    </li>
</ol>

<h2>Next Steps</h2>
<p>Once this test works properly, try your original login form again. Make sure login_process.php is in the same folder as your HTML file.</p>