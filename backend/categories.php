<?php
require_once dirname(__DIR__) . '/config/config.php';
requireAdmin(BACKEND_URL . '/login.php');

$page_title = 'Manage Categories';

$action = $_GET['action'] ?? 'list';
$category_id = $_GET['id'] ?? null;
$category = null;
$errors = [];
$success_message = '';

// Handle image upload path
$upload_dir_categories = UPLOADS_PATH . '/categories/';
$upload_url_categories = UPLOADS_URL . '/categories/';

if (!is_dir($upload_dir_categories)) {
    mkdir($upload_dir_categories, 0775, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $slug = sanitizeInput($_POST['slug'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
    if ($parent_id === 0) $parent_id = null; // Allow 'No Parent'
    $status = sanitizeInput($_POST['status'] ?? 'inactive');

    if (empty($name)) $errors[] = "Category name is required.";

    // Generate slug if empty or ensure uniqueness
    if (empty($slug)) {
        $slug = generateSlug($name, 'categories', 'slug', $category_id);
    } else {
        $slug = generateSlug($slug, 'categories', 'slug', $category_id);
    }

    // Image handling
    $image_filename = $_POST['current_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_original_name = $_FILES['image']['name'];
        $image_ext = strtolower(pathinfo($image_original_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($image_ext, $allowed_exts)) {
            if ($_FILES['image']['size'] <= 1000000) { // Max 1MB for category images
                $image_filename = uniqid('cat_', true) . '.' . $image_ext;
                $image_destination = $upload_dir_categories . $image_filename;
                
                if (move_uploaded_file($image_tmp_name, $image_destination)) {
                    if ($category_id && !empty($_POST['current_image']) && $_POST['current_image'] !== $image_filename) {
                        $old_image_path = $upload_dir_categories . $_POST['current_image'];
                        if (file_exists($old_image_path)) unlink($old_image_path);
                    }
                } else {
                    $errors[] = "Failed to move uploaded category image.";
                    $image_filename = $_POST['current_image'] ?? null;
                }
            } else {
                $errors[] = "Category image file is too large (Max 1MB).";
            }
        } else {
            $errors[] = "Invalid category image file type. Allowed: " . implode(', ', $allowed_exts);
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading category image: " . $_FILES['image']['error'];
    }


    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $sql = "INSERT INTO categories (name, slug, description, parent_id, image, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $slug, $description, $parent_id, $image_filename, $status]);
                $success_message = "Category added successfully!";
                redirect(BACKEND_URL . '/categories.php?status=success_add');
            } elseif ($action === 'edit' && $category_id) {
                $sql = "UPDATE categories SET name=?, slug=?, description=?, parent_id=?, image=?, status=?, updated_at=NOW() 
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $slug, $description, $parent_id, $image_filename, $status, $category_id]);
                $success_message = "Category updated successfully!";
                redirect(BACKEND_URL . '/categories.php?status=success_edit');
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
} elseif ($action === 'edit' && $category_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();
        if (!$category) {
            setAlert("Category not found.", "warning");
            redirect(BACKEND_URL . '/categories.php');
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching category: " . $e->getMessage();
    }
} elseif ($action === 'delete' && $category_id) {
    try {
        // Check if category has products or subcategories before deleting (optional, for data integrity)
        $stmt_check_prod = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_check_prod->execute([$category_id]);
        if ($stmt_check_prod->fetchColumn() > 0) {
            setAlert("Cannot delete category: It has products associated with it. Reassign products first.", "danger");
            redirect(BACKEND_URL . '/categories.php');
        }
        $stmt_check_subcat = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt_check_subcat->execute([$category_id]);
        if ($stmt_check_subcat->fetchColumn() > 0) {
            setAlert("Cannot delete category: It has subcategories. Delete or reassign subcategories first.", "danger");
            redirect(BACKEND_URL . '/categories.php');
        }

        $stmt_img = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt_img->execute([$category_id]);
        $img_to_delete = $stmt_img->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);

        if ($img_to_delete && file_exists($upload_dir_categories . $img_to_delete)) {
            unlink($upload_dir_categories . $img_to_delete);
        }
        setAlert("Category deleted successfully!", "success");
    } catch (PDOException $e) {
        setAlert("Error deleting category: " . $e->getMessage(), "danger");
    }
    redirect(BACKEND_URL . '/categories.php');
}

// Fetch categories for listing and parent dropdown
$categories_list = [];
$parent_categories = [];
try {
    $stmt_list = $pdo->query("SELECT c1.*, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY c1.name ASC");
    $categories_list = $stmt_list->fetchAll();
    
    // For parent dropdown, exclude current category if editing
    $parent_query = "SELECT id, name FROM categories WHERE status = 'active'";
    if ($category_id) {
        $parent_query .= " AND id != " . (int)$category_id; // Prevent self-parenting
    }
    $parent_query .= " ORDER BY name ASC";
    $stmt_parent = $pdo->query($parent_query);
    $parent_categories = $stmt_parent->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Error fetching categories list: " . $e->getMessage();
}

if(isset($_GET['status'])){
    if($_GET['status'] == 'success_add') $success_message = "Category added successfully!";
    if($_GET['status'] == 'success_edit') $success_message = "Category updated successfully!";
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
            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-folder-plus me-1"></i><?php echo $action === 'add' ? 'Add New Category' : 'Edit Category: ' . htmlspecialchars($category['name'] ?? ''); ?></div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($category['image'] ?? ''); ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name'] ?? $_POST['name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug (auto-generated if blank)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($category['slug'] ?? $_POST['slug'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Category</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="0">-- No Parent --</option>
                        <?php foreach ($parent_categories as $parent_cat): ?>
                            <?php if ($category_id && $parent_cat['id'] == $category_id) continue; // Skip self ?>
                            <option value="<?php echo $parent_cat['id']; ?>" <?php echo (($category['parent_id'] ?? $_POST['parent_id'] ?? '') == $parent_cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent_cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Category Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                     <?php if (!empty($category['image'])): ?>
                        <img src="<?php echo $upload_url_categories . htmlspecialchars($category['image']); ?>" alt="Current Image" class="img-thumbnail mt-2" style="max-height: 100px;">
                        <p class="small text-muted">Current: <?php echo htmlspecialchars($category['image']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" <?php echo (($category['status'] ?? $_POST['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (($category['status'] ?? $_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $action === 'add' ? 'Add Category' : 'Save Changes'; ?></button>
                <a href="<?php echo BACKEND_URL; ?>/categories.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-1"></i>Categories List</span>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Category</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Parent Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories_list)): ?>
                            <tr><td colspan="6" class="text-center">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories_list as $cat): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($cat['image'])): ?>
                                        <img src="<?php echo $upload_url_categories . htmlspecialchars($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="<?php echo FRONTEND_URL . '/assets/images/placeholder.png'; ?>" alt="Placeholder" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                                <td><?php echo htmlspecialchars($cat['parent_name'] ?: '--'); ?></td>
                                <td><span class="badge bg-<?php echo $cat['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($cat['status']); ?></span></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category? This might affect products associated with it.');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
include 'includes/footer.php';
?>
