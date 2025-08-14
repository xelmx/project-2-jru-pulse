<?php

// Make sure this path points to your database connection file
require_once 'config/connection.php'; 

echo "<h1>Admin User Creation Script</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>Database connection successful.</p>";
} catch (Exception $e) {
    die("<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>");
}

/*
$firstName = '';
$lastName = '';
$email = '';
$password = 'password123'; // A temporary PW
$role = 'admin';
*/

// --- Hashing the Password (for security) ---
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

echo "<hr>";
echo "<p><strong>Attempting to create admin user:</strong></p>";
echo "<ul>";
echo "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
echo "<li><strong>Password (Plain Text):</strong> " . htmlspecialchars($password) . "</li>";
echo "</ul><hr>";

try {
    $query = "INSERT INTO users (first_name, last_name, email, password_hash, role) 
              VALUES (:first_name, :last_name, :email, :password_hash, :role)
              ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), password_hash = VALUES(password_hash)";
    
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':first_name', $firstName);
    $stmt->bindParam(':last_name', $lastName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>SUCCESS: Admin user created or updated successfully!</h2>";
        echo "<p>You can now log in with:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
        echo "<li><strong>Password:</strong> " . htmlspecialchars($password) . "</li>";
        echo "</ul>";
       // echo "<p style='color: red; font-weight: bold;'>IMPORTANT: Please delete this `create_my_admin.php` file from your server now for security reasons.</p>";
    } else {
        echo "<h2 style='color: red;'>ERROR: Could not create admin user.</h2>";
        print_r($stmt->errorInfo());
    }

} catch (PDOException $e) {
    echo "<h2>DATABASE ERROR: " . $e->getMessage() . "</h2>";
}

?>