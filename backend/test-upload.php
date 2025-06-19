<?php
// Simple upload test script
echo "<h2>Upload Directory Test</h2>";

$upload_dir = dirname(__DIR__) . '/public/uploads/products/';
echo "<p><strong>Upload Directory:</strong> " . $upload_dir . "</p>";

// Check if directory exists
if (is_dir($upload_dir)) {
    echo "<p>✅ Directory exists</p>";
} else {
    echo "<p>❌ Directory does not exist</p>";
    echo "<p>Attempting to create directory...</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p>✅ Directory created successfully</p>";
    } else {
        echo "<p>❌ Failed to create directory</p>";
    }
}

// Check if directory is writable
if (is_writable($upload_dir)) {
    echo "<p>✅ Directory is writable</p>";
} else {
    echo "<p>❌ Directory is NOT writable</p>";
}

// Check directory permissions
$perms = fileperms($upload_dir);
echo "<p><strong>Directory Permissions:</strong> " . substr(sprintf('%o', $perms), -4) . "</p>";

// Check directory owner
$owner = posix_getpwuid(fileowner($upload_dir));
echo "<p><strong>Directory Owner:</strong> " . $owner['name'] . "</p>";

// Check current user
$current_user = posix_getpwuid(posix_geteuid());
echo "<p><strong>Current PHP User:</strong> " . $current_user['name'] . "</p>";

// Test file creation
$test_file = $upload_dir . 'test_' . time() . '.txt';
if (file_put_contents($test_file, 'test content')) {
    echo "<p>✅ Successfully created test file: " . basename($test_file) . "</p>";
    unlink($test_file); // Clean up
} else {
    echo "<p>❌ Failed to create test file</p>";
}

// Show upload_tmp_dir
echo "<p><strong>PHP upload_tmp_dir:</strong> " . ini_get('upload_tmp_dir') . "</p>";
echo "<p><strong>PHP upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>PHP post_max_size:</strong> " . ini_get('post_max_size') . "</p>";

// Test actual file upload simulation
echo "<hr><h3>File Upload Test Form</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h4>Upload Results:</h4>";
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['test_file']['tmp_name'];
        $name = $_FILES['test_file']['name'];
        $destination = $upload_dir . 'test_upload_' . time() . '_' . $name;
        
        if (move_uploaded_file($tmp_name, $destination)) {
            echo "<p>✅ File uploaded successfully to: " . basename($destination) . "</p>";
            // Clean up
            unlink($destination);
        } else {
            echo "<p>❌ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p>❌ Upload error: " . $_FILES['test_file']['error'] . "</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <p>Test file upload:</p>
    <input type="file" name="test_file" accept="image/*">
    <button type="submit">Test Upload</button>
</form>
