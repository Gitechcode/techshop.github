<?php
require_once '../config/config.php';

// Check if logged in as admin
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $status = sanitizeInput($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $success = 'Order status updated successfully!';
        } else {
            $error = 'Error updating order status.';
        }
    } catch(Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get order details if viewing a specific order
$order = null;
$order_items = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as user_name, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name as product_name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
        }
    } catch(Exception $e) {
        $error = 'Error loading order details: ' . $e->getMessage();
    }
}

// Get all orders for listing
$orders = [];
try {
    $stmt = $pdo->query("
        SELECT o.*, u.name as user_name, u.email as user_email,
        COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
} catch(Exception $e) {
    $error = 'Error loading orders: ' . $e->getMessage();
}

$page_title = $order ? 'Order Details #' . $order['id'] : 'Orders Management';
include 'includes/header.php';
?>

<style>
.product-image-admin {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e3e6f0;
}
</style>

<div class="container-fluid">
    <?php if ($order): ?>
    <!-- Order Details View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order #<?php echo $order['id']; ?> Details</h1>
        <a href="orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Orders
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Order Items</h6>
                    <span class="badge bg-<?php echo getOrderStatusBadgeClass($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($order_items)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No items found</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $image_url = '';
                                            if (!empty($item['image'])) {
                                                // Check multiple possible image locations
                                                $possible_paths = [
                                                    '../public/uploads/products/' . $item['image'],
                                                    '../uploads/products/' . $item['image'],
                                                    '../public/uploads/' . $item['image'],
                                                    UPLOADS_PATH . '/products/' . $item['image']
                                                ];
                                                
                                                foreach ($possible_paths as $path) {
                                                    if (file_exists($path)) {
                                                        $image_url = str_replace('../', FRONTEND_URL . '/', $path);
                                                        break;
                                                    }
                                                }
                                            }
                                            
                                            if (empty($image_url)) {
                                                $image_url = FRONTEND_URL . '/assets/images/placeholder.png';
                                            }
                                            ?>
                                            <img src="<?php echo $image_url; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="product-image-admin me-2"
                                                 onerror="this.src='<?php echo FRONTEND_URL; ?>/assets/images/placeholder.png';">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><?php echo formatPrice($order['total_amount'] - $order['shipping_amount'] - $order['tax_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                    <td><?php echo formatPrice($order['shipping_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                    <td><?php echo formatPrice($order['tax_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Customer Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
                </div>
            </div>
            
            <!-- Shipping Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Shipping Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                    <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($order['shipping_postal']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                </div>
            </div>
            
            <!-- Order Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Order Status</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Orders List View -->
    <h1 class="h3 mb-4 text-gray-800">Orders Management</h1>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Orders</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No orders found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo $order['item_count']; ?></td>
                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getOrderStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<?php
function getOrderStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>
