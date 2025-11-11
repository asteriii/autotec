<?php
/**
 * Homepage Upload Directory Checker
 * Access this via: yourdomain.com/AdminSide/check_homepage_uploads.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Homepage Upload Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .check-item {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .check-item.success {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        .check-item.error {
            border-color: #f44336;
            background: #ffebee;
        }
        .check-item.warning {
            border-color: #ff9800;
            background: #fff3e0;
        }
        .check-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .check-detail {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
            font-family: monospace;
        }
        .icon {
            display: inline-block;
            margin-right: 10px;
            font-size: 20px;
        }
        code {
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
        .file-list {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        .file-item {
            padding: 5px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏠 Homepage Upload Directory Check</h1>
        <p>Diagnostic tool for homepage image uploads</p>

        <?php
        $checks = [];
        
        // Check 1: Document Root
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $checks[] = [
            'status' => 'success',
            'title' => 'Document Root',
            'details' => ["Path: $docRoot"]
        ];

        // Check 2: Uploads Base Directory
        $uploadsBase = $docRoot . '/uploads';
        if (file_exists($uploadsBase)) {
            $isWritable = is_writable($uploadsBase);
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Uploads Base Directory',
                'details' => [
                    "Path: $uploadsBase",
                    "Exists: YES",
                    "Writable: " . ($isWritable ? 'YES' : 'NO'),
                    "Permissions: " . substr(sprintf('%o', fileperms($uploadsBase)), -4)
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'error',
                'title' => 'Uploads Base Directory',
                'details' => [
                    "Path: $uploadsBase",
                    "Status: DOES NOT EXIST"
                ]
            ];
        }

        // Check 3: Homepage Directory
        $homepageDir = $docRoot . '/uploads/homepage';
        if (file_exists($homepageDir)) {
            $isWritable = is_writable($homepageDir);
            $fileCount = count(glob($homepageDir . '/*'));
            
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Homepage Upload Directory',
                'details' => [
                    "Path: $homepageDir",
                    "Exists: YES",
                    "Writable: " . ($isWritable ? 'YES' : 'NO'),
                    "Permissions: " . substr(sprintf('%o', fileperms($homepageDir)), -4),
                    "Files: $fileCount"
                ]
            ];

            // List files in homepage directory
            $files = glob($homepageDir . '/*');
            if (!empty($files)) {
                $fileList = '<div class="file-list">';
                foreach (array_slice($files, 0, 20) as $file) {
                    $filename = basename($file);
                    $size = filesize($file);
                    $sizeKB = round($size / 1024, 2);
                    $fileList .= "<div class='file-item'>📄 $filename ($sizeKB KB)</div>";
                }
                if (count($files) > 20) {
                    $fileList .= "<div class='file-item'>... and " . (count($files) - 20) . " more files</div>";
                }
                $fileList .= '</div>';
                
                $checks[] = [
                    'status' => 'success',
                    'title' => 'Homepage Files',
                    'details' => [
                        "Total files: " . count($files),
                        $fileList
                    ]
                ];
            }
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'Homepage Upload Directory',
                'details' => [
                    "Path: $homepageDir",
                    "Status: DOES NOT EXIST",
                    "Will be created on first upload"
                ]
            ];
        }

        // Check 4: Database Configuration
        include 'db.php';
        $stmt = $conn->query("SELECT service1_img, service2_img, service3_img, announcement_img FROM homepage WHERE id = 1");
        if ($stmt) {
            $row = $stmt->fetch_assoc();
            $dbFiles = [
                'Service 1' => $row['service1_img'] ?? 'NULL',
                'Service 2' => $row['service2_img'] ?? 'NULL',
                'Service 3' => $row['service3_img'] ?? 'NULL',
                'Announcement' => $row['announcement_img'] ?? 'NULL'
            ];
            
            $dbDetails = [];
            foreach ($dbFiles as $label => $filename) {
                $exists = !empty($filename) && file_exists($homepageDir . '/' . $filename);
                $status = $exists ? '✅' : ($filename === 'NULL' ? '⚠️' : '❌');
                $dbDetails[] = "$status $label: " . ($filename === 'NULL' ? 'Not set' : $filename);
            }
            
            $checks[] = [
                'status' => 'success',
                'title' => 'Database Image Paths',
                'details' => $dbDetails
            ];
        }

        // Check 5: PHP Upload Settings
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxFileUploads = ini_get('max_file_uploads');
        
        $checks[] = [
            'status' => 'success',
            'title' => 'PHP Upload Configuration',
            'details' => [
                "upload_max_filesize: $uploadMaxFilesize",
                "post_max_size: $postMaxSize",
                "max_file_uploads: $maxFileUploads"
            ]
        ];

        // Check 6: Railway Volume (if mounted)
        $isRailwayVolume = is_link($uploadsBase) || getenv('RAILWAY_VOLUME_MOUNT_PATH');
        if ($isRailwayVolume) {
            $volumePath = getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: 'Unknown';
            $checks[] = [
                'status' => 'success',
                'title' => 'Railway Volume Detection',
                'details' => [
                    "Volume detected: YES",
                    "Mount path: " . ($volumePath !== 'Unknown' ? $volumePath : 'Direct mount to /uploads'),
                    "Type: " . (is_link($uploadsBase) ? 'Symlink' : 'Direct mount')
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'Railway Volume Detection',
                'details' => [
                    "Volume detected: NO",
                    "Storage: Local (ephemeral)",
                    "Note: Files will be lost on redeployment"
                ]
            ];
        }

        // Display all checks
        foreach ($checks as $check) {
            $icon = $check['status'] === 'success' ? '✅' : 
                   ($check['status'] === 'warning' ? '⚠️' : '❌');
            
            echo "<div class='check-item {$check['status']}'>";
            echo "<div class='check-title'><span class='icon'>$icon</span>{$check['title']}</div>";
            foreach ($check['details'] as $detail) {
                if (strpos($detail, '<div') === 0) {
                    echo $detail; // HTML content
                } else {
                    echo "<div class='check-detail'>$detail</div>";
                }
            }
            echo "</div>";
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;">
            <h3 style="margin-top: 0;">📝 Troubleshooting Steps:</h3>
            <ul style="line-height: 1.8;">
                <li>If uploads directory doesn't exist, it will be created automatically on first upload</li>
                <li>If permission errors occur, check Docker container permissions</li>
                <li>Ensure Railway volume is properly mounted to <code>/var/www/html/uploads</code></li>
                <li>Database stores only filenames, not full paths</li>
                <li>Files are accessed via: <code>uploads/homepage/filename.jpg</code></li>
            </ul>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="homepage-edit.php" style="display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                ← Back to Homepage Editor
            </a>
        </div>
    </div>
</body>
</html>