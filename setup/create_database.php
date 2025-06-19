<?php
// Database setup script
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/complete_database.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
    echo "<h2 style='color: #28a745;'>✅ Database Setup Complete!</h2>";
    echo "<p><strong>Database:</strong> tech_shop</p>";
    echo "<p><strong>Admin Login:</strong> admin@techshop.com / password</p>";
    echo "<p><strong>User Login:</strong> john@example.com / password</p>";
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='../frontend/index.php' target='_blank'>Visit Frontend</a></li>";
    echo "<li><a href='../backend/login.php' target='_blank'>Admin Panel</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #dc3545; border-radius: 8px; background: #f8d7da;'>";
    echo "<h2 style='color: #dc3545;'>❌ Database Setup Failed!</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
