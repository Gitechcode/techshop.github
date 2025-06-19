<?php
// Common functions for TechShop

function get_products($limit = null, $category_id = null, $search = null) {
    global $pdo;
    
    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active'";
        
        $params = [];
        
        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error in get_products: " . $e->getMessage());
        return [];
    }
}

function get_product_by_slug($slug) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.slug = ? AND p.status = 'active'");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in get_product_by_slug: " . $e->getMessage());
        return false;
    }
}

function get_categories($active_only = true) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM categories";
        if ($active_only) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in get_categories: " . $e->getMessage());
        return [];
    }
}

function get_cart_items($user_id = null, $session_id = null) {
    global $pdo;
    
    try {
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock 
                                  FROM cart c 
                                  JOIN products p ON c.product_id = p.id 
                                  WHERE c.user_id = ?");
            $stmt->execute([$user_id]);
        } else if ($session_id) {
            $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock 
                                  FROM cart c 
                                  JOIN products p ON c.product_id = p.id 
                                  WHERE c.session_id = ?");
            $stmt->execute([$session_id]);
        } else {
            return [];
        }
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in get_cart_items: " . $e->getMessage());
        return [];
    }
}

function add_to_cart($product_id, $quantity = 1, $user_id = null, $session_id = null) {
    global $pdo;
    
    try {
        // Check if product exists and has stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        if ($product['stock'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }
        
        // Check if item already in cart
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$session_id, $product_id]);
        }
        
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing_item['id']]);
        } else {
            // Insert new item
            if ($user_id) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$session_id, $product_id, $quantity]);
            }
        }
        
        return ['success' => true, 'message' => 'Product added to cart'];
        
    } catch (Exception $e) {
        error_log("Error in add_to_cart: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add product to cart'];
    }
}

function get_image_url($image_filename, $type = 'products') {
    if (empty($image_filename)) {
        return FRONTEND_URL . '/assets/images/placeholder.png';
    }
    
    $image_path = UPLOADS_PATH . '/' . $type . '/' . $image_filename;
    if (file_exists($image_path)) {
        return UPLOADS_URL . '/' . $type . '/' . $image_filename;
    }
    
    return FRONTEND_URL . '/assets/images/placeholder.png';
}
?>
