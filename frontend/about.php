<?php
$page_title = 'About Us';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-primary">About <?php echo SITE_NAME; ?></h1>
                <p class="lead text-muted">Your trusted partner in technology</p>
            </div>
            
            <div class="card shadow-sm mb-5">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4">Our Story</h2>
                    <p class="mb-4">
                        Founded in 2020, <?php echo SITE_NAME; ?> has been at the forefront of bringing the latest technology 
                        to consumers worldwide. We started as a small team of tech enthusiasts with a simple mission: 
                        to make cutting-edge technology accessible to everyone.
                    </p>
                    
                    <p class="mb-4">
                        Today, we're proud to serve thousands of customers globally, offering everything from the latest 
                        smartphones and laptops to innovative gadgets and accessories. Our commitment to quality, 
                        competitive pricing, and exceptional customer service has made us a trusted name in the tech industry.
                    </p>
                    
                    <h3 class="h4 mb-3">Our Mission</h3>
                    <p class="mb-4">
                        To democratize access to technology by providing high-quality products at affordable prices, 
                        backed by outstanding customer service and support.
                    </p>
                    
                    <h3 class="h4 mb-3">Why Choose Us?</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Quality Products</h5>
                                    <p class="text-muted mb-0">We source only the best products from trusted manufacturers.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shipping-fast text-primary me-3 mt-1"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Fast Shipping</h5>
                                    <p class="text-muted mb-0">Quick and reliable delivery to your doorstep.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-headset text-info me-3 mt-1"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">24/7 Support</h5>
                                    <p class="text-muted mb-0">Our customer service team is always here to help.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt text-warning me-3 mt-1"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Secure Shopping</h5>
                                    <p class="text-muted mb-0">Your data and transactions are always protected.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <h3 class="mb-4">Ready to Start Shopping?</h3>
                <a href="shop.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Browse Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
