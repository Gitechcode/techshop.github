<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Generate HTML
ob_start();
?>
<div class="row">
    <div class="col-md-6">
        <h6>Order Information</h6>
        <table class="table table-sm">
            <tr><td><strong>Order Number:</strong></td><td><?php echo htmlspecialchars($order['order_number']); ?></td></tr>
            <tr><td><strong>Date:</strong></td><td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td></tr>
            <tr><td><strong>Status:</strong></td><td><span class="badge bg-<?php echo getOrderStatusColor($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span></td></tr>
            <tr><td><strong>Payment Method:</strong></td><td><?php echo ucfirst($order['payment_method']); ?></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Shipping Address</h6>
        <address>
            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
        </address>
    </div>
</div>

<h6 class="mt-4">Order Items</h6>
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
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="<?php echo SITE_URL . '/' . ($item['image'] ?: 'assets/images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                    </div>
                </td>
                <td><?php echo formatPrice($item['price']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Subtotal:</th>
                <th><?php echo formatPrice($order['subtotal']); ?></th>
            </tr>
            <tr>
                <th colspan="3">Tax:</th>
                <th><?php echo formatPrice($order['tax_amount']); ?></th>
            </tr>
            <tr>
                <th colspan="3">Shipping:</th>
                <th><?php echo formatPrice($order['shipping_amount']); ?></th>
            </tr>
            <tr class="table-primary">
                <th colspan="3">Total:</th>
                <th><?php echo formatPrice($order['total_amount']); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
<?php
$html = ob_get_clean();

echo json_encode(['success' => true, 'html' => $html]);

function getOrderStatusColor($status) {
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
