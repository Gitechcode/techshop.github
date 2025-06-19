<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'TechShop');

// Auto-detect Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Assumes config.php is in 'config/' directory, one level down from project root.
$script_dir_path = dirname($_SERVER['SCRIPT_NAME']); // Path of the script being executed
// Find the project root by going up one level from where config.php is likely included (e.g., frontend/index.php)
// This logic might need adjustment if your structure is very different or if config.php is included from deeper.
// A more reliable way is to set it manually if auto-detection fails.
$project_root_url_segment = dirname(dirname($_SERVER['SCRIPT_NAME']));
if ($project_root_url_segment === '/' || $project_root_url_segment === '\\') {
    $project_root_url_segment = '';
}
define('SITE_URL', rtrim($protocol . $host . $project_root_url_segment, '/'));


define('FRONTEND_URL', SITE_URL . '/frontend');
define('BACKEND_URL', SITE_URL . '/backend');

// Physical path for uploads - __DIR__ is the 'config' directory
define('PROJECT_ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', PROJECT_ROOT_PATH . '/public/uploads');
define('UPLOADS_URL', SITE_URL . '/public/uploads');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tech_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Password configuration
define('PASSWORD_MIN_LENGTH', 6); // Minimum password length

// Development mode (true for development, false for production)
define('DEVELOPMENT', true);

// Error reporting
if (DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    // In production, you might want to log errors to a file:
    // ini_set('log_errors', 1);
    // ini_set('error_log', PROJECT_ROOT_PATH . '/logs/php_errors.log');
}

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    if (DEVELOPMENT) {
        die("Database connection failed: " . $e->getMessage() . "<br><br>Please ensure:<br>1. MySQL server (like XAMPP or WAMP) is running.<br>2. The database '<b>" . DB_NAME . "</b>' exists.<br>3. Database credentials (user/password) are correct in <b>config/config.php</b>.<br>4. If the database doesn't exist, run <b>setup/install.php</b> first.");
    } else {
        // Generic error for production
        die("A critical error occurred with the database connection. Please try again later or contact support.");
    }
}

// Helper functions
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

function requireLogin($redirectPage = 'login.php') {
    if (!isLoggedIn()) {
        setAlert('Please login to access this page.', 'warning');
        // Ensure the redirect URL is absolute or correctly relative to the current script's location
        $redirectTarget = (strpos($redirectPage, 'http') === 0) ? $redirectPage : FRONTEND_URL . '/' . ltrim($redirectPage, '/');
        header('Location: ' . $redirectTarget);
        exit;
    }
}

function requireAdmin($redirectPage = 'login.php') { // Admin login is usually in backend
    if (!isAdmin()) {
        setAlert('You do not have permission to access this page.', 'danger');
        $redirectTarget = (strpos($redirectPage, 'http') === 0) ? $redirectPage : BACKEND_URL . '/' . ltrim($redirectPage, '/');
        header('Location: ' . $redirectTarget);
        exit;
    }
}


function formatPrice($price) {
    if (!is_numeric($price)) {
        return '$0.00';
    }
    return '$' . number_format((float)$price, 2);
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        // Fallback if headers already sent
        echo "<script type='text/javascript'>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit;
    }
}

function generateSlug($text, $table = '', $column = 'slug', $id_to_ignore = null) {
    global $pdo;
    $slug = preg_replace('~[^\pL\d]+~u', '-', (string)$text);
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    $slug = preg_replace('~[^-\w]+~', '', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('~-+~', '-', $slug);
    $slug = strtolower($slug);

    if (empty($slug)) {
        $slug = 'n-a-' . time() . rand(100,999); // More unique fallback
    }

    // Ensure slug uniqueness if table and pdo are provided
    if (!empty($table) && $pdo) {
        $original_slug = $slug;
        $counter = 1;
        $query = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?";
        $params = [$slug];
        if ($id_to_ignore !== null) {
            $query .= " AND `id` != ?";
            $params[] = $id_to_ignore;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        while ($stmt->fetchColumn() > 0) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
            $params[0] = $slug; // Update slug in params
            $stmt->execute($params);
        }
    }
    return $slug;
}

// Alert system
function setAlert($message, $type = 'info') {
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = $type;
}

function displayAlert() {
    if (isset($_SESSION['alert_message']) && isset($_SESSION['alert_type'])) {
        // Ensure this is called before any significant HTML output or within a buffer
        echo '<div class="container mt-3" id="alert-container"><div class="alert alert-' . htmlspecialchars($_SESSION['alert_type']) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['alert_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div></div>';
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
    }
}

// Cart functions
function getCartCount() {
    global $pdo;
    if (!$pdo) return 0;

    try {
        $count = 0;
        if (isLoggedIn()) {
            $stmt = $pdo->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            $count = $result ? (int)$result['total_items'] : 0;
        } else {
            $session_id = session_id();
            if (empty($session_id)) { // Ensure session_id is available
                 return 0;
            }
            $stmt = $pdo->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $result = $stmt->fetch();
            $count = $result ? (int)$result['total_items'] : 0;
        }
        return $count;
    } catch (Exception $e) {
        if(DEVELOPMENT) error_log("Error in getCartCount: " . $e->getMessage());
        return 0;
    }
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), 7, 6));
}

// Set timezone
date_default_timezone_set('America/New_York'); // Adjust to your timezone

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0775, true);
}
if (!is_dir(UPLOADS_PATH . '/products')) {
    mkdir(UPLOADS_PATH . '/products', 0775, true);
}
if (!is_dir(UPLOADS_PATH . '/categories')) {
    mkdir(UPLOADS_PATH . '/categories', 0775, true);
}

?>
