<?php
// Database Configuration
include_once 'config.php';

try {
    // Get connection
    $conn = getConnection();
    
    // Add is_admin column if it doesn't exist
    $sql = "SHOW COLUMNS FROM users LIKE 'is_admin'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
        if ($conn->query($sql) === TRUE) {
            echo "Column 'is_admin' added successfully.<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    } else {
        echo "Column 'is_admin' already exists.<br>";
    }
    
    // Create admin user
    $admin_name = 'Tuba Mirza';
    $admin_email = 'tubamirza822@gmail.com';
    $admin_password = 'Tuba@12';
    $admin_hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Insert admin user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $admin_name, $admin_email, $admin_hashed_password);
        
        if ($stmt->execute()) {
            echo "Admin user created successfully.<br>";
        } else {
            echo "Error creating admin user: " . $stmt->error . "<br>";
        }
    } else {
        // Update existing user to be admin
        $user_id = $result->fetch_assoc()['id'];
        $stmt = $conn->prepare("UPDATE users SET name = ?, password = ?, is_admin = 1 WHERE id = ?");
        $stmt->bind_param("ssi", $admin_name, $admin_hashed_password, $user_id);
        
        if ($stmt->execute()) {
            echo "Admin user updated successfully.<br>";
        } else {
            echo "Error updating admin user: " . $stmt->error . "<br>";
        }
    }
    
    $stmt->close();
    
    echo "<p>Admin setup completed. You can now <a href='admin/login.php'>login to the admin panel</a> using:</p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> " . $admin_email . "</li>";
    echo "<li><strong>Password:</strong> " . $admin_password . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

$conn->close();
?> 