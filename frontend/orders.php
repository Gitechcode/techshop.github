<?php
$page_title = 'My Orders';
include 'includes/header.php';

requireLogin();

// Get user orders
try {
    // Get user orders with better error handling
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Orders page error: " . $e->getMessage());
    $orders = [];
    setAlert('Error loading orders. Please try again later.', 'danger');
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-box me-2"></i>My Orders</h2>
                <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>

            <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-box-open fa-5x text-muted"></i>
                </div>
                <h4 class="text-muted">No Orders Yet</h4>
                <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
            <?php else: ?>
            
            <div class="row">
                <?php foreach ($orders as $order): ?>
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="mb-2">Items (<?php echo $order['item_count']; ?>):</h6>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($order['items']); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </button>
                                        <?php if ($order['status'] === 'delivered'): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-star me-1"></i>Review
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Progress -->
                            <div class="mt-3">
                                <div class="progress" style="height: 6px;">
                                    <?php
                                    $progress = 0;
                                    switch ($order['status']) {
                                        case 'pending': $progress = 25; break;
                                        case 'processing': $progress = 50; break;
                                        case 'shipped': $progress = 75; break;
                                        case 'delivered': $progress = 100; break;
                                    }
                                    ?>
                                    <div class="progress-bar bg-<?php echo getOrderStatusColor($order['status']); ?>" 
                                         style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Pending</small>
                                    <small class="text-muted">Processing</small>
                                    <small class="text-muted">Shipped</small>
                                    <small class="text-muted">Delivered</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function viewOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    modal.show();
    
    fetch(`<?php echo FRONTEND_URL; ?>/api/order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading order details.</div>';
        });
}
</script>

<?php
// Replace the getOrderStatusColor function with better error handling:
function getOrderStatusColor($status) {
    $status_colors = [
        'pending' => 'warning',
        'processing' => 'info', 
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return isset($status_colors[$status]) ? $status_colors[$status] : 'secondary';
}
?>
