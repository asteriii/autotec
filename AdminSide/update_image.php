<?php
header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine image type and column name
    $imageType = $_POST['type'] ?? '';
    
    if ($imageType === 'service') {
        // Validate service number
        if (!isset($_POST['service']) || !in_array($_POST['service'], ['1', '2', '3'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid service number']);
            exit;
        }
        $service = $_POST['service'];
        $columnName = 'service' . $service . '_img';
        $filenamePrefix = 'service' . $service . '_';
    } elseif ($imageType === 'announcement') {
        $columnName = 'announcement_img';
        $filenamePrefix = 'announcement_';
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid image type']);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
        exit;
    }

    $file = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/jfif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and JFIF allowed']);
        exit;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']);
        exit;
    }

    // FIXED: Use Railway volume path or fallback to local path
    $baseUploadDir = getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: '/var/www/html';
    $uploadDir = $baseUploadDir . '/uploads/homepage/';
    $uploadDirRelative = 'uploads/homepage/';
    
    // Debug logging
    error_log("=== HOMEPAGE-EDIT.PHP DEBUG ===");
    error_log("BASE_UPLOAD_DIR: " . $baseUploadDir);
    error_log("UPLOAD_DIR (absolute): " . $uploadDir);
    error_log("UPLOAD_DIR_RELATIVE: " . $uploadDirRelative);
    error_log("RAILWAY_VOLUME_MOUNT_PATH: " . (getenv('RAILWAY_VOLUME_MOUNT_PATH') ?: 'NOT SET'));
    error_log("===============================");

    // Create uploads/homepage directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create directory: " . $uploadDir);
            echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
            exit;
        }
        error_log("Created directory: " . $uploadDir);
    }

    // Ensure directory is writable
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0755);
        error_log("Set permissions 0755 on: " . $uploadDir);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $filenamePrefix . uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    error_log("Uploading file to: " . $targetPath);

    // Get old image to delete
    $stmt = $conn->prepare("SELECT $columnName FROM homepage WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $oldImage = $row[$columnName] ?? null;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $targetPath);
        error_log("Last error: " . print_r(error_get_last(), true));
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        exit;
    }

    // Set file permissions
    chmod($targetPath, 0644);
    error_log("File uploaded successfully: " . $targetPath);

    // Update database with just the filename (not full path)
    $stmt = $conn->prepare("UPDATE homepage SET $columnName = ? WHERE id = 1");
    $stmt->bind_param('s', $filename);

    if ($stmt->execute()) {
        // Delete old image if it exists
        if ($oldImage && file_exists($uploadDir . $oldImage)) {
            if (unlink($uploadDir . $oldImage)) {
                error_log("Deleted old image: " . $uploadDir . $oldImage);
            } else {
                error_log("Failed to delete old image: " . $uploadDir . $oldImage);
            }
        }

        error_log("Database updated successfully with filename: " . $filename);
        
        echo json_encode([
            'success' => true,
            'filePath' => $uploadDirRelative . $filename,
            'message' => ucfirst($imageType) . ' image uploaded successfully'
        ]);
    } else {
        // If database update fails, remove the uploaded file
        unlink($targetPath);
        error_log("Database update failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>