<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile Picture Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #4CAF50; }
        .error { border-color: #f44336; }
        .success { color: #4CAF50; font-weight: bold; }
        .fail { color: #f44336; font-weight: bold; }
        img { max-width: 200px; border: 2px solid #ddd; margin: 10px; }
        code { background: #333; color: #0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Profile Picture Test</h1>
    
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="box error">
            <p class="fail">⚠️ You need to log in first!</p>
            <p><a href="index.php">Go to homepage and log in</a></p>
        </div>
    <?php else: ?>
        
        <div class="box">
            <h2>1. Session Check</h2>
            <p>User ID: <code><?php echo $_SESSION['user_id']; ?></code></p>
            <p>Username: <code><?php echo $_SESSION['username'] ?? 'Not set'; ?></code></p>
            <p>Session profile_picture: <code><?php echo $_SESSION['profile_picture'] ?? 'Not set'; ?></code></p>
        </div>
        
        <div class="box">
            <h2>2. Database Check</h2>
            <?php
            require_once 'db.php';
            $userId = $_SESSION['user_id'];
            $sql = "SELECT Username, profile_picture FROM users WHERE UserID = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($user) {
                echo "<p>Database Username: <code>" . htmlspecialchars($user['Username']) . "</code></p>";
                echo "<p>Database profile_picture: <code>" . htmlspecialchars($user['profile_picture'] ?? 'NULL') . "</code></p>";
            } else {
                echo "<p class='fail'>User not found in database!</p>";
            }
            ?>
        </div>
        
        <div class="box">
            <h2>3. File System Check</h2>
            <?php
            $uploadDir = __DIR__ . '/uploads/profile/';
            echo "<p>Upload directory: <code>" . htmlspecialchars($uploadDir) . "</code></p>";
            echo "<p>Directory exists: " . (is_dir($uploadDir) ? '<span class="success">YES</span>' : '<span class="fail">NO</span>') . "</p>";
            echo "<p>Directory is symlink: " . (is_link($uploadDir) ? '<span class="success">YES</span>' : 'NO') . "</p>";
            
            if (is_link($uploadDir)) {
                echo "<p>Symlink points to: <code>" . readlink($uploadDir) . "</code></p>";
            }
            
            echo "<p>Directory readable: " . (is_readable($uploadDir) ? '<span class="success">YES</span>' : '<span class="fail">NO</span>') . "</p>";
            ?>
        </div>
        
        <?php if (!empty($user['profile_picture'])): ?>
        <div class="box">
            <h2>4. Profile Picture Test</h2>
            <?php
            $filename = $user['profile_picture'];
            $relativePath = 'uploads/profile/' . $filename;
            $absolutePath = $uploadDir . $filename;
            
            echo "<p>Filename: <code>" . htmlspecialchars($filename) . "</code></p>";
            echo "<p>Relative path: <code>" . htmlspecialchars($relativePath) . "</code></p>";
            echo "<p>Absolute path: <code>" . htmlspecialchars($absolutePath) . "</code></p>";
            echo "<p>File exists: " . (file_exists($absolutePath) ? '<span class="success">YES</span>' : '<span class="fail">NO</span>') . "</p>";
            
            if (file_exists($absolutePath)) {
                echo "<p>File size: <code>" . filesize($absolutePath) . " bytes</code></p>";
                echo "<p>File readable: " . (is_readable($absolutePath) ? '<span class="success">YES</span>' : '<span class="fail">NO</span>') . "</p>";
                
                echo "<h3>Browser Test:</h3>";
                echo "<img src='" . htmlspecialchars($relativePath) . "?v=" . time() . "' onload=\"document.getElementById('img-success').style.display='block';\" onerror=\"document.getElementById('img-error').style.display='block';\">";
                echo "<p id='img-success' style='display:none;' class='success'>✓ Image loaded successfully in browser!</p>";
                echo "<p id='img-error' style='display:none;' class='fail'>✗ Image failed to load in browser - Apache cannot serve the file!</p>";
                
                echo "<h3>Direct Link Test:</h3>";
                echo "<p>Try accessing directly: <a href='" . htmlspecialchars($relativePath) . "' target='_blank'>" . htmlspecialchars($relativePath) . "</a></p>";
            }
            ?>
        </div>
        <?php else: ?>
        <div class="box">
            <h2>4. No Profile Picture</h2>
            <p>You don't have a profile picture yet. Upload one from your <a href="profile.php">profile page</a>.</p>
        </div>
        <?php endif; ?>
        
        <div class="box">
            <h2>5. All Files in Upload Directory</h2>
            <?php
            if (is_dir($uploadDir)) {
                $files = array_diff(scandir($uploadDir), ['.', '..']);
                if (count($files) > 0) {
                    echo "<p class='success'>Found " . count($files) . " file(s):</p>";
                    echo "<ul>";
                    foreach ($files as $file) {
                        $size = filesize($uploadDir . $file);
                        echo "<li><code>" . htmlspecialchars($file) . "</code> (" . $size . " bytes)</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='fail'>Directory is empty</p>";
                }
            }
            ?>
        </div>
        
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <a href="profile.php" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">← Back to Profile</a>
    </div>
</body>
</html>