<?php
$page_title = 'My Wishlist';
include 'includes/header.php';

requireLogin();

// Get wishlist items
$stmt = $pdo->prepare("
    SELECT w.*, p.name, p.price, p.image, p.stock, p.status
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_items = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-heart me-2 text-danger"></i>My Wishlist</h2>
                <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>

            <?php if (empty($wishlist_items)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-heart fa-5x text-muted"></i>
                </div>
                <h4 class="text-muted">Your Wishlist is Empty</h4>
                <p class="text-muted">Save items you love to your wishlist and shop them later.</p>
                <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
            <?php else: ?>
            
            <div class="row">
                <?php foreach ($wishlist_items as $item): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm product-card">
                        <div class="position-relative">
                            <img src="<?php echo SITE_URL . '/' . ($item['image'] ?: 'assets/images/placeholder.jpg'); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            
                            <!-- Remove from Wishlist -->
                            <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                                    onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                                    title="Remove from Wishlist">
                                <i class="fas fa-times"></i>
                            </button>
                            
                            <?php if ($item['status'] !== 'active' || $item['stock'] <= 0): ?>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-danger">Out of Stock</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h5 mb-0 text-primary"><?php echo formatPrice($item['price']); ?></span>
                                    <?php if ($item['stock'] > 0): ?>
                                    <small class="text-success">In Stock</small>
                                    <?php else: ?>
                                    <small class="text-danger">Out of Stock</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($item['status'] === 'active' && $item['stock'] > 0): ?>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                        <i class="fas fa-shopping-cart me-1"></i>Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fas fa-ban me-1"></i>Unavailable
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo FRONTEND_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Bulk Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><strong><?php echo count($wishlist_items); ?></strong> items in your wishlist</span>
                                <div>
                                    <button class="btn btn-outline-danger" onclick="clearWishlist()">
                                        <i class="fas fa-trash me-1"></i>Clear Wishlist
                                    </button>
                                    <button class="btn btn-primary" onclick="addAllToCart()">
                                        <i class="fas fa-shopping-cart me-1"></i>Add All to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function removeFromWishlist(productId) {
    if (confirm('Remove this item from your wishlist?')) {
        fetch('<?php echo FRONTEND_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error removing item from wishlist');
            }
        });
    }
}

function addToCart(productId) {
    fetch('<?php echo FRONTEND_URL; ?>/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Item added to cart!', 'success');
            updateCartCount();
        } else {
            showToast('Error adding item to cart', 'error');
        }
    });
}

function clearWishlist() {
    if (confirm('Are you sure you want to clear your entire wishlist?')) {
        fetch('<?php echo FRONTEND_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'clear'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error clearing wishlist');
            }
        });
    }
}

function addAllToCart() {
    if (confirm('Add all available items to cart?')) {
        fetch('<?php echo FRONTEND_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_all_to_cart'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`${data.added_count} items added to cart!`, 'success');
                updateCartCount();
            } else {
                showToast('Error adding items to cart', 'error');
            }
        });
    }
}
</script>

<style>
.product-card {
    transition: transform 0.2s ease-in-out;
}

.product-card:hover {
    transform: translateY(-5px);
}
</style>
