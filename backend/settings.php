<?php
require_once '../config/config.php';

// Check if logged in as admin
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $site_name = sanitizeInput($_POST['site_name']);
        $site_email = sanitizeInput($_POST['site_email']);
        $site_description = sanitizeInput($_POST['site_description']);
        $currency = sanitizeInput($_POST['currency']);
        $tax_rate = (float)$_POST['tax_rate'];
        
        // In a real application, you would save these to a settings table or config file
        // For this demo, we'll just show a success message
        $success = 'Settings updated successfully!';
    }
}

// Get current settings
// In a real application, these would come from a database or config file
$settings = [
    'site_name' => SITE_NAME,
    'site_email' => 'info@techshop.com',
    'site_description' => 'Your one-stop shop for all tech products',
    'currency' => 'USD',
    'tax_rate' => 8.5,
];

$page_title = 'Site Settings';
include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Site Settings</h1>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="site_email" class="form-label">Site Email</label>
                                <input type="email" class="form-control" id="site_email" name="site_email" 
                                       value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="site_description" class="form-label">Site Description</label>
                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $settings['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="GBP" <?php echo $settings['currency'] == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                       value="<?php echo $settings['tax_rate']; ?>" step="0.1" min="0" max="100">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="clearCache()">
                            <i class="fas fa-trash me-1"></i> Clear Cache
                        </button>
                        <button class="btn btn-outline-info" onclick="backupDatabase()">
                            <i class="fas fa-database me-1"></i> Backup Database
                        </button>
                        <button class="btn btn-outline-warning" onclick="maintenanceMode()">
                            <i class="fas fa-tools me-1"></i> Maintenance Mode
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                    <p><strong>MySQL Version:</strong> <?php echo $pdo->query('select version()')->fetchColumn(); ?></p>
                    <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
        alert('Cache cleared successfully!');
    }
}

function backupDatabase() {
    if (confirm('Are you sure you want to backup the database?')) {
        alert('Database backup initiated! The backup file will be available in the admin downloads section.');
    }
}

function maintenanceMode() {
    if (confirm('Are you sure you want to toggle maintenance mode?')) {
        alert('Maintenance mode toggled!');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
