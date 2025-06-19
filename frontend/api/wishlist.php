<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $product_id = (int)$input['product_id'];
            
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            // Add to wishlist (ignore if already exists)
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
            break;
            
        case 'remove':
            $product_id = (int)$input['product_id'];
            
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Wishlist cleared']);
            break;
            
        case 'add_all_to_cart':
            // Get all wishlist items that are in stock
            $stmt = $pdo->prepare("
                SELECT w.product_id 
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ? AND p.status = 'active' AND p.stock > 0
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $products = $stmt->fetchAll();
            
            $added_count = 0;
            foreach ($products as $product) {
                // Add to cart
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO cart (user_id, product_id, quantity) 
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE quantity = quantity + 1
                    ");
                    $stmt->execute([$_SESSION['user_id'], $product['product_id']]);
                } else {
                    $session_id = session_id();
                    $stmt = $pdo->prepare("
                        INSERT INTO cart (session_id, product_id, quantity) 
                        VALUES (?, ?, 1)
                        ON DUPLICATE KEY UPDATE quantity = quantity + 1
                    ");
                    $stmt->execute([$session_id, $product['product_id']]);
                }
                $added_count++;
            }
            
            echo json_encode(['success' => true, 'added_count' => $added_count]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
