<?php
// Temporary debug file to check what's wrong
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Backend Debug Information</h2>";

// Check if config file exists
$config_path = '../config/config.php';
echo "<p><strong>Config file path:</strong> " . realpath($config_path) . "</p>";
echo "<p><strong>Config file exists:</strong> " . (file_exists($config_path) ? 'YES' : 'NO') . "</p>";

if (file_exists($config_path)) {
    echo "<p>Attempting to include config...</p>";
    try {
        require_once $config_path;
        echo "<p>✅ Config loaded successfully</p>";
        echo "<p><strong>Database connection:</strong> " . (isset($pdo) ? 'Connected' : 'Not connected') . "</p>";
        
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
                $admin_count = $stmt->fetchColumn();
                echo "<p><strong>Admin users found:</strong> " . $admin_count . "</p>";
            } catch (Exception $e) {
                echo "<p><strong>Database query error:</strong> " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Config file not found!</p>";
}

// Check session
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session data:</strong> " . print_r($_SESSION, true) . "</p>";

// Check file permissions
echo "<p><strong>Current directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Files in backend directory:</strong></p>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . " (readable: " . (is_readable($file) ? 'YES' : 'NO') . ")</li>";
    }
}
?>
