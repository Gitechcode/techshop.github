<?php
$page_title = 'Contact Us';
include 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Save contact message to database
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'new', NOW())
        ");
        
        if ($stmt->execute([$name, $email, $phone, $subject, $message])) {
            $success = 'Thank you for your message! We will get back to you soon.';
            
            // Send email notification (implement email sending)
            // sendContactNotification($name, $email, $subject, $message);
        } else {
            $error = 'Error sending message. Please try again.';
        }
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">Contact Us</h1>
                <p class="lead text-muted">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Choose a subject...</option>
                                    <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                                    <option value="Product Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Product Support') ? 'selected' : ''; ?>>Product Support</option>
                                    <option value="Order Issue" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Order Issue') ? 'selected' : ''; ?>>Order Issue</option>
                                    <option value="Return/Refund" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Return/Refund') ? 'selected' : ''; ?>>Return/Refund</option>
                                    <option value="Technical Support" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="Partnership" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'Partnership') ? 'selected' : ''; ?>>Partnership</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Please describe your inquiry in detail..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Get in Touch</h5>
                    
                    <div class="contact-info">
                        <div class="d-flex align-items-start mb-3">
                            <div class="contact-icon me-3">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Address</h6>
                                <p class="text-muted mb-0">123 Tech Street<br>Digital City, DC 12345</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3">
                            <div class="contact-icon me-3">
                                <i class="fas fa-phone text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Phone</h6>
                                <p class="text-muted mb-0">+1 (555) 123-4567</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3">
                            <div class="contact-icon me-3">
                                <i class="fas fa-envelope text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Email</h6>
                                <p class="text-muted mb-0">support@techshop.com</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4">
                            <div class="contact-icon me-3">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Business Hours</h6>
                                <p class="text-muted mb-0">
                                    Mon - Fri: 9:00 AM - 6:00 PM<br>
                                    Sat: 10:00 AM - 4:00 PM<br>
                                    Sun: Closed
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="social-links">
                        <h6 class="mb-3">Follow Us</h6>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sokhacading" target="_blank" class="btn btn-outline-primary btn-sm" title="Follow us on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://x.com/PounSokha" target="_blank" class="btn btn-outline-info btn-sm" title="Follow us on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.instagram.com/poun_sokha/" target="_blank" class="btn btn-outline-danger btn-sm" title="Follow us on Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/poun-sokha-272564288/" target="_blank" class="btn btn-outline-primary btn-sm" title="Connect with us on LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card shadow-sm mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Frequently Asked Questions</h5>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer a 30-day return policy for all products in original condition.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How long does shipping take?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Standard shipping takes 3-5 business days. Express shipping is available for 1-2 days.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Do you offer warranty?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, all products come with manufacturer warranty. Extended warranty options are available.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
