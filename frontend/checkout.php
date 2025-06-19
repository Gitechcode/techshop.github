<?php
$page_title = 'Checkout';
include 'includes/header.php';

requireLogin();

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.sale_price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    redirect(FRONTEND_URL . '/cart.php');
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
}

$shipping = $subtotal > 100 ? 0 : 10; // Free shipping over $100
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_name = sanitizeInput($_POST['shipping_name']);
    $shipping_address = sanitizeInput($_POST['shipping_address']);
    $shipping_city = sanitizeInput($_POST['shipping_city']);
    $shipping_postal = sanitizeInput($_POST['shipping_postal']);
    $shipping_phone = sanitizeInput($_POST['shipping_phone']);
    
    $billing_same = isset($_POST['billing_same']);
    $billing_name = $billing_same ? $shipping_name : sanitizeInput($_POST['billing_name']);
    $billing_address = $billing_same ? $shipping_address : sanitizeInput($_POST['billing_address']);
    $billing_city = $billing_same ? $shipping_city : sanitizeInput($_POST['billing_city']);
    $billing_postal = $billing_same ? $shipping_postal : sanitizeInput($_POST['billing_postal']);
    
    $payment_method = sanitizeInput($_POST['payment_method']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    if (empty($shipping_name) || empty($shipping_address) || empty($shipping_city) || empty($payment_method)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $order_number = generateOrderNumber();
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_amount, shipping_amount, tax_amount, 
                    shipping_name, shipping_address, shipping_city, shipping_postal, shipping_phone,
                    billing_name, billing_address, billing_city, billing_postal,
                    payment_method, notes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $order_number, $total, $shipping, $tax,
                $shipping_name, $shipping_address, $shipping_city, $shipping_postal, $shipping_phone,
                $billing_name, $billing_address, $billing_city, $billing_postal,
                $payment_method, $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Create order items
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ?: $item['price'];
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price, total) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $price, $price * $item['quantity']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            // Redirect to success page
            redirect(FRONTEND_URL . '/order-success.php?order=' . $order_number);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error processing order. Please try again.';
        }
    }
}

// Get user data for pre-filling
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Checkout
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Shipping Information -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="shipping_name" name="shipping_name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                          rows="2" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="shipping_city" name="shipping_city" 
                                           value="<?php echo htmlspecialchars($user['city']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_postal" class="form-label">Postal Code *</label>
                                    <input type="text" class="form-control" id="shipping_postal" name="shipping_postal" 
                                           value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Information -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-file-invoice me-2"></i>Billing Information
                            </h5>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="billing_same" name="billing_same" checked>
                                <label class="form-check-label" for="billing_same">
                                    Same as shipping address
                                </label>
                            </div>

                            <div id="billing_fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="billing_name" name="billing_name">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="billing_address" class="form-label">Address</label>
                                    <textarea class="form-control" id="billing_address" name="billing_address" rows="2"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="billing_city" name="billing_city">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="billing_postal" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="billing_postal" name="billing_postal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">
                                <i class="fas fa-credit-card me-2"></i>Payment Method
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                        <label class="form-check-label" for="credit_card">
                                            <i class="fas fa-credit-card me-2"></i>Credit/Debit Card
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                        <label class="form-check-label" for="paypal">
                                            <i class="fab fa-paypal me-2"></i>PayPal
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="card_details" class="card bg-light p-3">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="card_cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="card_cvv" placeholder="123">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card_expiry" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="card_expiry" placeholder="MM/YY">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_name" class="form-label">Cardholder Name</label>
                                        <input type="text" class="form-control" id="card_name" placeholder="John Doe">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Order Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any special instructions for your order..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-lock me-2"></i>Place Order - <?php echo formatPrice($total); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo SITE_URL . '/' . ($item['image'] ?: 'assets/images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                        </div>
                        <div class="text-end">
                            <?php 
                            $price = $item['sale_price'] ?: $item['price'];
                            echo formatPrice($price * $item['quantity']); 
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span><?php echo $shipping > 0 ? formatPrice($shipping) : 'Free'; ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span><?php echo formatPrice($tax); ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span class="text-success"><?php echo formatPrice($total); ?></span>
                    </div>

                    <?php if ($shipping == 0): ?>
                    <div class="alert alert-success mt-3 py-2">
                        <small><i class="fas fa-truck me-1"></i>Free shipping applied!</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="card shadow-sm mt-4">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt text-success mb-2" style="font-size: 2rem;"></i>
                    <h6>Secure Checkout</h6>
                    <small class="text-muted">Your payment information is encrypted and secure.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Toggle billing address fields
document.getElementById('billing_same').addEventListener('change', function() {
    const billingFields = document.getElementById('billing_fields');
    billingFields.style.display = this.checked ? 'none' : 'block';
});

// Toggle payment method details
document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const cardDetails = document.getElementById('card_details');
        cardDetails.style.display = this.value === 'credit_card' ? 'block' : 'none';
    });
});

// Format card number input
document.getElementById('card_number').addEventListener('input', function() {
    let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    this.value = formattedValue;
});

// Format expiry date input
document.getElementById('card_expiry').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    this.value = value;
});
</script>
