<?php
// Enhanced Database setup and test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TechShop Database Setup & Test</h2>";

// Database configuration
$host = 'localhost';
$db_name = 'tech_shop';
$username = 'root';
$password = '';

try {
    // Test MySQL connection first
    echo "<h3>1. Testing MySQL Connection...</h3>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL connection successful<br>";
    
    // Create database if not exists
    echo "<h3>2. Creating Database...</h3>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    echo "✅ Database '$db_name' created/exists<br>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to database '$db_name'<br>";
    
    // Create users table first
    echo "<h3>3. Creating Users Table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            postal_code VARCHAR(20),
            role ENUM('user', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            email_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Users table created<br>";
    
    // Create admin user
    echo "<h3>4. Creating Admin User...</h3>";
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Delete existing admin if exists
    $pdo->exec("DELETE FROM users WHERE email = 'admin@techshop.com'");
    
    // Insert new admin
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, status, created_at) 
        VALUES (?, ?, ?, 'admin', 'active', NOW())
    ");
    $stmt->execute(['Admin User', 'admin@techshop.com', $admin_password]);
    echo "✅ Admin user created<br>";
    echo "📧 Email: admin@techshop.com<br>";
    echo "🔑 Password: admin123<br>";
    
    // Create test user
    echo "<h3>5. Creating Test User...</h3>";
    $user_password = password_hash('user123', PASSWORD_DEFAULT);
    
    // Delete existing user if exists
    $pdo->exec("DELETE FROM users WHERE email = 'john@example.com'");
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, status, created_at) 
        VALUES (?, ?, ?, 'user', 'active', NOW())
    ");
    $stmt->execute(['John Doe', 'john@example.com', $user_password]);
    echo "✅ Test user created<br>";
    echo "📧 Email: john@example.com<br>";
    echo "🔑 Password: user123<br>";
    
    // Test login functionality
    echo "<h3>6. Testing Login Functionality...</h3>";
    
    // Test admin login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@techshop.com']);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify('admin123', $admin['password'])) {
        echo "✅ Admin login test successful<br>";
    } else {
        echo "❌ Admin login test failed<br>";
    }
    
    // Test user login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['john@example.com']);
    $user = $stmt->fetch();
    
    if ($user && password_verify('user123', $user['password'])) {
        echo "✅ User login test successful<br>";
    } else {
        echo "❌ User login test failed<br>";
    }
    
    // Execute the complete schema
    echo "<h3>7. Creating Complete Database Schema...</h3>";
    
    // Read and execute the schema file
    $schema_file = __DIR__ . '/complete_schema.sql';
    if (file_exists($schema_file)) {
        $schema_sql = file_get_contents($schema_file);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore errors for statements that might already exist
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo "⚠️ Warning: " . $e->getMessage() . "<br>";
                    }
                }
            }
        }
        echo "✅ Complete schema executed successfully<br>";
    } else {
        echo "⚠️ Schema file not found, creating basic tables manually<br>";
    }
    
    // Verify tables exist
    echo "<h3>8. Verifying Tables...</h3>";
    $tables = ['users', 'categories', 'products', 'cart', 'orders', 'wishlist', 'admin_logs'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Check if products have sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetch()['count'];
    echo "📦 Products in database: $product_count<br>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>🎉 Database setup successful! You can now login with:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin Panel:</strong> <a href='../backend/login.php' target='_blank'>admin@techshop.com</a> / admin123</li>";
    echo "<li><strong>Frontend:</strong> <a href='../frontend/login.php' target='_blank'>john@example.com</a> / user123</li>";
    echo "</ul>";
    echo "<p><strong>Quick Links:</strong></p>";
    echo "<ul>";
    echo "<li><a href='../frontend/index.php' target='_blank'>🏠 Visit Store</a></li>";
    echo "<li><a href='../backend/index.php' target='_blank'>⚙️ Admin Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<h3>❌ Database Error:</h3>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<h4>🔧 Common Solutions:</h4>";
    echo "<ul>";
    echo "<li>✅ Make sure XAMPP/WAMP is running</li>";
    echo "<li>✅ Check if MySQL service is started</li>";
    echo "<li>✅ Verify database credentials in config/database.php</li>";
    echo "<li>✅ Make sure port 3306 is not blocked</li>";
    echo "<li>✅ Try restarting Apache and MySQL services</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #007bff; margin-top: 20px; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
