<?php
require_once dirname(__DIR__) . '/config/config.php';

$product_slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : null;

if (!$product_slug) {
    setAlert('Product not specified.', 'danger');
    redirect(FRONTEND_URL . '/shop.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.slug = ? AND p.status = 'active'
    ");
    $stmt->execute([$product_slug]);
    $product = $stmt->fetch();

    if (!$product) {
        setAlert('Product not found or is unavailable.', 'warning');
        redirect(FRONTEND_URL . '/shop.php');
    }

    // Fetch product attributes
    $attr_stmt = $pdo->prepare("SELECT attribute_name, attribute_value FROM product_attributes WHERE product_id = ? ORDER BY attribute_name");
    $attr_stmt->execute([$product['id']]);
    $attributes = $attr_stmt->fetchAll();

    // Fetch reviews (basic example)
    $reviews_stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? AND r.status = 'approved' 
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $reviews_stmt->execute([$product['id']]);
    $reviews = $reviews_stmt->fetchAll();
    
    // Calculate average rating
    $avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND status = 'approved'");
    $avg_rating_stmt->execute([$product['id']]);
    $rating_info = $avg_rating_stmt->fetch();
    $avg_rating = $rating_info['avg_rating'] ? round($rating_info['avg_rating'], 1) : 0;
    $total_reviews = $rating_info['total_reviews'] ?: 0;


    // Fetch related products (simple example: same category, not current product)
    $related_stmt = $pdo->prepare("
        SELECT id, name, slug, price, sale_price, image 
        FROM products 
        WHERE category_id = ? AND id != ? AND status = 'active' 
        ORDER BY RAND() LIMIT 4 
    "); // RAND() can be slow on large tables
    $related_stmt->execute([$product['category_id'], $product['id']]);
    $related_products = $related_stmt->fetchAll();

} catch (PDOException $e) {
    if (DEVELOPMENT) {
        setAlert('Error fetching product details: ' . $e->getMessage(), 'danger');
    } else {
        setAlert('An error occurred while loading product details.', 'danger');
    }
    redirect(FRONTEND_URL . '/shop.php');
}

$page_title = htmlspecialchars($product['name']);
// $page_specific_css = ['product-page.css']; // If you have specific CSS
// $page_specific_js = ['product-page.js']; // If you have specific JS for image gallery, etc.

include 'includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo FRONTEND_URL; ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo FRONTEND_URL; ?>/shop.php">Shop</a></li>
            <?php if ($product['category_id'] && $product['category_name']): ?>
            <li class="breadcrumb-item"><a href="<?php echo FRONTEND_URL . '/shop.php?category=' . htmlspecialchars($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Image Gallery -->
        <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
        <?php
        // Fix image path - check multiple possible locations
        $main_image_url = FRONTEND_URL . '/assets/images/placeholder.png'; // Default fallback
        
        if (!empty($product['image'])) {
            // Try different possible paths
            $possible_paths = [
                UPLOADS_URL . '/' . $product['image'],
                UPLOADS_URL . '/products/' . $product['image'],
                FRONTEND_URL . '/uploads/' . $product['image'],
                FRONTEND_URL . '/uploads/products/' . $product['image']
            ];
            
            foreach ($possible_paths as $path) {
                $file_path = str_replace([FRONTEND_URL, UPLOADS_URL], [dirname(__DIR__), dirname(__DIR__) . '/uploads'], $path);
                if (file_exists($file_path)) {
                    $main_image_url = $path;
                    break;
                }
            }
        }
        ?>
        <img id="main-product-image" src="<?php echo $main_image_url; ?>" 
             class="card-img-top product-main-image" alt="<?php echo htmlspecialchars($product['name']); ?>" 
             style="max-height: 500px; object-fit: contain; padding: 1rem;"
             onerror="this.src='<?php echo FRONTEND_URL; ?>/assets/images/placeholder.png'">
    </div>
    <?php 
    $gallery_images = $product['gallery'] ? json_decode($product['gallery'], true) : [];
    if (!empty($gallery_images)): 
    ?>
    <div class="product-thumbnails d-flex mt-3">
        <?php foreach ($gallery_images as $thumb_filename): ?>
        <?php
        // Fix thumbnail paths too
        $thumb_url = FRONTEND_URL . '/assets/images/placeholder.png';
        $thumb_possible_paths = [
            UPLOADS_URL . '/' . $thumb_filename,
            UPLOADS_URL . '/products/' . $thumb_filename,
            FRONTEND_URL . '/uploads/' . $thumb_filename,
            FRONTEND_URL . '/uploads/products/' . $thumb_filename
        ];
        
        foreach ($thumb_possible_paths as $path) {
            $file_path = str_replace([FRONTEND_URL, UPLOADS_URL], [dirname(__DIR__), dirname(__DIR__) . '/uploads'], $path);
            if (file_exists($file_path)) {
                $thumb_url = $path;
                break;
            }
        }
        ?>
        <img src="<?php echo $thumb_url; ?>" 
             class="img-thumbnail me-2" alt="Product thumbnail" 
             style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
             onclick="document.getElementById('main-product-image').src='<?php echo $thumb_url; ?>'"
             onerror="this.src='<?php echo FRONTEND_URL; ?>/assets/images/placeholder.png'">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

        <!-- Product Details -->
        <div class="col-lg-6">
            <h1 class="mb-3 product-title-large"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="d-flex align-items-center mb-3">
                <?php if ($total_reviews > 0): ?>
                    <div class="star-rating me-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-muted">(<?php echo $avg_rating; ?>/5 based on <?php echo $total_reviews; ?> reviews)</span>
                <?php else: ?>
                    <span class="text-muted">No reviews yet.</span>
                <?php endif; ?>
            </div>

            <p class="text-muted small">Brand: <strong class="text-dark"><?php echo htmlspecialchars($product['brand'] ?: 'N/A'); ?></strong> | SKU: <strong class="text-dark"><?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></strong></p>

            <div class="mb-3">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="h2 text-danger fw-bold me-2"><?php echo formatPrice($product['sale_price']); ?></span>
                    <span class="h4 text-muted text-decoration-line-through"><?php echo formatPrice($product['price']); ?></span>
                    <?php 
                        $discount_percentage = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                    ?>
                    <span class="badge bg-success ms-2 fs-6"><?php echo $discount_percentage; ?>% OFF</span>
                <?php else: ?>
                    <span class="h2 text-primary fw-bold"><?php echo formatPrice($product['price']); ?></span>
                <?php endif; ?>
            </div>

            <p class="lead short-description mb-4"><?php echo nl2br(htmlspecialchars($product['short_description'] ?: 'No short description available.')); ?></p>

            <div class="mb-4">
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i> In Stock (<?php echo $product['stock']; ?> available)</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6"><i class="fas fa-times-circle me-1"></i> Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart Form -->
            <form id="add-to-cart-form" class="mb-4">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="quantity" class="col-form-label">Quantity:</label>
                    </div>
                    <div class="col-auto" style="max-width: 100px;">
                        <input type="number" class="form-control text-center" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock'] > 0 ? $product['stock'] : 1; ?>" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                    </div>
                    <div class="col">
                        <button type="submit" class="btn btn-primary btn-lg w-100 add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </form>
            
            <button class="btn btn-outline-danger btn-sm wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                <i class="far fa-heart me-1"></i> Add to Wishlist
            </button>

            <!-- Social Share (Optional) -->
            <div class="mt-4">
                <p class="mb-1 small text-muted">Share this product:</p>
                <a href="#" class="btn btn-outline-secondary btn-sm me-1" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="btn btn-outline-secondary btn-sm me-1" title="Share on Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="btn btn-outline-secondary btn-sm" title="Share on Pinterest"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </div>

    <!-- Product Information Tabs -->
    <div class="mt-5">
        <ul class="nav nav-tabs" id="productTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-content" type="button" role="tab" aria-controls="description-content" aria-selected="true">Description</button>
            </li>
            <?php if (!empty($attributes)): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes-content" type="button" role="tab" aria-controls="attributes-content" aria-selected="false">Specifications</button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-content" type="button" role="tab" aria-controls="reviews-content" aria-selected="false">Reviews (<?php echo $total_reviews; ?>)</button>
            </li>
        </ul>
        <div class="tab-content card card-body border-top-0 rounded-bottom" id="productTabContent">
            <div class="tab-pane fade show active p-3" id="description-content" role="tabpanel" aria-labelledby="description-tab">
                <?php echo nl2br(htmlspecialchars($product['description'] ?: 'No detailed description available.')); ?>
            </div>
            <?php if (!empty($attributes)): ?>
            <div class="tab-pane fade p-3" id="attributes-content" role="tabpanel" aria-labelledby="attributes-tab">
                <table class="table table-striped table-sm">
                    <tbody>
                        <?php foreach ($attributes as $attr): ?>
                        <tr>
                            <th scope="row" style="width: 30%;"><?php echo htmlspecialchars($attr['attribute_name']); ?></th>
                            <td><?php echo htmlspecialchars($attr['attribute_value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <div class="tab-pane fade p-3" id="reviews-content" role="tabpanel" aria-labelledby="reviews-tab">
                <?php if (empty($reviews)): ?>
                    <p>There are no reviews for this product yet. Be the first to write one!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong>
                            <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                        </div>
                        <div class="star-rating my-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if ($review['title']): ?>
                            <h6 class="fw-semibold mt-1"><?php echo htmlspecialchars($review['title']); ?></h6>
                        <?php endif; ?>
                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- Add Review Form (for logged-in users who purchased) -->
                <?php if (isLoggedIn()): // Add more conditions like "has purchased this product" ?>
                <hr>
                <h5 class="mt-3">Write a Review</h5>
                <form action="<?php echo FRONTEND_URL; ?>/api/submit_review.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Your Rating</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="">Select Rating</option>
                            <option value="5">5 Stars (Excellent)</option>
                            <option value="4">4 Stars (Good)</option>
                            <option value="3">3 Stars (Average)</option>
                            <option value="2">2 Stars (Fair)</option>
                            <option value="1">1 Star (Poor)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="review_title" class="form-label">Review Title (Optional)</label>
                        <input type="text" class="form-control" id="review_title" name="review_title">
                    </div>
                    <div class="mb-3">
                        <label for="review_comment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="review_comment" name="review_comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
                <?php else: ?>
                <p class="mt-3"><a href="<?php echo FRONTEND_URL; ?>/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Login</a> to write a review.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <div class="mt-5">
        <h3 class="mb-4">Related Products</h3>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
            <?php foreach ($related_products as $related_product): ?>
            <div class="col">
                <div class="card product-card h-100 shadow-sm border-0">
                    <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($related_product['slug']); ?>">
                        <img src="<?php echo $related_product['image'] ? UPLOADS_URL . '/products/' . htmlspecialchars($related_product['image']) : FRONTEND_URL . '/assets/images/placeholder.png'; ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                    </a>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title fs-6">
                            <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($related_product['slug']); ?>" class="text-decoration-none text-dark product-name-link">
                                <?php echo htmlspecialchars($related_product['name']); ?>
                            </a>
                        </h6>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <?php if ($related_product['sale_price'] && $related_product['sale_price'] < $related_product['price']): ?>
                                        <span class="fw-bold text-primary"><?php echo formatPrice($related_product['sale_price']); ?></span>
                                        <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($related_product['price']); ?></small>
                                    <?php else: ?>
                                        <span class="fw-bold text-primary"><?php echo formatPrice($related_product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-sm btn-outline-primary add-to-cart-btn" data-product-id="<?php echo $related_product['id']; ?>">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
