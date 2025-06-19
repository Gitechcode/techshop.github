<?php
require_once dirname(__DIR__) . '/config/config.php';
$page_title = 'Shop All Products';

// Get filters and search
$category_slug = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort_order = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 12;
$offset = ($page - 1) * $products_per_page;

// Build query
$sql_conditions = ["p.status = 'active'"];
$sql_params = [];
$selected_category_name = 'All Products';

if (!empty($category_slug)) {
    $stmt_cat = $pdo->prepare("SELECT id, name FROM categories WHERE slug = ? AND status = 'active'");
    $stmt_cat->execute([$category_slug]);
    $category_data = $stmt_cat->fetch();
    if ($category_data) {
        $sql_conditions[] = "p.category_id = ?";
        $sql_params[] = $category_data['id'];
        $selected_category_name = $category_data['name'];
        $page_title = 'Shop - ' . $category_data['name'];
    }
}

if (!empty($search_query)) {
    $sql_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $sql_params[] = "%{$search_query}%";
    $sql_params[] = "%{$search_query}%";
    $sql_params[] = "%{$search_query}%";
    $page_title = 'Search Results for "' . htmlspecialchars($search_query) . '"';
}

if ($min_price !== null) {
    $sql_conditions[] = "COALESCE(p.sale_price, p.price) >= ?";
    $sql_params[] = $min_price;
}
if ($max_price !== null && $max_price > 0) {
    $sql_conditions[] = "COALESCE(p.sale_price, p.price) <= ?";
    $sql_params[] = $max_price;
}

$where_clause = implode(' AND ', $sql_conditions);

// Sorting options
$order_by_options = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'COALESCE(p.sale_price, p.price) ASC, p.price ASC',
    'price_high' => 'COALESCE(p.sale_price, p.price) DESC, p.price DESC',
    'name_az' => 'p.name ASC',
    'name_za' => 'p.name DESC',
    'featured' => 'p.featured DESC, p.created_at DESC'
];
$current_order_by = $order_by_options[$sort_order] ?? $order_by_options['newest'];

// Get total products count for pagination
$count_sql = "SELECT COUNT(p.id) FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($sql_params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $products_per_page);

// Get products for the current page
$products_sql = "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price, p.image, p.featured, p.stock, c.name as category_name
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               WHERE $where_clause
               ORDER BY $current_order_by
               LIMIT $products_per_page OFFSET $offset";
$products_stmt = $pdo->prepare($products_sql);
$products_stmt->execute($sql_params);
$products = $products_stmt->fetchAll();

// Get all active categories for filter sidebar
$all_categories_stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC");
$all_categories = $all_categories_stmt->fetchAll();

// Get min and max price for price slider from all active products
$price_range_stmt = $pdo->query("SELECT MIN(COALESCE(sale_price, price)) as min_p, MAX(COALESCE(sale_price, price)) as max_p FROM products WHERE status = 'active'");
$price_range = $price_range_stmt->fetch();
$global_min_price = $price_range ? floor($price_range['min_p']) : 0;
$global_max_price = $price_range ? ceil($price_range['max_p']) : 1000;

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo FRONTEND_URL; ?>/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($selected_category_name); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Sidebar Filters -->
        <aside class="col-lg-3">
            <form method="GET" action="<?php echo FRONTEND_URL; ?>/shop.php" id="filter-form">
                <div class="filter-widget card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search within shop -->
                        <div class="mb-3">
                            <label for="shop-search" class="form-label fw-semibold">Search Products</label>
                            <input type="text" class="form-control" id="shop-search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Keyword...">
                        </div>

                        <!-- Categories -->
                        <div class="mb-3">
                            <label for="category-filter" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="category-filter" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($category_slug == $cat['slug']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Price Range</label>
                            <div class="d-flex justify-content-between mt-2">
                                <input type="number" class="form-control form-control-sm me-2" id="min-price-input" name="min_price" value="<?php echo $min_price ?? $global_min_price; ?>" placeholder="Min" style="width: 80px;">
                                <input type="number" class="form-control form-control-sm" id="max-price-input" name="max_price" value="<?php echo $max_price ?? $global_max_price; ?>" placeholder="Max" style="width: 80px;">
                            </div>
                            <small class="form-text text-muted">Range: <?php echo formatPrice($global_min_price) . " - " . formatPrice($global_max_price); ?></small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-check me-1"></i>Apply Filters</button>
                        <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </div>
            </form>
        </aside>

        <!-- Main Product Listing -->
        <main class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded shadow-sm">
                <div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($selected_category_name); ?></h4>
                    <small class="text-muted">Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</small>
                </div>
                <div>
                    <form method="GET" action="<?php echo FRONTEND_URL; ?>/shop.php" class="d-inline-block">
                        <!-- Hidden fields to retain other filters -->
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price ?? ''); ?>">
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price ?? ''); ?>">
                        
                        <select class="form-select form-select-sm" name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo ($sort_order == 'newest') ? 'selected' : ''; ?>>Sort by Newest</option>
                            <option value="oldest" <?php echo ($sort_order == 'oldest') ? 'selected' : ''; ?>>Sort by Oldest</option>
                            <option value="price_low" <?php echo ($sort_order == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($sort_order == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="name_az" <?php echo ($sort_order == 'name_az') ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name_za" <?php echo ($sort_order == 'name_za') ? 'selected' : ''; ?>>Name: Z-A</option>
                            <option value="featured" <?php echo ($sort_order == 'featured') ? 'selected' : ''; ?>>Featured First</option>
                        </select>
                    </form>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4>No Products Found</h4>
                <p class="text-muted">Try adjusting your filters or search terms.</p>
                <a href="<?php echo FRONTEND_URL; ?>/shop.php" class="btn btn-primary mt-2">View All Products</a>
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card product-card h-100 shadow-sm border-0">
                         <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($product['slug']); ?>">
                            <?php
                            // Fix image path - check multiple possible locations
                            $image_url = FRONTEND_URL . '/assets/images/placeholder.png'; // Default fallback
                            
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
                                        $image_url = $path;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <img src="<?php echo $image_url; ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 280px; object-fit: cover;"
                                 onerror="this.src='<?php echo FRONTEND_URL; ?>/assets/images/placeholder.png'">
                        </a>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): 
                            $discount_percentage = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                        ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">-<?php echo $discount_percentage; ?>%</span>
                        <?php elseif ($product['featured']): ?>
                             <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Featured</span>
                        <?php endif; ?>
                         <?php if ($product['stock'] <= 0): ?>
                            <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Out of Stock</span>
                        <?php elseif ($product['stock'] > 0 && $product['stock'] < 10): ?>
                            <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Low Stock</span>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fs-6">
                                <a href="<?php echo FRONTEND_URL . '/product.php?slug=' . htmlspecialchars($product['slug']); ?>" class="text-decoration-none text-dark product-name-link">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h5>
                            <?php if ($product['category_name']): ?>
                                <small class="text-muted mb-2 d-block"><?php echo htmlspecialchars($product['category_name']); ?></small>
                            <?php endif; ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(substr($product['short_description'] ?: '', 0, 70)) . (strlen($product['short_description'] ?: '') > 70 ? '...' : ''); ?>
                            </p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <span class="fw-bold text-primary fs-5"><?php echo formatPrice($product['sale_price']); ?></span>
                                            <small class="text-muted text-decoration-line-through ms-1"><?php echo formatPrice($product['price']); ?></small>
                                        <?php else: ?>
                                            <span class="fw-bold text-primary fs-5"><?php echo formatPrice($product['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger wishlist-btn" data-product-id="<?php echo $product['id']; ?>" title="Add to Wishlist">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-sm btn-primary add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>" <?php echo $product['stock'] <=0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-cart-plus me-1"></i> <?php echo $product['stock'] <=0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
$page_specific_js = ['shop.js'];
include 'includes/footer.php';
?>
