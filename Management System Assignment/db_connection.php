<?php

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'zedauto_db');

function connectionToDatabase() {
    static $conn = null;

    // Return existing connection if already established
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }

    try {
        // Create a new MySQLi connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check for connection errors
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Set character set to UTF-8
        if (!$conn->set_charset('utf8mb4')) {
            throw new Exception("Failed to set character set: " . $conn->error);
        }

        return $conn;
    } catch (Exception $e) {
        // Log error (in production, configure a proper logging mechanism)
        error_log($e->getMessage());

        // In production, avoid exposing error details to users
        if (defined('ENV') && ENV === 'production') {
            exit('Unable to connect to the database. Please try again later.');
        } else {
            exit($e->getMessage());
        }
    }
}

?>