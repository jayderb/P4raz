<?php
// hash_passwords.php

// Include the database connection
require_once "config.php"; // Ensure this path is correct based on your directory structure

try {
    // Select all users with their current passwords
    $sql = "SELECT id, password FROM users";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through each user and hash their password
    foreach ($users as $user) {
        // Skip if the password is already hashed (optional check)
        if (password_get_info($user['password'])['algo'] == 0) { // If not hashed
            $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
            
            // Update the user's password in the database
            $update_sql = "UPDATE users SET password = :password WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
            $update_stmt->bindParam(":id", $user['id'], PDO::PARAM_INT);
            $update_stmt->execute();
            
            echo "Hashed password for user ID {$user['id']}.<br>";
        } else {
            echo "Password for user ID {$user['id']} is already hashed.<br>";
        }
    }

    echo "All passwords have been hashed successfully.";
} catch (PDOException $e) {
    // Handle any database errors
    echo "Error: " . $e->getMessage();
}

// Close the connection (optional, PDO closes automatically at script end)
unset($conn);
?>