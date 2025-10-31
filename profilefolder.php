<?php
/**
 * Create Profile Folder Structure
 * 
 * Save this file as: setup_profile_folders.php
 * Run it once by accessing: http://your-domain/setup_profile_folders.php
 * Then delete this file after running it.
 */

echo "<h1>Creating Profile Upload Folder...</h1>";

// Define the directory path
$uploadDir = 'uploads/profile/';

// Check if directory exists
if (file_exists($uploadDir)) {
    echo "<p style='color: orange;'>✓ Directory already exists: <strong>$uploadDir</strong></p>";
} else {
    // Try to create the directory
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color: green;'>✓ Successfully created directory: <strong>$uploadDir</strong></p>";
        
        // Create a test file to verify write permissions
        $testFile = $uploadDir . 'test.txt';
        if (file_put_contents($testFile, 'Test file created successfully!')) {
            echo "<p style='color: green;'>✓ Directory is writable!</p>";
            unlink($testFile); // Delete test file
        } else {
            echo "<p style='color: red;'>✗ Warning: Directory created but may not be writable!</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to create directory: <strong>$uploadDir</strong></p>";
        echo "<p>Please create this folder manually and set permissions.</p>";
    }
}

// Check if pictures folder exists for default avatar
$picturesDir = 'pictures/';
if (file_exists($picturesDir)) {
    echo "<p style='color: green;'>✓ Pictures directory exists</p>";
    
    // Check for default avatar
    if (file_exists($picturesDir . 'default-avatar.png')) {
        echo "<p style='color: green;'>✓ Default avatar image found</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Warning: Default avatar not found at <strong>pictures/default-avatar.png</strong></p>";
        echo "<p>Please add a default avatar image.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Pictures directory not found</p>";
}

// Display current file permissions
if (file_exists($uploadDir)) {
    $perms = fileperms($uploadDir);
    $perms_octal = substr(sprintf('%o', $perms), -4);
    echo "<p>Current permissions: <strong>$perms_octal</strong></p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If the folder was created successfully, you can delete this setup file.</li>";
echo "<li>Make sure <strong>pictures/default-avatar.png</strong> exists.</li>";
echo "<li>Test registration with profile picture upload.</li>";
echo "<li>Test profile picture update in profile page.</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='profile.php'>Go to Profile</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #333;
        border-bottom: 3px solid #bd1e51;
        padding-bottom: 10px;
    }
    p {
        padding: 10px;
        background: white;
        margin: 10px 0;
        border-radius: 5px;
    }
    ol {
        background: white;
        padding: 20px 40px;
        border-radius: 5px;
    }
    li {
        margin: 10px 0;
    }
</style>