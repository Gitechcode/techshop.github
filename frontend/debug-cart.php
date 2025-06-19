<?php
require_once '../config/config.php';

echo "<h2>Cart Debug Information</h2>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Database connected. Active products: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Check session
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User logged in: " . (isLoggedIn() ? 'Yes (ID: ' . $_SESSION['user_id'] . ')' : 'No') . "</p>";

// Check cart table structure
try {
    $stmt = $pdo->query("DESCRIBE cart");
    $columns = $stmt->fetchAll();
    echo "<h3>Cart Table Structure:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Cart table error: " . $e->getMessage() . "</p>";
}

// Test cart count function
try {
    $count = getCartCount();
    echo "<p>Current cart count: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Cart count error: " . $e->getMessage() . "</p>";
}

// Show current cart contents
try {
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ?");
        $stmt->execute([session_id()]);
    }
    
    $cart_items = $stmt->fetchAll();
    echo "<h3>Current Cart Contents:</h3>";
    if (empty($cart_items)) {
        echo "<p>Cart is empty</p>";
    } else {
        echo "<ul>";
        foreach ($cart_items as $item) {
            echo "<li>" . htmlspecialchars($item['name']) . " (Qty: " . $item['quantity'] . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Cart contents error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='shop.php'>← Back to Shop</a></p>";
?>
