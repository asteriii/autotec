<?php
/**
 * Upload Debugging Script
 * Place this file in your project root and access it via browser
 * Example: http://localhost/your-project/test_upload.php
 */

echo "<h1>Upload Configuration Checker</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-left: 4px solid #333; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    td, th { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #333; color: white; }
</style>";

// 1. Check PHP Upload Settings
echo "<div class='section'>";
echo "<h2>1. PHP Upload Configuration</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$fileUploads = ini_get('file_uploads');
$maxFileUploads = ini_get('max_file_uploads');

echo "<tr><td>file_uploads</td><td>" . ($fileUploads ? 'On' : 'Off') . "</td><td class='" . ($fileUploads ? 'success' : 'error') . "'>" . ($fileUploads ? '✓' : '✗ DISABLED') . "</td></tr>";
echo "<tr><td>upload_max_filesize</td><td>$uploadMaxSize</td><td class='success'>✓</td></tr>";
echo "<tr><td>post_max_size</td><td>$postMaxSize</td><td class='success'>✓</td></tr>";
echo "<tr><td>max_file_uploads</td><td>$maxFileUploads</td><td class='success'>✓</td></tr>";

echo "</table>";
echo "</div>";

// 2. Check Directory Structure
echo "<div class='section'>";
echo "<h2>2. Directory Structure</h2>";

$uploadDir = __DIR__ . '/uploads/profile/';
$uploadDirRelative = 'uploads/profile/';

echo "<table>";
echo "<tr><th>Check</th><th>Path</th><th>Status</th></tr>";

// Check if directory exists
$dirExists = file_exists($uploadDir);
echo "<tr><td>Directory Exists</td><td>$uploadDir</td><td class='" . ($dirExists ? 'success' : 'error') . "'>" . ($dirExists ? '✓ YES' : '✗ NO') . "</td></tr>";

if (!$dirExists) {
    // Try to create it
    if (mkdir($uploadDir, 0755, true)) {
        echo "<tr><td colspan='3' class='success'>✓ Directory created successfully!</td></tr>";
        $dirExists = true;
    } else {
        echo "<tr><td colspan='3' class='error'>✗ Failed to create directory. Check parent folder permissions.</td></tr>";
    }
}

if ($dirExists) {
    // Check if writable
    $isWritable = is_writable($uploadDir);
    echo "<tr><td>Directory Writable</td><td>$uploadDir</td><td class='" . ($isWritable ? 'success' : 'error') . "'>" . ($isWritable ? '✓ YES' : '✗ NO - chmod 755 needed') . "</td></tr>";
    
    // Check permissions
    $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    echo "<tr><td>Directory Permissions</td><td>$perms</td><td class='" . ($perms >= '0755' ? 'success' : 'warning') . "'>$perms</td></tr>";
    
    // Check owner
    $owner = posix_getpwuid(fileowner($uploadDir));
    echo "<tr><td>Directory Owner</td><td>" . $owner['name'] . "</td><td class='success'>Info</td></tr>";
}

echo "</table>";
echo "</div>";

// 3. Check Web Server User
echo "<div class='section'>";
echo "<h2>3. Web Server Configuration</h2>";
echo "<table>";
echo "<tr><th>Property</th><th>Value</th></tr>";

// Current PHP user
$currentUser = posix_getpwuid(posix_geteuid());
echo "<tr><td>PHP Process User</td><td>" . $currentUser['name'] . "</td></tr>";

// Document root
echo "<tr><td>Document Root</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";

// Script location
echo "<tr><td>Script Location</td><td>" . __DIR__ . "</td></tr>";

// Temp directory
echo "<tr><td>Temp Directory</td><td>" . sys_get_temp_dir() . "</td></tr>";
echo "<tr><td>Temp Dir Writable</td><td class='" . (is_writable(sys_get_temp_dir()) ? 'success' : 'error') . "'>" . (is_writable(sys_get_temp_dir()) ? '✓ YES' : '✗ NO') . "</td></tr>";

echo "</table>";
echo "</div>";

// 4. Test File Upload
echo "<div class='section'>";
echo "<h2>4. Test File Upload</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Upload Test Results:</h3>";
    
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['test_file']['tmp_name'];
        $fileName = 'test_' . time() . '_' . basename($_FILES['test_file']['name']);
        $destPath = $uploadDir . $fileName;
        
        echo "<p>Source: $tmpName</p>";
        echo "<p>Destination: $destPath</p>";
        echo "<p>Temp file exists: " . (file_exists($tmpName) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</p>";
        
        if (move_uploaded_file($tmpName, $destPath)) {
            echo "<p class='success'>✓ File uploaded successfully!</p>";
            echo "<p>File saved at: $destPath</p>";
            echo "<p>File exists: " . (file_exists($destPath) ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO</span>') . "</p>";
            
            if (file_exists($destPath)) {
                echo "<p>File size: " . filesize($destPath) . " bytes</p>";
                echo "<p>File permissions: " . substr(sprintf('%o', fileperms($destPath)), -4) . "</p>";
                
                // Try to display image if it's an image
                if (getimagesize($destPath)) {
                    echo "<p><img src='$uploadDirRelative$fileName' style='max-width: 200px; border: 2px solid #333;'></p>";
                }
                
                // Clean up test file
                echo "<p><button onclick='if(confirm(\"Delete test file?\")) window.location.href=\"?delete=$fileName\"'>Delete Test File</button></p>";
            }
        } else {
            echo "<p class='error'>✗ Failed to move uploaded file</p>";
            echo "<p>Last error: " . print_r(error_get_last(), true) . "</p>";
        }
    } else {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload'
        ];
        echo "<p class='error'>Upload error: " . ($errors[$_FILES['test_file']['error']] ?? 'Unknown error') . "</p>";
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $fileToDelete = $uploadDir . basename($_GET['delete']);
    if (file_exists($fileToDelete) && unlink($fileToDelete)) {
        echo "<p class='success'>✓ Test file deleted successfully</p>";
    }
}

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<p>Upload a test image (max 5MB):</p>";
echo "<input type='file' name='test_file' accept='image/*' required>";
echo "<button type='submit' style='margin-left: 10px; padding: 8px 16px; background: #333; color: white; border: none; cursor: pointer;'>Upload Test File</button>";
echo "</form>";
echo "</div>";

// 5. Recommendations
echo "<div class='section'>";
echo "<h2>5. Recommendations</h2>";
echo "<ul>";

if (!$fileUploads) {
    echo "<li class='error'>Enable file_uploads in php.ini</li>";
}

if (!$dirExists) {
    echo "<li class='error'>Create the uploads/profile/ directory</li>";
} else if (!$isWritable) {
    echo "<li class='error'>Run: chmod 755 " . $uploadDir . "</li>";
    echo "<li class='error'>Or run: chown -R " . $currentUser['name'] . " " . $uploadDir . "</li>";
}

echo "<li>Ensure your web server has write access to the uploads directory</li>";
echo "<li>Check your Apache/Nginx error logs for more details</li>";
echo "<li>Verify .htaccess files aren't blocking uploads</li>";
echo "</ul>";
echo "</div>";

// 6. List existing files
echo "<div class='section'>";
echo "<h2>6. Existing Files in Upload Directory</h2>";

if ($dirExists) {
    $files = glob($uploadDir . '*');
    if (count($files) > 0) {
        echo "<table>";
        echo "<tr><th>Filename</th><th>Size</th><th>Modified</th><th>Permissions</th></tr>";
        foreach ($files as $file) {
            if (is_file($file)) {
                $perms = substr(sprintf('%o', fileperms($file)), -4);
                echo "<tr>";
                echo "<td>" . basename($file) . "</td>";
                echo "<td>" . number_format(filesize($file)) . " bytes</td>";
                echo "<td>" . date('Y-m-d H:i:s', filemtime($file)) . "</td>";
                echo "<td>$perms</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<p>No files found in upload directory</p>";
    }
} else {
    echo "<p class='error'>Upload directory doesn't exist</p>";
}
echo "</div>";
?>