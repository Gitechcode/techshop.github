<?php
require_once dirname(__DIR__) . '/config/config.php';
requireAdmin(BACKEND_URL . '/login.php'); // Redirect to backend login if not admin

$page_title = 'Manage Products';

$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? null;
$product = null;
$errors = [];
$success_message = '';

// Handle image upload path
$upload_dir_products = UPLOADS_PATH . '/products/';
$upload_url_products = UPLOADS_URL . '/products/';

if (!is_dir($upload_dir_products)) {
    mkdir($upload_dir_products, 0775, true);
}


// Fetch categories for dropdown
try {
    $stmt_cat = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $stmt_cat->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Error fetching categories: " . $e->getMessage();
    $categories = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? ''); // Consider allowing some HTML with a proper editor/sanitizer
    $short_description = sanitizeInput($_POST['short_description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $sale_price = filter_input(INPUT_POST, 'sale_price', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $sku = sanitizeInput($_POST['sku'] ?? '');

    // Auto-generate SKU if empty or ensure uniqueness
    if (empty($sku)) {
        $sku = 'PROD-' . strtoupper(substr(uniqid(), 7, 6));
    } else {
        // Check if SKU already exists and modify if needed
        $original_sku = $sku;
        $counter = 1;
        $stmt_sku_check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?" . ($product_id ? " AND id != ?" : ""));
        $params_sku = [$sku];
        if ($product_id) $params_sku[] = $product_id;
        
        $stmt_sku_check->execute($params_sku);
        while ($stmt_sku_check->fetchColumn() > 0) {
            $sku = $original_sku . '-' . $counter;
            $counter++;
            $params_sku[0] = $sku;
            $stmt_sku_check->execute($params_sku);
        }
    }
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $brand = sanitizeInput($_POST['brand'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = sanitizeInput($_POST['status'] ?? 'inactive');
    $slug = sanitizeInput($_POST['slug'] ?? '');

    // Basic Validation
    if (empty($name)) $errors[] = "Product name is required.";
    if ($price === false || $price < 0) $errors[] = "Valid price is required.";
    if ($stock === false || $stock < 0) $errors[] = "Valid stock quantity is required.";
    if (empty($category_id)) $errors[] = "Category is required.";
    if (empty($status)) $errors[] = "Status is required.";

    // Generate slug if empty or ensure uniqueness
    if (empty($slug)) {
        $slug = generateSlug($name, 'products', 'slug', $product_id);
    } else {
        $slug = generateSlug($slug, 'products', 'slug', $product_id); // Ensure uniqueness even if provided
    }
    
    // Image handling
    $image_filename = $_POST['current_image'] ?? null; // Keep current image if not changed
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_original_name = $_FILES['image']['name'];
        $image_ext = strtolower(pathinfo($image_original_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($image_ext, $allowed_exts)) {
            if ($_FILES['image']['size'] <= 2000000) { // Max 2MB
                // Generate a unique filename to prevent overwriting
                $image_filename = uniqid('prod_', true) . '.' . $image_ext;
                $image_destination = $upload_dir_products . $image_filename;
            
                // Check if directory is writable
                if (!is_writable($upload_dir_products)) {
                    $errors[] = "Upload directory is not writable. Please check permissions for: " . $upload_dir_products;
                } else {
                    if (move_uploaded_file($image_tmp_name, $image_destination)) {
                        // Delete old image if updating and new image uploaded successfully
                        if ($product_id && !empty($_POST['current_image']) && $_POST['current_image'] !== $image_filename) {
                            $old_image_path = $upload_dir_products . $_POST['current_image'];
                            if (file_exists($old_image_path)) {
                                unlink($old_image_path);
                            }
                        }
                    } else {
                        $errors[] = "Failed to move uploaded image. Check directory permissions.";
                        $image_filename = $_POST['current_image'] ?? null; // Revert to old if move failed
                    }
                }
            } else {
                $errors[] = "Image file is too large (Max 2MB).";
            }
        } else {
            $errors[] = "Invalid image file type. Allowed: " . implode(', ', $allowed_exts);
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading image: " . $_FILES['image']['error'];
    }


    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $sql = "INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock, category_id, brand, image, featured, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $slug, $description, $short_description, $price, $sale_price, $sku, $stock, $category_id, $brand, $image_filename, $featured, $status]);
                $success_message = "Product added successfully!";
                redirect(BACKEND_URL . '/products.php?action=list&status=success_add');
            } elseif ($action === 'edit' && $product_id) {
                $sql = "UPDATE products SET name=?, slug=?, description=?, short_description=?, price=?, sale_price=?, sku=?, stock=?, category_id=?, brand=?, image=?, featured=?, status=?, updated_at=NOW() 
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $slug, $description, $short_description, $price, $sale_price, $sku, $stock, $category_id, $brand, $image_filename, $featured, $status, $product_id]);
                $success_message = "Product updated successfully!";
                 redirect(BACKEND_URL . '/products.php?action=list&status=success_edit');
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
} elseif ($action === 'edit' && $product_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if (!$product) {
            setAlert("Product not found.", "warning");
            redirect(BACKEND_URL . '/products.php');
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching product: " . $e->getMessage();
    }
} elseif ($action === 'delete' && $product_id) {
    // CSRF protection should be added here
    try {
        // First, get the image filename to delete it
        $stmt_img = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_img->execute([$product_id]);
        $img_to_delete = $stmt_img->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        // Delete the image file if it exists
        if ($img_to_delete && file_exists($upload_dir_products . $img_to_delete)) {
            unlink($upload_dir_products . $img_to_delete);
        }
        setAlert("Product deleted successfully!", "success");
    } catch (PDOException $e) {
        setAlert("Error deleting product: " . $e->getMessage(), "danger");
    }
    redirect(BACKEND_URL . '/products.php');
}


// Fetch products for listing
$products_list = [];
if ($action === 'list') {
    try {
        $stmt_list = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
        $products_list = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        $errors[] = "Error fetching products list: " . $e->getMessage();
    }
}
if(isset($_GET['status'])){
    if($_GET['status'] == 'success_add') $success_message = "Product added successfully!";
    if($_GET['status'] == 'success_edit') $success_message = "Product updated successfully!";
}


include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo BACKEND_URL; ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
    </ol>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus-circle me-1"></i>
                <?php echo $action === 'add' ? 'Add New Product' : 'Edit Product: ' . htmlspecialchars($product['name'] ?? ''); ?>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? $_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug (auto-generated if blank)</label>
                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($product['slug'] ?? $_POST['slug'] ?? ''); ?>">
                            </div>
                             <div class="mb-3">
                                <label for="description" class="form-label">Full Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Short Description (for listings)</label>
                                <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($product['short_description'] ?? $_POST['short_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="mb-3">
                                <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo (($product['category_id'] ?? $_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($product['price'] ?? $_POST['price'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="sale_price" class="form-label">Sale Price (optional)</label>
                                <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($product['sale_price'] ?? $_POST['sale_price'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($product['sku'] ?? $_POST['sku'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock'] ?? $_POST['stock'] ?? '0'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($product['brand'] ?? $_POST['brand'] ?? ''); ?>">
                            </div>
                             <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo $upload_url_products . htmlspecialchars($product['image']); ?>" alt="Current Image" class="img-thumbnail mt-2" style="max-height: 100px;">
                                    <p class="small text-muted">Current: <?php echo htmlspecialchars($product['image']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1" <?php echo (($product['featured'] ?? $_POST['featured'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">Featured Product</label>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo (($product['status'] ?? $_POST['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo (($product['status'] ?? $_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="out_of_stock" <?php echo (($product['status'] ?? $_POST['status'] ?? '') === 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> <?php echo $action === 'add' ? 'Add Product' : 'Save Changes'; ?>
                    </button>
                    <a href="<?php echo BACKEND_URL; ?>/products.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: // List view ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-1"></i>Products List</span>
                <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Product</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productsTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products_list)): ?>
                                <tr><td colspan="9" class="text-center">No products found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($products_list as $p): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?php echo $upload_url_products . htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="<?php echo FRONTEND_URL . '/assets/images/placeholder.png'; ?>" alt="Placeholder" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['sku'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo formatPrice($p['sale_price'] ?: $p['price']); ?></td>
                                    <td><?php echo $p['stock']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($p['status']) {
                                                case 'active': echo 'success'; break;
                                                case 'inactive': echo 'secondary'; break;
                                                case 'out_of_stock': echo 'warning text-dark'; break;
                                                default: echo 'light text-dark';
                                            }
                                        ?>"><?php echo ucfirst($p['status']); ?></span>
                                    </td>
                                    <td><?php echo $p['featured'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-muted"></i>'; ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product? This cannot be undone.');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
            // Basic JS for DataTables if you want to include it
            // window.addEventListener('DOMContentLoaded', event => {
            //     const productsTable = document.getElementById('productsTable');
            //     if (productsTable) {
            // new simpleDatatables.DataTable(productsTable); // If using Simple DataTables
            //     }
            // });
        </script>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
