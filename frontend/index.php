<?php
require_once dirname(__DIR__) . '/config/config.php';
$page_title = 'Homepage';

// Get featured products
try {
    $stmt_featured = $pdo->query("SELECT id, name, slug, short_description, price, sale_price, image, featured FROM products WHERE status = 'active' AND featured = 1 ORDER BY created_at DESC LIMIT 8");
    $featured_products = $stmt_featured->fetchAll();
    
    // Get new arrivals if no featured products or less than 4
    if (count($featured_products) < 4) {
        $limit_new = 8 - count($featured_products);
        $stmt_new = $pdo->query("SELECT id, name, slug, short_description, price, sale_price, image, featured FROM products WHERE status = 'active' AND featured = 0 ORDER BY created_at DESC LIMIT $limit_new");
        $new_products = $stmt_new->fetchAll();
        $featured_products = array_merge($featured_products, $new_products);
    }
} catch(PDOException $e) {
    $featured_products = [];
}
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden" style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <!-- Animated Background Elements -->
    <div class="position-absolute w-100 h-100">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>
    </div>
    
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6 text-white">
                <div class="hero-content">
                    <!-- Badge -->
                    <div class="badge bg-white bg-opacity-20 text-white px-3 py-2 rounded-pill mb-4">
                        <i class="fas fa-star me-2"></i>
                        #1 Tech Store in Cambodia
                    </div>
                    
                    <!-- Main Heading -->
                    <h1 class="display-2 fw-bold mb-4 text-white">
                        Welcome to 
                        <span class="text-warning"><?php echo SITE_NAME; ?></span>
                    </h1>
                    
                    <!-- Subtitle -->
                    <p class="lead mb-4 text-white-75 fs-4" style="opacity: 0.9; line-height: 1.6;">
                        Discover the latest technology products at unbeatable prices. 
                        From cutting-edge laptops to innovative smartphones, we bring you 
                        the future of technology today.
                    </p>
                    
                    <!-- Features List -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-warning me-2"></i>
                                <span class="text-white">Free Shipping Worldwide</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-warning me-2"></i>
                                <span class="text-white">24/7 Customer Support</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-warning me-2"></i>
                                <span class="text-white">Secure Payment Gateway</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-warning me-2"></i>
                                <span class="text-white">1 Year Warranty</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CTA Buttons -->
                    <div class="hero-buttons d-flex flex-wrap gap-3">
                        <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-warning btn-lg px-4 py-3 shadow-lg">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Shop Now
                        </a>
                        <a href="<?php echo FRONTEND_URL; ?>/about.php" class="btn btn-outline-light btn-lg px-4 py-3 shadow">
                            <i class="fas fa-play-circle me-2"></i>
                            Watch Demo
                        </a>
                    </div>
                </div>
            </div>
            
            <?php
// Get best seller and featured products for hero section
try {
    // Get best seller (most ordered product)
    $stmt_bestseller = $pdo->query("
        SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price, p.image, 
               COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p 
        LEFT JOIN order_items oi ON p.id = oi.product_id 
        WHERE p.status = 'active' 
        GROUP BY p.id 
        ORDER BY total_sold DESC, p.featured DESC 
        LIMIT 1
    ");
    $bestseller = $stmt_bestseller->fetch();
    
    // Get 3 featured products for floating cards
    $stmt_floating = $pdo->query("
        SELECT id, name, slug, price, sale_price, image 
        FROM products 
        WHERE status = 'active' AND id != " . ($bestseller['id'] ?? 0) . "
        ORDER BY featured DESC, created_at DESC 
        LIMIT 3
    ");
    $floating_products = $stmt_floating->fetchAll();
    
} catch(PDOException $e) {
    $bestseller = null;
    $floating_products = [];
}
?>
<div class="col-lg-6">
    <div class="hero-image-container text-center position-relative">
        <!-- Main Product Showcase -->
        <div class="product-showcase position-relative">
            <?php if ($bestseller): ?>
            <div class="main-product-card bg-white rounded-4 shadow-lg p-4 mb-4">
                <!-- Best Seller Badge -->
                <div class="position-absolute top-0 start-0 m-3">
                    <span class="badge bg-danger fs-6 px-3 py-2">
                        <i class="fas fa-fire me-1"></i>Best Seller
                    </span>
                </div>
                
                <img src="<?php 
                    if ($bestseller['image']) {
                        $image_path = dirname(__DIR__) . '/public/uploads/products/' . $bestseller['image'];
                        if (file_exists($image_path)) {
                            echo SITE_URL . '/public/uploads/products/' . htmlspecialchars($bestseller['image']);
                        } else {
                            echo 'https://via.placeholder.com/400x300/f8f9fa/6c757d?text=' . urlencode($bestseller['name']);
                        }
                    } else {
                        echo 'https://via.placeholder.com/400x300/f8f9fa/6c757d?text=' . urlencode($bestseller['name']);
                    }
                ?>" 
                     class="img-fluid rounded-3" alt="<?php echo htmlspecialchars($bestseller['name']); ?>" 
                     style="max-height: 300px; width: 100%; object-fit: cover;">
                <div class="mt-3">
                    <h5 class="fw-bold text-dark"><?php echo htmlspecialchars($bestseller['name']); ?></h5>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($bestseller['short_description']); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <?php if ($bestseller['sale_price'] && $bestseller['sale_price'] < $bestseller['price']): ?>
                            <div>
                                <span class="h5 text-primary fw-bold mb-0"><?php echo formatPrice($bestseller['sale_price']); ?></span>
                                <small class="text-muted text-decoration-line-through ms-2"><?php echo formatPrice($bestseller['price']); ?></small>
                            </div>
                        <?php else: ?>
                            <span class="h5 text-primary fw-bold mb-0"><?php echo formatPrice($bestseller['price']); ?></span>
                        <?php endif; ?>
                        <span class="badge bg-success">In Stock</span>
                    </div>
                    <button class="btn btn-primary w-100 mt-3 add-to-cart-btn" data-product-id="<?php echo $bestseller['id']; ?>">
                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="main-product-card bg-white rounded-4 shadow-lg p-4 mb-4">
                <img src="https://via.placeholder.com/400x300/f8f9fa/6c757d?text=No+Products+Available" 
                     class="img-fluid rounded-3" alt="No Products">
                <div class="mt-3">
                    <h5 class="fw-bold text-dark">No Products Available</h5>
                    <p class="text-muted mb-2">Please add products to your store</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Floating Product Cards with Real Images -->
            <?php if (!empty($floating_products)): ?>
                <?php foreach ($floating_products as $index => $product): ?>
                <div class="floating-product floating-product-<?php echo $index + 1; ?> bg-white rounded-3 shadow p-3">
                    <div class="d-flex align-items-center">
                        <img src="<?php 
                            if ($product['image']) {
                                $image_path = dirname(__DIR__) . '/public/uploads/products/' . $product['image'];
                                if (file_exists($image_path)) {
                                    echo SITE_URL . '/public/uploads/products/' . htmlspecialchars($product['image']);
                                } else {
                                    echo 'https://via.placeholder.com/60x60/007bff/ffffff?text=' . substr($product['name'], 0, 1);
                                }
                            } else {
                                echo 'https://via.placeholder.com/60x60/007bff/ffffff?text=' . substr($product['name'], 0, 1);
                            }
                        ?>" 
                             class="rounded me-2" alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="width: 60px; height: 60px; object-fit: cover;">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars(substr($product['name'], 0, 15)) . (strlen($product['name']) > 15 ? '...' : ''); ?></h6>
                            <small class="text-muted">
                                <?php echo $product['sale_price'] && $product['sale_price'] < $product['price'] ? formatPrice($product['sale_price']) : formatPrice($product['price']); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <!-- Fallback floating cards if no products -->
            <div class="floating-product floating-product-1 bg-white rounded-3 shadow p-3">
                <div class="d-flex align-items-center">
                    <img src="https://via.placeholder.com/60x60/007bff/ffffff?text=📱" class="rounded me-2" alt="Phone">
                    <div>
                        <h6 class="mb-0 fw-bold">Smartphones</h6>
                        <small class="text-muted">Coming Soon</small>
                    </div>
                </div>
            </div>
            
            <div class="floating-product floating-product-2 bg-white rounded-3 shadow p-3">
                <div class="d-flex align-items-center">
                    <img src="https://via.placeholder.com/60x60/28a745/ffffff?text=🎧" class="rounded me-2" alt="Headphones">
                    <div>
                        <h6 class="mb-0 fw-bold">Audio</h6>
                        <small class="text-muted">Coming Soon</small>
                    </div>
                </div>
            </div>
            
            <div class="floating-product floating-product-3 bg-white rounded-3 shadow p-3">
                <div class="d-flex align-items-center">
                    <img src="https://via.placeholder.com/60x60/ffc107/ffffff?text=⌚" class="rounded me-2" alt="Watch">
                    <div>
                        <h6 class="mb-0 fw-bold">Wearables</h6>
                        <small class="text-muted">Coming Soon</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="position-absolute bottom-0 start-50 translate-middle-x mb-4">
        <div class="scroll-indicator text-white text-center">
            <small class="d-block mb-2">Scroll to explore</small>
            <i class="fas fa-chevron-down fa-2x animate-bounce"></i>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-4 bg-white shadow-sm">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <div class="stat-item">
                    <h3 class="fw-bold text-primary mb-1 counter" data-target="1000">0</h3>
                    <p class="text-muted mb-0 small">Products</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3 mb-md-0">
                <div class="stat-item">
                    <h3 class="fw-bold text-success mb-1 counter" data-target="50000">0</h3>
                    <p class="text-muted mb-0 small">Happy Customers</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h3 class="fw-bold text-warning mb-1">24/7</h3>
                    <p class="text-muted mb-0 small">Support</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <h3 class="fw-bold text-info mb-1">Free</h3>
                    <p class="text-muted mb-0 small">Shipping</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-5 mb-3">Featured Products</h2>
            <p class="text-muted lead">Check out our handpicked selection of top tech</p>
        </div>

        <?php if (empty($featured_products)): ?>
        <div class="row">
            <div class="col-12 text-center">
                <div class="alert alert-info border-0 shadow-sm">
                    <h4><i class="fas fa-info-circle me-2"></i>No Products Available Yet</h4>
                    <p class="mb-0">Our product catalog is currently being updated. Please check back soon!</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($featured_products as $product): ?>
            <div class="col">
                <div class="card product-card h-100 shadow-sm border-0 hover-lift">
                    <div class="position-relative overflow-hidden">
                        <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($product['slug']); ?>">
                            <img src="<?php 
                                if ($product['image']) {
                                    $image_path = dirname(__DIR__) . '/public/uploads/products/' . $product['image'];
                                    if (file_exists($image_path)) {
                                        echo SITE_URL . '/public/uploads/products/' . htmlspecialchars($product['image']);
                                    } else {
                                        echo 'https://via.placeholder.com/300x300/f8f9fa/6c757d?text=No+Image';
                                    }
                                } else {
                                    echo 'https://via.placeholder.com/300x300/f8f9fa/6c757d?text=No+Image';
                                }
                            ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 250px; object-fit: cover; transition: transform 0.3s ease;">
                        </a>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): 
                            $discount_percentage = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                        ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-<?php echo $discount_percentage; ?>%</span>
                        <?php elseif ($product['featured']): ?>
                             <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fs-6 fw-semibold">
                            <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($product['slug']); ?>" 
                               class="text-decoration-none text-dark product-name-link">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h5>
                        <p class="card-text text-muted small flex-grow-1">
                            <?php echo htmlspecialchars(substr($product['short_description'] ?: '', 0, 70)) . (strlen($product['short_description'] ?: '') > 70 ? '...' : ''); ?>
                        </p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <span class="fw-bold text-primary fs-5"><?php echo formatPrice($product['sale_price']); ?></span>
                                        <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($product['price']); ?></small>
                                    <?php else: ?>
                                        <span class="fw-bold text-primary fs-5"><?php echo formatPrice($product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary add-to-cart-btn shadow-sm" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-outline-primary btn-lg shadow">
                <i class="fas fa-store me-2"></i>View All Products
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-5 mb-3">Why Choose TechShop?</h2>
            <p class="text-muted lead">Experience the best in tech shopping</p>
        </div>
        <div class="row text-center">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-box p-4 h-100 border rounded shadow-sm bg-light">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shipping-fast fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-semibold mb-3">Fast Shipping</h5>
                    <p class="text-muted">Get your orders delivered quickly and reliably with our express shipping service.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-box p-4 h-100 border rounded shadow-sm bg-light">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shield-alt fa-3x text-success"></i>
                    </div>
                    <h5 class="fw-semibold mb-3">Secure Payments</h5>
                    <p class="text-muted">Shop with confidence using our secure payment gateway and data protection.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-box p-4 h-100 border rounded shadow-sm bg-light">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-medal fa-3x text-warning"></i>
                    </div>
                    <h5 class="fw-semibold mb-3">Quality Products</h5>
                    <p class="text-muted">Only genuine and high-quality tech products from trusted brands and manufacturers.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="feature-box p-4 h-100 border rounded shadow-sm bg-light">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-headset fa-3x text-info"></i>
                    </div>
                    <h5 class="fw-semibold mb-3">24/7 Support</h5>
                    <p class="text-muted">Our dedicated support team is always ready to assist you with any questions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Hero Section Animations */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-bounce {
    animation: bounce 2s infinite;
}

.floating-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.shape {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.shape-1 {
    width: 80px;
    height: 80px;
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.shape-2 {
    width: 120px;
    height: 120px;
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

.shape-3 {
    width: 60px;
    height: 60px;
    top: 80%;
    left: 20%;
    animation-delay: 4s;
}

.shape-4 {
    width: 100px;
    height: 100px;
    top: 10%;
    right: 30%;
    animation-delay: 1s;
}

.floating-product {
    position: absolute;
    animation: float 4s ease-in-out infinite;
}

.floating-product-1 {
    top: 10%;
    right: -10%;
    animation-delay: 0s;
}

.floating-product-2 {
    bottom: 30%;
    left: -10%;
    animation-delay: 2s;
}

.floating-product-3 {
    top: 50%;
    right: -15%;
    animation-delay: 1s;
}

.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.product-image:hover {
    transform: scale(1.05);
}

.feature-box {
    transition: transform 0.3s ease;
}

.feature-box:hover {
    transform: translateY(-3px);
}

.counter {
    transition: all 0.3s ease;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .floating-product {
        display: none;
    }
    
    .display-2 {
        font-size: 2.5rem;
    }
    
    .hero-buttons {
        justify-content: center;
    }
}
</style>

<script>
// Counter animation
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const increment = target / 100;
        let current = 0;
        
        const updateCounter = () => {
            if (current < target) {
                current += increment;
                counter.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };
        
        updateCounter();
    };
    
    // Intersection Observer for counter animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
