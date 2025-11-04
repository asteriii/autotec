<?php
// Save this as: debug_paths.php in your ROOT directory
// Access it via: yoursite.com/debug_paths.php

echo "<h2>Path Debugging Information</h2>";
echo "<pre>";

// Current directory
echo "Current Directory: " . __DIR__ . "\n\n";

// Check uploads folder
$uploadDir = 'uploads/profile/';
echo "Upload Directory Path: " . $uploadDir . "\n";
echo "Full Upload Path: " . __DIR__ . '/' . $uploadDir . "\n";
echo "Directory Exists: " . (file_exists($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory Writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory Permissions: " . (file_exists($uploadDir) ? substr(sprintf('%o', fileperms($uploadDir)), -4) : 'N/A') . "\n\n";

// List files in uploads/profile
if (file_exists($uploadDir)) {
    echo "Files in $uploadDir:\n";
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadDir . $file;
            echo "  - $file (Size: " . filesize($filePath) . " bytes, Permissions: " . substr(sprintf('%o', fileperms($filePath)), -4) . ")\n";
        }
    }
} else {
    echo "Upload directory does not exist!\n";
    echo "\nTrying to create directory...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✓ Directory created successfully!\n";
        echo "New permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    } else {
        echo "✗ Failed to create directory. Check parent folder permissions.\n";
    }
}

echo "\n--- Database Check ---\n";
// Check database for profile pictures
include 'db.php';
if (isset($conn)) {
    $query = "SELECT UserID, Username, profile_picture FROM users WHERE profile_picture IS NOT NULL AND profile_picture != ''";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo "Users with profile pictures in database:\n";
        while ($row = mysqli_fetch_assoc($result)) {
            $picPath = $uploadDir . $row['profile_picture'];
            $exists = file_exists($picPath) ? '✓ EXISTS' : '✗ MISSING';
            echo "  UserID {$row['UserID']} ({$row['Username']}): {$row['profile_picture']} - $exists\n";
        }
    } else {
        echo "Database query failed: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Database connection failed!\n";
}

echo "</pre>";

// Create .htaccess if it doesn't exist in uploads
$htaccessPath = 'uploads/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "Options -Indexes\n";
    $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
    $htaccessContent .= "    Require all granted\n";
    $htaccessContent .= "</FilesMatch>";
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "<p style='color: green;'>✓ Created .htaccess file for uploads directory</p>";
    }
}

echo "<hr>";
echo "<h3>Quick Fixes:</h3>";
echo "<ol>";
echo "<li>If directory doesn't exist, create it manually via FTP/cPanel: <code>uploads/profile/</code></li>";
echo "<li>Set folder permissions to <code>0755</code></li>";
echo "<li>Set file permissions to <code>0644</code></li>";
echo "<li>Make sure uploads folder is in the ROOT directory (same level as index.php)</li>";
echo "</ol>";
?>