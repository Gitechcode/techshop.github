<?php
require_once dirname(__DIR__) . '/config/config.php';

$order_number = isset($_GET['order']) ? sanitizeInput($_GET['order']) : null;
$order = null;

if ($order_number) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_number = ? AND o.user_id = ?
        ");
        // Ensure user can only see their own order success page
        if (isLoggedIn()) {
            $stmt->execute([$order_number, $_SESSION['user_id']]);
            $order = $stmt->fetch();
        }
    } catch (PDOException $e) {
        // Log error
        if(DEVELOPMENT) error_log("Error fetching order details for success page: " . $e->getMessage());
        $order = null; // Ensure order is null on error
    }
}

if (!$order) {
    // If order not found or not belonging to user, redirect or show generic message
    setAlert('Order details not found or you do not have permission to view this order.', 'warning');
    redirect(FRONTEND_URL . '/orders.php');
}

$page_title = 'Order Successful - ' . htmlspecialchars($order['order_number']);
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    <h1 class="display-5 fw-bold text-success">Thank You!</h1>
                    <p class="lead">Your order has been placed successfully.</p>
                    
                    <hr class="my-4">
                    
                    <h4 class="mb-3">Order Summary</h4>
                    <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))); ?></p>
                    <p>An email confirmation has been sent to <strong><?php echo htmlspecialchars($order['user_email']); ?></strong> with your order details.</p>
                    
                    <div class="mt-4">
                        <p class="text-muted">You can track your order status in your account dashboard.</p>
                        <a href="<?php echo FRONTEND_URL; ?>/orders.php" class="btn btn-primary me-2">
                            <i class="fas fa-receipt me-1"></i> View My Orders
                        </a>
                        <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-outline-secondary">
                            <i class="fas fa-shopping-bag me-1"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
