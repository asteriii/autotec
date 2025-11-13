<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');
include 'db.php';
require_once 'audit_trail.php';

// Get session variables
$username = $_SESSION['admin_username'] ?? 'Unknown Admin';

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
        $serviceName = 'Service ' . $service;
    } elseif ($imageType === 'announcement') {
        $columnName = 'announcement_img';
        $filenamePrefix = 'announcement_';
        $serviceName = 'Announcement';
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
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/jfif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, JFIF, and WebP allowed']);
        exit;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']);
        exit;
    }

    try {
        // FIXED: Direct path to uploads directory (Railway volume is mounted here)
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/homepage/';
        $uploadDirRelative = 'uploads/homepage/';
        
        // Debug logging
        error_log("=== UPDATE_IMAGE.PHP DEBUG ===");
        error_log("DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT']);
        error_log("UPLOAD_DIR (absolute): " . $uploadDir);
        error_log("UPLOAD_DIR_RELATIVE: " . $uploadDirRelative);
        error_log("Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO'));
        error_log("Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));
        error_log("==============================");

        // Create uploads/homepage directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("Failed to create directory: " . $uploadDir);
                throw new Exception('Failed to create upload directory');
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
            throw new Exception('Failed to move uploaded file. Check permissions.');
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
            
            // 🧾 Log the audit trail
            if ($imageType === 'service') {
                logServiceImage($username, $serviceName);
            } elseif ($imageType === 'announcement') {
                logAnnouncementImage($username);
            }
            
            echo json_encode([
                'success' => true,
                'filePath' => $uploadDirRelative . $filename,
                'message' => ucfirst($imageType) . ' image uploaded successfully'
            ]);
        } else {
            // If database update fails, remove the uploaded file
            unlink($targetPath);
            error_log("Database update failed: " . $conn->error);
            throw new Exception('Database update failed: ' . $conn->error);
        }

        $stmt->close();
        
    } catch (Exception $e) {
        // Log error
        logAction($username, 'Error', "Failed to update homepage image ($imageType): " . $e->getMessage());
        
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>