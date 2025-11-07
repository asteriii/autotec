<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, only in JSON

include 'db.php';

// Function to log errors for debugging
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/upload_errors.log');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate service number
        if (!isset($_POST['service']) || !in_array($_POST['service'], ['1', '2', '3'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid service number']);
            exit;
        }

        $service = $_POST['service'];
        $columnName = 'service' . $service . '_img';

        // Debug: Log what we received
        logError("POST data: " . print_r($_POST, true));
        logError("FILES data: " . print_r($_FILES, true));

        // Check if file was uploaded
        if (!isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'error' => 'No file field found in request']);
            exit;
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            
            $errorMsg = $errorMessages[$_FILES['image']['error']] ?? 'Unknown upload error';
            logError("Upload error: " . $errorMsg);
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }

        $file = $_FILES['image'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'image/jfif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
        
        // Get extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes) && !in_array($extension, $allowedExtensions)) {
            logError("Invalid file type: MIME=$mimeType, EXT=$extension");
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and JFIF allowed']);
            exit;
        }

        // Validate file size (max 10MB to be safe)
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB']);
            exit;
        }

        // Determine the correct upload directory path
        // Check if we're in AdminSide already or in root
        if (basename(getcwd()) === 'AdminSide') {
            $uploadDir = __DIR__ . '/uploads/';
        } else {
            $uploadDir = __DIR__ . '/AdminSide/uploads/';
        }

        logError("Upload directory: " . $uploadDir);

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                logError("Failed to create directory: " . $uploadDir);
                echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
                exit;
            }
            logError("Created directory: " . $uploadDir);
        }

        // Ensure directory is writable
        if (!is_writable($uploadDir)) {
            chmod($uploadDir, 0755);
            if (!is_writable($uploadDir)) {
                logError("Directory not writable: " . $uploadDir);
                echo json_encode(['success' => false, 'error' => 'Upload directory is not writable']);
                exit;
            }
        }

        // Generate unique filename
        $filename = 'service' . $service . '_' . uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        logError("Target path: " . $targetPath);

        // Get old image to delete
        $stmt = $conn->prepare("SELECT $columnName FROM homepage WHERE id = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $oldImage = $row[$columnName] ?? null;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            logError("Failed to move file from " . $file['tmp_name'] . " to " . $targetPath);
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file. Check server permissions.']);
            exit;
        }

        logError("File moved successfully to: " . $targetPath);

        // Update database with just the filename (not full path)
        $stmt = $conn->prepare("UPDATE homepage SET $columnName = ? WHERE id = 1");
        $stmt->bind_param('s', $filename);

        if ($stmt->execute()) {
            // Delete old image if it exists
            if ($oldImage && file_exists($uploadDir . $oldImage)) {
                unlink($uploadDir . $oldImage);
                logError("Deleted old image: " . $oldImage);
            }

            logError("Database updated successfully");

            echo json_encode([
                'success' => true,
                'filePath' => 'AdminSide/uploads/' . $filename,
                'message' => 'Image uploaded successfully'
            ]);
        } else {
            // If database update fails, remove the uploaded file
            unlink($targetPath);
            logError("Database update failed: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $conn->error]);
        }

        $stmt->close();
    } catch (Exception $e) {
        logError("Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>