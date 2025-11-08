<?php
// Save this as: test_uploads.php in your ROOT directory
// Access it: https://yoursite.com/test_uploads.php

echo "<h2>Upload Directory Test</h2>";
echo "<pre>";

echo "=== PATHS ===\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current File: " . __FILE__ . "\n";
echo "Current Directory: " . __DIR__ . "\n\n";

echo "=== UPLOADS DIRECTORIES ===\n";

// Check profile uploads
$profileDir = __DIR__ . '/uploads/profile/';
echo "Profile Directory: $profileDir\n";
echo "  Exists: " . (file_exists($profileDir) ? 'YES' : 'NO') . "\n";
echo "  Is Directory: " . (is_dir($profileDir) ? 'YES' : 'NO') . "\n";
echo "  Is Link: " . (is_link($profileDir) ? 'YES' : 'NO') . "\n";
if (is_link($profileDir)) {
    echo "  Links To: " . readlink($profileDir) . "\n";
}
echo "  Writable: " . (is_writable($profileDir) ? 'YES' : 'NO') . "\n";
if (file_exists($profileDir)) {
    echo "  Permissions: " . substr(sprintf('%o', fileperms($profileDir)), -4) . "\n";
    $files = scandir($profileDir);
    $fileCount = count($files) - 2; // Exclude . and ..
    echo "  Files: $fileCount\n";
    if ($fileCount > 0 && $fileCount < 10) {
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "    - $file\n";
            }
        }
    }
}
echo "\n";

// Check branches uploads
$branchesDir = __DIR__ . '/uploads/branches/';
echo "Branches Directory: $branchesDir\n";
echo "  Exists: " . (file_exists($branchesDir) ? 'YES' : 'NO') . "\n";
echo "  Is Directory: " . (is_dir($branchesDir) ? 'YES' : 'NO') . "\n";
echo "  Is Link: " . (is_link($branchesDir) ? 'YES' : 'NO') . "\n";
if (is_link($branchesDir)) {
    echo "  Links To: " . readlink($branchesDir) . "\n";
}
echo "  Writable: " . (is_writable($branchesDir) ? 'YES' : 'NO') . "\n";
if (file_exists($branchesDir)) {
    echo "  Permissions: " . substr(sprintf('%o', fileperms($branchesDir)), -4) . "\n";
    $files = scandir($branchesDir);
    $fileCount = count($files) - 2;
    echo "  Files: $fileCount\n";
    if ($fileCount > 0 && $fileCount < 10) {
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "    - $file\n";
            }
        }
    }
}
echo "\n";

// Check Railway environment
echo "=== RAILWAY ENVIRONMENT ===\n";
$volumePath = getenv('RAILWAY_VOLUME_MOUNT_PATH');
if ($volumePath) {
    echo "Railway Volume Path: $volumePath\n";
    echo "  Exists: " . (file_exists($volumePath) ? 'YES' : 'NO') . "\n";
    echo "  Writable: " . (is_writable($volumePath) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($volumePath . '/profile')) {
        echo "  Profile subfolder exists: YES\n";
        $files = scandir($volumePath . '/profile');
        echo "    Files: " . (count($files) - 2) . "\n";
    }
    
    if (file_exists($volumePath . '/branches')) {
        echo "  Branches subfolder exists: YES\n";
        $files = scandir($volumePath . '/branches');
        echo "    Files: " . (count($files) - 2) . "\n";
    }
} else {
    echo "Not running on Railway (no volume path detected)\n";
}
echo "\n";

// Check database images
echo "=== DATABASE CHECK ===\n";
require_once 'db.php';

if (isset($conn)) {
    $sql = "SELECT AboutID, BranchName, Picture FROM about_us";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        echo "Branches in database:\n";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "  Branch ID {$row['AboutID']} ({$row['BranchName']}):\n";
            echo "    Picture in DB: " . ($row['Picture'] ?: 'NULL') . "\n";
            if ($row['Picture']) {
                $fullPath = __DIR__ . '/' . $row['Picture'];
                echo "    Full path: $fullPath\n";
                echo "    File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            }
        }
    }
} else {
    echo "Database connection failed!\n";
}

echo "\n=== RECOMMENDATIONS ===\n";

if (!file_exists($branchesDir)) {
    echo "❌ Branches directory doesn't exist!\n";
    echo "   Run: mkdir -p uploads/branches && chmod 755 uploads/branches\n";
} elseif (!is_writable($branchesDir)) {
    echo "❌ Branches directory not writable!\n";
    echo "   Run: chmod 755 uploads/branches\n";
} else {
    echo "✅ Branches directory is ready\n";
}

if (!file_exists($profileDir)) {
    echo "❌ Profile directory doesn't exist!\n";
    echo "   Run: mkdir -p uploads/profile && chmod 755 uploads/profile\n";
} elseif (!is_writable($profileDir)) {
    echo "❌ Profile directory not writable!\n";
    echo "   Run: chmod 755 uploads/profile\n";
} else {
    echo "✅ Profile directory is ready\n";
}

echo "</pre>";

// Add some CSS
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
h2 { color: #c0392b; }
pre { background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; line-height: 1.6; }
</style>";
?>