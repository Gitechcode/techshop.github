</main> <!-- End of main-content -->

    <!-- Footer -->
    <footer class="footer bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h5 class="mb-3 text-uppercase"><i class="fas fa-laptop-code me-2 text-primary"></i><?php echo SITE_NAME; ?></h5>
                    <p class="text-white-50 small"><?php echo htmlspecialchars(SITE_NAME); ?> is your premier destination for the latest in technology. We are committed to providing top-quality products and exceptional customer service.</p>
                    <div class="mt-3">
    <a href="https://www.facebook.com/sokhacading" class="text-white-50 me-3" title="Facebook" target="_blank"><i class="fab fa-facebook-f fa-lg"></i></a>
    <a href="https://x.com/PounSokha" class="text-white-50 me-3" title="Twitter" target="_blank"><i class="fab fa-twitter fa-lg"></i></a>
    <a href="https://www.instagram.com/poun_sokha/" class="text-white-50 me-3" title="Instagram" target="_blank"><i class="fab fa-instagram fa-lg"></i></a>
    <a href="https://www.linkedin.com/in/poun-sokha-272564288/" class="text-white-50" title="LinkedIn" target="_blank"><i class="fab fa-linkedin-in fa-lg"></i></a>
</div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?php echo FRONTEND_URL; ?>/index.php" class="text-white-50 text-decoration-none hover-primary">Home</a></li>
                        <li class="mb-2"><a href="<?php echo FRONTEND_URL; ?>/shop.php" class="text-white-50 text-decoration-none hover-primary">Shop</a></li>
                        <li class="mb-2"><a href="<?php echo FRONTEND_URL; ?>/about.php" class="text-white-50 text-decoration-none hover-primary">About Us</a></li>
                        <li class="mb-2"><a href="<?php echo FRONTEND_URL; ?>/contact.php" class="text-white-50 text-decoration-none hover-primary">Contact</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-primary">FAQs</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h6 class="text-uppercase fw-bold mb-3">Customer Service</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-primary">My Account</a></li>
                        <li class="mb-2"><a href="<?php echo FRONTEND_URL; ?>/orders.php" class="text-white-50 text-decoration-none hover-primary">Order History</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-primary">Shipping & Returns</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-primary">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none hover-primary">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-uppercase fw-bold mb-3">Newsletter</h6>
                    <p class="text-white-50 small">Subscribe to our newsletter for the latest updates and special offers.</p>
                    <form action="#" method="post" id="newsletter-form">
                        <div class="input-group mb-3">
                            <input type="email" class="form-control form-control-sm" name="newsletter_email" placeholder="Your email address" required>
                            <button class="btn btn-primary btn-sm" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <hr class="my-4 bg-white-50">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa fa-lg text-white-50 me-2" title="Visa"></i>
                        <i class="fab fa-cc-mastercard fa-lg text-white-50 me-2" title="Mastercard"></i>
                        <i class="fab fa-cc-paypal fa-lg text-white-50 me-2" title="PayPal"></i>
                        <i class="fab fa-cc-stripe fa-lg text-white-50" title="Stripe"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script>
        // Define FRONTEND_URL for JavaScript
        const FRONTEND_URL = '<?php echo FRONTEND_URL; ?>';
        const IS_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo FRONTEND_URL; ?>/assets/js/main.js?v=<?php echo time(); ?>"></script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_specific_js) && is_array($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $script_file): ?>
            <script src="<?php echo FRONTEND_URL . '/assets/js/' . htmlspecialchars($script_file) . '?v=' . time(); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Toast Container for notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090">
      <!-- Toasts will be appended here by JavaScript -->
    </div>
</body>
</html>
