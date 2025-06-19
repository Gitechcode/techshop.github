<?php
require_once '../config/config.php';
requireAdmin(BACKEND_URL . '/login.php');
$page_title = 'Users Management';

// Handle form submissions for add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user']) || isset($_POST['edit_user'])) {
        $name = sanitizeInput($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $city = sanitizeInput($_POST['city']);
        $postal_code = sanitizeInput($_POST['postal_code']);
        $role = sanitizeInput($_POST['role']);
        $status = sanitizeInput($_POST['status']);
        $password = $_POST['password']; // Keep plain for now, hash if provided

        if (empty($name) || !$email) {
            setAlert('Name and a valid Email are required.', 'danger');
        } else {
            try {
                if (isset($_POST['add_user'])) {
                    if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
                         setAlert('Password is required and must be at least ' . PASSWORD_MIN_LENGTH . ' characters long for new users.', 'danger');
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, city, postal_code, role, status, created_at, updated_at) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                        $stmt->execute([$name, $email, $hashed_password, $phone, $address, $city, $postal_code, $role, $status]);
                        setAlert('User added successfully!', 'success');
                        redirect('users.php?success=1');
                    }
                } elseif (isset($_POST['edit_user'])) {
                    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
                    if (!empty($password)) { // If password is being changed
                        if (strlen($password) < PASSWORD_MIN_LENGTH) {
                            setAlert('New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.', 'danger');
                        } else {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, phone=?, address=?, city=?, postal_code=?, role=?, status=?, updated_at=NOW() WHERE id=?");
                            $stmt->execute([$name, $email, $hashed_password, $phone, $address, $city, $postal_code, $role, $status, $user_id]);
                            setAlert('User updated successfully (with new password)!', 'success');
                            redirect('users.php?success=1');
                        }
                    } else { // Password not changed
                        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, city=?, postal_code=?, role=?, status=?, updated_at=NOW() WHERE id=?");
                        $stmt->execute([$name, $email, $phone, $address, $city, $postal_code, $role, $status, $user_id]);
                        setAlert('User updated successfully!', 'success');
                        redirect('users.php?success=1');
                    }
                }
            } catch (PDOException $e) {
                 if ($e->getCode() == 23000) { 
                    setAlert('Database error: Email already exists.', 'danger');
                } else {
                    setAlert('Database error: ' . $e->getMessage(), 'danger');
                }
            }
        }
    }
}

// Handle delete user
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        // Prevent deleting the currently logged-in admin or the last admin
        $can_delete = true;
        if ($delete_id == $_SESSION['admin_id']) { // Assuming admin_id is set in session for backend
            setAlert('You cannot delete your own account.', 'danger');
            $can_delete = false;
        } else {
            $stmt_check_admin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt_check_admin->execute();
            $admin_count = $stmt_check_admin->fetchColumn();

            $stmt_user_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt_user_role->execute([$delete_id]);
            $user_to_delete_role = $stmt_user_role->fetchColumn();

            if ($user_to_delete_role === 'admin' && $admin_count <= 1) {
                setAlert('Cannot delete the last admin account.', 'danger');
                $can_delete = false;
            }
        }

        if ($can_delete) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$delete_id]);
                setAlert('User deleted successfully!', 'success');
            } catch (PDOException $e) {
                setAlert('Database error: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('users.php');
    }
}

// Fetch users for display
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search_param = "%$search_term%";

$sql_count = "SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute([$search_param, $search_param]);
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $limit);

$sql = "SELECT * FROM users 
        WHERE name LIKE ? OR email LIKE ?
        ORDER BY id ASC 
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$search_param, $search_param, $limit, $offset]);
$users = $stmt->fetchAll();

// Fetch user for editing
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
    if ($edit_id) {
        $stmt_edit = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_edit->execute([$edit_id]);
        $edit_user = $stmt_edit->fetch();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
    </ol>

    <?php displayAlert(); ?>

    <!-- Add/Edit User Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
            <?php echo $edit_user ? 'Edit User' : 'Add New User'; ?>
        </div>
        <div class="card-body">
            <form method="POST" action="users.php">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_user['name'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $edit_user['email'] ?? ''; ?>" required>
                    </div>
                </div>

                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo !$edit_user ? 'required' : ''; ?>>
                        <?php if (!$edit_user): ?> <small class="form-text text-muted">Min <?php echo PASSWORD_MIN_LENGTH; ?> characters.</small> <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $edit_user['phone'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo $edit_user['address'] ?? ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo $edit_user['city'] ?? ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo $edit_user['postal_code'] ?? ''; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo (isset($edit_user['role']) && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo (isset($edit_user['status']) && $edit_user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($edit_user['status']) && $edit_user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo (isset($edit_user['status']) && $edit_user['status'] == 'banned') ? 'selected' : ''; ?>>Banned</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="<?php echo $edit_user ? 'edit_user' : 'add_user'; ?>" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
                </button>
                <?php if ($edit_user): ?>
                    <a href="users.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Users List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Users List
             <form method="GET" action="users.php" class="d-inline-flex float-end">
                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $user['role'] == 'admin' ? 'primary' : 'secondary'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : ($user['status'] == 'inactive' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="users.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($user['id'] != ($_SESSION['admin_id'] ?? null)): // Prevent deleting self ?>
                                        <a href="users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
             <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
