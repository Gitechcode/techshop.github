<?php
// TechShop Installation Script
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // Increase execution time limit

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>TechShop Installation</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 20px; background-color: #f0f2f5; color: #333; line-height: 1.6; }
        .container { max-width: 800px; margin: 20px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        h1 { color: #1a253c; text-align: center; margin-bottom: 25px; font-weight: 600; border-bottom: 1px solid #e0e0e0; padding-bottom: 15px; }
        h2 { color: #007bff; margin-top: 25px; margin-bottom: 12px; font-size: 1.3em; font-weight: 500; }
        .message { padding: 12px 18px; margin-bottom: 15px; border-radius: 5px; border-left-width: 4px; border-left-style: solid; font-size: 0.95em; }
        .success { background-color: #e6ffed; color: #006421; border-left-color: #28a745; }
        .error { background-color: #ffebee; color: #c62828; border-left-color: #dc3545; }
        .info { background-color: #e7f3fe; color: #0c5460; border-left-color: #17a2b8; }
        .warning { background-color: #fff8e1; color: #856404; border-left-color: #ffc107; }
        code { background-color: #f8f9fa; padding: 3px 6px; border-radius: 4px; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace; font-size: 0.9em; border: 1px solid #dee2e6;}
        .button-container { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;}
        .button { display: inline-block; background-color: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 1em; font-weight: 500; transition: background-color 0.2s ease, transform 0.2s ease; margin: 0 5px; }
        .button:hover { background-color: #0056b3; transform: translateY(-2px); }
        .button.admin { background-color: #28a745; }
        .button.admin:hover { background-color: #1e7e34; }
        .credentials { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .credentials strong { display: inline-block; width: 70px; font-weight: 500; }
        .php-info-check { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; border: 1px solid #eee;}
        .php-info-check summary { font-weight: bold; cursor: pointer; color: #007bff; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🛒 TechShop Installation Script</h1>";

// Database configuration (should match config/config.php for consistency, but defined here for standalone setup)
$db_host = 'localhost';
$db_name = 'tech_shop'; // Ensure this matches your intended database name
$db_user = 'root';      // Default XAMPP/WAMP username
$db_pass = '';          // Default XAMPP/WAMP password (often empty)

$pdo = null;
$pdo_server = null;
$sql_setup_file = 'database_setup.sql'; // The single SQL file

try {
    echo "<details class='php-info-check'><summary>PHP Environment Check</summary>";
    echo "<ul class='list-group list-group-flush'>";
    echo "<li class='list-group-item'>PHP Version: " . phpversion() . (version_compare(phpversion(), '7.4.0', '>=') ? " <span class='badge bg-success'>Recommended</span>" : " <span class='badge bg-warning'>Older version, consider upgrading</span>") . "</li>";
    echo "<li class='list-group-item'>PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? "<span class='badge bg-success'>Enabled</span>" : "<span class='badge bg-danger'>Not Enabled - Required!</span>") . "</li>";
    echo "<li class='list-group-item'>JSON Extension: " . (extension_loaded('json') ? "<span class='badge bg-success'>Enabled</span>" : "<span class='badge bg-danger'>Not Enabled - Required!</span>") . "</li>";
    echo "</ul></details>";

    echo "<h2>Step 1: Connecting to MySQL Server...</h2>";
    $pdo_server = new PDO("mysql:host=$db_host", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "<div class='message success'>✅ Successfully connected to MySQL server at <code>{$db_host}</code>.</div>";

    echo "<h2>Step 2: Creating Database '{$db_name}'...</h2>";
    $pdo_server->exec("DROP DATABASE IF EXISTS `{$db_name}`;");
    echo "<div class='message info'>ℹ️ Dropped database `{$db_name}` if it existed.</div>";
    $pdo_server->exec("CREATE DATABASE `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "<div class='message success'>✅ Database '{$db_name}' created successfully.</div>";
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "<div class='message success'>✅ Successfully connected to '{$db_name}' database.</div>";

    echo "<h2>Step 3: Setting up Database Tables & Sample Data...</h2>";
    $sql_content = file_get_contents($sql_setup_file);
    if ($sql_content === false) {
        throw new Exception("❌ Error: Could not read '{$sql_setup_file}'. Make sure the file exists in the 'setup' directory.");
    }
    $pdo->exec($sql_content); // PDO can execute multiple statements separated by semicolons if the driver supports it.
    echo "<div class='message success'>✅ Database schema created and sample data inserted successfully from '{$sql_setup_file}'.</div>";

    echo "<h2>Step 4: Verifying Installation...</h2>";
    $tables_to_check = ['users', 'categories', 'products', 'orders', 'settings']; // Check a few key tables
    $all_tables_exist = true;
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $record_count = $count_stmt->fetchColumn();
            echo "<div class='message info'>✔️ Table '<code>{$table}</code>' exists (Found {$record_count} records).</div>";
        } else {
            echo "<div class='message error'>❌ Table '<code>{$table}</code>' is missing! SQL import might have failed.</div>";
            $all_tables_exist = false;
        }
    }

    if ($all_tables_exist) {
        echo "<div class='message success text-center fs-5 p-3'>";
        echo "<strong>🎉 Installation Complete! TechShop is ready. 🎉</strong>";
        echo "</div>";

        echo "<div class='credentials alert alert-light'>";
        echo "<h3 class='alert-heading'>Default Login Credentials:</h3>";
        echo "<p><strong>Admin:</strong> <code>admin@techshop.com</code> / Password: <code>admin123</code> (or the one in your SQL file)</p>";
        echo "<p><strong>User:</strong> <code>john@example.com</code> / Password: <code>user123</code> (or the one in your SQL file)</p>";
        echo "<p class='text-danger'><strong>Important:</strong> Change these default passwords immediately after logging in for security!</p>";
        echo "</div>";

        // Determine base URL for links
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        // Assuming 'setup' is a direct child of the project root
        $project_root_url_segment = dirname($_SERVER['SCRIPT_NAME']); 
        if ($project_root_url_segment === '/' || $project_root_url_segment === '\\') {
            $project_root_url_segment = '';
        } else {
            // Go one level up from /setup
            $project_root_url_segment = dirname($project_root_url_segment);
             if ($project_root_url_segment === '/' || $project_root_url_segment === '\\') {
                $project_root_url_segment = '';
            }
        }
        $project_root_url = rtrim($protocol . $host . $project_root_url_segment, '/');

        echo "<div class='button-container'>";
        echo "<a href='{$project_root_url}/frontend/index.php' class='button btn btn-primary'>🏠 Visit Frontend Store</a>";
        echo "<a href='{$project_root_url}/backend/login.php' class='button admin btn btn-success'>⚙️ Go to Admin Panel</a>";
        echo "</div>";
    } else {
        echo "<div class='message error'>❌ Installation incomplete due to missing tables. Please check the '{$sql_setup_file}' file and error messages above, then try again.</div>";
    }

} catch (PDOException $e) {
    echo "<div class='message error'><h3>❌ Database Error Occurred:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    echo "<p><strong>Troubleshooting Tips:</strong></p><ul class='list-group'>";
    echo "<li class='list-group-item'>Ensure your MySQL server (e.g., XAMPP, WAMP) is running.</li>";
    echo "<li class='list-group-item'>Verify the database credentials (<code>\$db_host</code>, <code>\$db_user</code>, <code>\$db_pass</code>) at the top of this script.</li>";
    echo "<li class='list-group-item'>Check if the MySQL user '<code>{$db_user}</code>' has privileges to CREATE DATABASE and tables on <code>{$db_host}</code>.</li>";
    echo "<li class='list-group-item'>Make sure '{$sql_setup_file}' is in the same directory as this script ('setup/').</li>";
    echo "<li class='list-group-item'>If you see 'Access denied' errors, check your MySQL user permissions.</li>";
    echo "</ul></div>";
} catch (Exception $e) {
    echo "<div class='message error'><h3>❌ An Error Occurred:</h3><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
} finally {
    if ($pdo) {
        $pdo = null; // Close the connection
    }
    if ($pdo_server) {
        $pdo_server = null;
    }
}

echo "</div> <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script></body></html>";
?>
