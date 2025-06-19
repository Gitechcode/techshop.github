<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

// Ensure placeholder image exists
$placeholder_dir = dirname(__DIR__) . '/assets/images';
if (!file_exists($placeholder_dir)) {
    mkdir($placeholder_dir, 0755, true);
}

$placeholder_file = $placeholder_dir . '/placeholder.png';
if (!file_exists($placeholder_file)) {
    // Create a simple placeholder image
    $image = imagecreate(400, 400);
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 128, 128, 128);
    imagefill($image, 0, 0, $bg_color);
    
    $text = 'No Image';
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $x = (400 - $text_width) / 2;
    $y = (400 - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $text, $text_color);
    imagepng($image, $placeholder_file);
    imagedestroy($image);
}
?>
<!DOCTYPE html>
<html lang="en" data-frontend-url="<?php echo FRONTEND_URL; ?>" data-is-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <link href="<?php echo FRONTEND_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php if (isset($page_specific_css) && is_array($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css_file): ?>
            <link href="<?php echo FRONTEND_URL . '/assets/css/' . htmlspecialchars($css_file) . '?v=' . time(); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand d-flex align-items-center" href="<?php echo FRONTEND_URL; ?>/index.php">
                    <i class="fas fa-laptop-code fa-2x text-primary me-3"></i>
                    <span class="fw-bold fs-3"><?php echo SITE_NAME; ?></span>
                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo FRONTEND_URL; ?>/index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : ''; ?>" href="<?php echo FRONTEND_URL; ?>/shop.php">Shop</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo (basename($_SERVER['PHP_SELF']) == 'shop.php' && isset($_GET['category'])) ? 'active' : ''; ?>" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Categories
                            </a>
                            <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="categoriesDropdown">
                                <?php
                                try {
                                    if (isset($pdo)) {
                                        $stmt_cat_nav = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name LIMIT 7");
                                        $categories_nav = $stmt_cat_nav->fetchAll();
                                        if ($categories_nav) {
                                            foreach ($categories_nav as $category_nav) {
                                                echo "<li><a class='dropdown-item py-2' href='" . FRONTEND_URL . "/shop.php?category=" . htmlspecialchars($category_nav['slug']) . "'><i class='fas fa-tag me-2 text-primary'></i>" . htmlspecialchars($category_nav['name']) . "</a></li>";
                                            }
                                        } else {
                                             echo "<li><span class='dropdown-item text-muted py-2'>No categories found</span></li>";
                                        }
                                    }
                                } catch (Exception $e) {
                                    if(DEVELOPMENT) error_log("Error fetching categories for nav: " . $e->getMessage());
                                    echo "<li><span class='dropdown-item text-danger py-2'>Error loading categories</span></li>";
                                }
                                ?>
                                 <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item py-2" href="<?php echo FRONTEND_URL; ?>/shop.php"><i class='fas fa-th-large me-2 text-primary'></i>All Categories</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="<?php echo FRONTEND_URL; ?>/about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="<?php echo FRONTEND_URL; ?>/contact.php">Contact</a>
                        </li>
                    </ul>

                    <!-- Right Side Actions -->
                    <div class="d-flex align-items-center gap-2">
                        <!-- Search Form -->
                         <form class="d-none d-lg-flex" method="GET" action="<?php echo FRONTEND_URL; ?>/shop.php">
                            <div class="input-group">
                                <input class="form-control border-end-0" type="search" name="search" placeholder="Search products..." aria-label="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button class="btn btn-outline-secondary border-start-0" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                         </form>

                        <!-- Cart -->
                        <a href="<?php echo FRONTEND_URL; ?>/cart.php" class="btn btn-outline-primary position-relative" title="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="cart-count-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo getCartCount(); ?>
                            </span>
                        </a>

                        <?php if (isLoggedIn()): ?>
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="My Account">
                                <i class="fas fa-user me-2"></i>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item py-2" href="<?php echo FRONTEND_URL; ?>/profile.php"><i class="fas fa-user-circle fa-fw me-2 text-primary"></i>Profile</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo FRONTEND_URL; ?>/orders.php"><i class="fas fa-box-open fa-fw me-2 text-primary"></i>My Orders</a></li>
                                <li><a class="dropdown-item py-2" href="<?php echo FRONTEND_URL; ?>/wishlist.php"><i class="fas fa-heart fa-fw me-2 text-primary"></i>Wishlist</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2" href="<?php echo BACKEND_URL; ?>/dashboard.php" target="_blank"><i class="fas fa-tachometer-alt fa-fw me-2 text-warning"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?php echo FRONTEND_URL; ?>/logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <!-- Login/Register -->
                        <a href="<?php echo FRONTEND_URL; ?>/login.php" class="btn btn-outline-primary">Login</a>
                        <a href="<?php echo FRONTEND_URL; ?>/register.php" class="btn btn-primary">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Area -->
    <main class="main-content fade-in">
        <?php displayAlert(); ?>
