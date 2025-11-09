<?php
/**
 * Upload Setup Checker for Railway Volume
 * Place this file in your web root and access it via browser
 * to verify your upload directories are configured correctly
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Directory Setup Checker</title>
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
        .check-item.warning {
            border-color: #ff9800;
            background: #fff3e0;
        }
        .check-item.error {
            border-color: #f44336;
            background: #ffebee;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Upload Directory Setup Checker</h1>
        <p>This tool verifies your Railway volume and upload directory configuration.</p>

        <?php
        $checks = [];
        
        // Check 1: Railway Volume Environment Variable
        $volumePath = getenv('RAILWAY_VOLUME_MOUNT_PATH');
        if ($volumePath) {
            $checks[] = [
                'status' => 'success',
                'title' => 'Railway Volume Detected',
                'details' => [
                    "Volume path: $volumePath",
                    "This is the persistent storage location"
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'No Railway Volume Detected',
                'details' => [
                    "Running in local mode - uploads will not persist across deployments",
                    "To enable persistent storage, attach a Railway volume"
                ]
            ];
        }

        // Check 2: Document Root
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $checks[] = [
            'status' => 'success',
            'title' => 'Document Root',
            'details' => ["Path: $docRoot"]
        ];

        // Check 3: Uploads Directory
        $uploadsDir = $docRoot . '/uploads';
        if (file_exists($uploadsDir)) {
            $isWritable = is_writable($uploadsDir);
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Uploads Directory',
                'details' => [
                    "Path: $uploadsDir",
                    "Exists: YES",
                    "Writable: " . ($isWritable ? 'YES' : 'NO')
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'error',
                'title' => 'Uploads Directory Missing',
                'details' => [
                    "Path: $uploadsDir",
                    "Status: Does not exist",
                    "Action: Check Dockerfile startup script"
                ]
            ];
        }

        // Check 4: Payment Receipts Directory
        $paymentsDir = $docRoot . '/uploads/payment_receipts';
        if (file_exists($paymentsDir)) {
            $isSymlink = is_link($paymentsDir);
            $isWritable = is_writable($paymentsDir);
            $realPath = $isSymlink ? readlink($paymentsDir) : $paymentsDir;
            
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Payment Receipts Directory',
                'details' => [
                    "Path: $paymentsDir",
                    "Type: " . ($isSymlink ? 'Symlink' : 'Regular Directory'),
                    "Real Path: $realPath",
                    "Writable: " . ($isWritable ? 'YES' : 'NO')
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'Payment Receipts Directory',
                'details' => [
                    "Path: $paymentsDir",
                    "Status: Does not exist - will be created on first upload"
                ]
            ];
        }

        // Check 5: Profile Directory
        $profileDir = $docRoot . '/uploads/profile';
        if (file_exists($profileDir)) {
            $isSymlink = is_link($profileDir);
            $isWritable = is_writable($profileDir);
            $realPath = $isSymlink ? readlink($profileDir) : $profileDir;
            
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Profile Directory',
                'details' => [
                    "Path: $profileDir",
                    "Type: " . ($isSymlink ? 'Symlink' : 'Regular Directory'),
                    "Real Path: $realPath",
                    "Writable: " . ($isWritable ? 'YES' : 'NO')
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'Profile Directory',
                'details' => [
                    "Path: $profileDir",
                    "Status: Does not exist - will be created on first upload"
                ]
            ];
        }

        // Check 6: Branches Directory
        $branchesDir = $docRoot . '/uploads/branches';
        if (file_exists($branchesDir)) {
            $isSymlink = is_link($branchesDir);
            $isWritable = is_writable($branchesDir);
            $realPath = $isSymlink ? readlink($branchesDir) : $branchesDir;
            
            $checks[] = [
                'status' => $isWritable ? 'success' : 'error',
                'title' => 'Branches Directory',
                'details' => [
                    "Path: $branchesDir",
                    "Type: " . ($isSymlink ? 'Symlink' : 'Regular Directory'),
                    "Real Path: $realPath",
                    "Writable: " . ($isWritable ? 'YES' : 'NO')
                ]
            ];
        } else {
            $checks[] = [
                'status' => 'warning',
                'title' => 'Branches Directory',
                'details' => [
                    "Path: $branchesDir",
                    "Status: Does not exist"
                ]
            ];
        }

        // Check 7: PHP Upload Settings
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

        // Display all checks
        foreach ($checks as $check) {
            $icon = $check['status'] === 'success' ? '✅' : 
                   ($check['status'] === 'warning' ? '⚠️' : '❌');
            
            echo "<div class='check-item {$check['status']}'>";
            echo "<div class='check-title'><span class='icon'>$icon</span>{$check['title']}</div>";
            foreach ($check['details'] as $detail) {
                echo "<div class='check-detail'>$detail</div>";
            }
            echo "</div>";
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;">
            <h3 style="margin-top: 0;">📝 Next Steps:</h3>
            <ul style="line-height: 1.8;">
                <li>If all checks show ✅, your setup is ready for file uploads</li>
                <li>If you see ❌ errors, check your Dockerfile startup script</li>
                <li>Payment receipts will be stored in: <code><?php echo $paymentsDir; ?></code></li>
                <li>Files persist across deployments if Railway volume is attached</li>
            </ul>
        </div>
    </div>
</body>
</html>