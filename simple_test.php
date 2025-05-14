<?php
// The simplest possible connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple MySQL Connection Test</h2>";

// Don't even try to connect to a specific database yet
$servername = "localhost";
$username = "root";
$password = ""; // Empty password

echo "<p>Attempting to connect to MySQL with:</p>";
echo "<ul>";
echo "<li>Server: $servername</li>";
echo "<li>Username: $username</li>";
echo "<li>Password: [empty string]</li>";
echo "</ul>";

try {
    // Create connection without specifying a database
    $conn = new mysqli($servername, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        echo "<p style='color: red; font-weight: bold;'>Connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green; font-weight: bold;'>Connection successful!</p>";
        
        echo "<h3>Available Databases:</h3>";
        $result = $conn->query("SHOW DATABASES");
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row['Database'] . "</li>";
        }
        echo "</ul>";
        
        // Try to create the database if it doesn't exist
        echo "<h3>Creating zedauto_db if it doesn't exist:</h3>";
        if ($conn->query("CREATE DATABASE IF NOT EXISTS zedauto_db")) {
            echo "<p style='color: green;'>Database zedauto_db is now available.</p>";
        } else {
            echo "<p style='color: red;'>Failed to create database: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
}
?>