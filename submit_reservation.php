<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log to a file for Railway debugging
$logFile = __DIR__ . '/upload_debug.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    error_log($message);
}

require_once 'db.php';

try {
    writeLog("=== NEW RESERVATION SUBMISSION ===");
    writeLog("POST data: " . print_r($_POST, true));
    writeLog("FILES data: " . print_r($_FILES, true));

    // Map the field names from JavaScript to PHP expected names
    $field_mapping = [
        'plateNumber' => 'plateNumber',
        'brand' => 'brand', 
        'vehicleType' => 'vehicleType',
        'vehicleCategory' => 'vehicleCategory',
        'firstName' => 'firstName',
        'lastName' => 'lastName',
        'middleName' => 'middleName',
        'contactNumber' => 'contactNumber',
        'email' => 'email',
        'address' => 'address',
        'scheduleDate' => 'date',
        'scheduleTime' => 'time'
    ];

    $mapped_post = [];
    foreach ($field_mapping as $js_field => $php_field) {
        if (isset($_POST[$js_field])) {
            $mapped_post[$php_field] = $_POST[$js_field];
        }
    }

    $_POST = array_merge($_POST, $mapped_post);

    // Validate required fields
    $required_fields = ['plateNumber', 'brand', 'vehicleType', 'vehicleCategory', 'firstName', 'lastName', 'contactNumber', 'email', 'date', 'time', 'address', 'paymentMethod'];
    $missing_fields = [];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Get and sanitize form data
    $plateNo = trim($_POST['plateNumber']);
    $brand = trim($_POST['brand']);
    $vehicleType = trim($_POST['vehicleType']);
    $vehicleCategory = trim($_POST['vehicleCategory']);
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $middleName = isset($_POST['middleName']) ? trim($_POST['middleName']) : '';
    $contactNumber = trim($_POST['contactNumber']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
    $paymentMethod = strtolower(trim($_POST['paymentMethod']));
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;

    // Get UserID from session
    $userID = null;
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
    } elseif (isset($_SESSION['UserID'])) {
        $userID = $_SESSION['UserID'];
    } elseif (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
    }
    
    writeLog("UserID: " . ($userID ?? 'NULL'));
    writeLog("Payment Method (before normalization): " . $paymentMethod);

    // Normalize payment method for ENUM compatibility
    if ($paymentMethod === 'cash') {
        $paymentMethod = 'onsite';
        writeLog("Payment method normalized from 'cash' to 'onsite'");
    }

    writeLog("Payment Method (after normalization): " . $paymentMethod);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Invalid date format");
    }

    // Validate time format and add seconds if needed
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        throw new Exception("Invalid time format");
    }
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        $time .= ':00';
    }

    // Validate payment method
    if (!in_array($paymentMethod, ['gcash', 'onsite'])) {
        throw new Exception("Invalid payment method: " . $paymentMethod);
    }

    // Get TypeID from vehicle_types table
    $type_stmt = $conn->prepare("SELECT VehicleTypeID FROM vehicle_types WHERE Name = ?");
    $type_stmt->bind_param("s", $vehicleType);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();

    if ($type_result->num_rows == 0) {
        $type_stmt->close();
        throw new Exception("Invalid vehicle type: " . $vehicleType);
    }

    $type_row = $type_result->fetch_assoc();
    $typeID = $type_row['VehicleTypeID'];
    $type_stmt->close();

    // Get CategoryID
    $category_stmt = $conn->prepare("SELECT CategoryID FROM vehicle_categories WHERE Name = ?");
    $category_stmt->bind_param("s", $vehicleCategory);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();

    if ($category_result->num_rows == 0) {
        $category_stmt->close();
        throw new Exception("Invalid vehicle category: " . $vehicleCategory);
    }

    $category_row = $category_result->fetch_assoc();
    $categoryID = $category_row['CategoryID'];
    $category_stmt->close();

    // Get BranchName
    $branchName = null;
    if (isset($_POST['branchId']) && !empty($_POST['branchId'])) {
        $branch_stmt = $conn->prepare("SELECT BranchName FROM about_us WHERE AboutID = ?");
        $branch_stmt->bind_param("i", $_POST['branchId']);
        $branch_stmt->execute();
        $branch_result = $branch_stmt->get_result();
        
        if ($branch_result->num_rows > 0) {
            $branch_row = $branch_result->fetch_assoc();
            $branchName = $branch_row['BranchName'];
        }
        $branch_stmt->close();
    }

    // Check if plate number already exists for the same date
    $check_stmt = $conn->prepare("SELECT ReservationID FROM reservations WHERE PlateNo = ? AND Date = ?");
    $check_stmt->bind_param("ss", $plateNo, $date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $check_stmt->close();
        throw new Exception("A reservation already exists for this plate number on the selected date");
    }
    $check_stmt->close();

    // Check slot availability (max 3 per slot)
    $max_slots = 3;
    $slot_check_stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM reservations WHERE Date = ? AND Time = ? AND BranchName = ?");
    $slot_check_stmt->bind_param("sss", $date, $time, $branchName);
    $slot_check_stmt->execute();
    $slot_result = $slot_check_stmt->get_result();
    $slot_row = $slot_result->fetch_assoc();
    $current_bookings = intval($slot_row['booking_count']);
    $slot_check_stmt->close();

    if ($current_bookings >= $max_slots) {
        throw new Exception("This time slot is fully booked (" . $current_bookings . "/" . $max_slots . " slots taken). Please choose a different time.");
    }

    // ===== RAILWAY-COMPATIBLE FILE UPLOAD =====
    $paymentReceiptPath = null;
    
    if ($paymentMethod === 'gcash') {
        writeLog("Processing GCash payment receipt upload");
        
        if (!isset($_FILES['paymentReceipt']) || $_FILES['paymentReceipt']['error'] === UPLOAD_ERR_NO_FILE) {
            writeLog("ERROR: No file uploaded for GCash payment");
            throw new Exception('Payment receipt is required for GCash payment.');
        }
        
        $file = $_FILES['paymentReceipt'];
        
        writeLog("File details: name={$file['name']}, type={$file['type']}, size={$file['size']}, error={$file['error']}");
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
            ];
            $errorMessage = $uploadErrors[$file['error']] ?? 'Unknown upload error';
            writeLog("Upload error: " . $errorMessage);
            throw new Exception('Upload error: ' . $errorMessage);
        }
        
        // Validate file type using finfo (most reliable)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        writeLog("Detected MIME: {$detectedMimeType}, Extension: {$fileExtension}");
        
        if (!in_array($detectedMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Please upload JPG, PNG, GIF, or WebP image.');
        }
        
        // Check file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }
        
        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }
        
        writeLog("Image validated: {$imageInfo[0]}x{$imageInfo[1]}px");
        
        // ===== RAILWAY SYMLINK-COMPATIBLE UPLOAD DIRECTORY =====
        $uploadDir = 'uploads/payment_receipts/';
        $fullUploadDir = __DIR__ . '/' . $uploadDir;
        
        writeLog("Upload directory path: {$fullUploadDir}");
        writeLog("Current directory: " . __DIR__);
        writeLog("Document root: " . $_SERVER['DOCUMENT_ROOT']);
        
        // Check if directory exists (including symlink)
        if (!file_exists($fullUploadDir)) {
            writeLog("Directory doesn't exist, attempting to create...");
            if (!mkdir($fullUploadDir, 0777, true)) {
                writeLog("FAILED to create directory");
                throw new Exception('Upload directory does not exist and could not be created.');
            }
            writeLog("Directory created successfully");
        }
        
        // Check if it's a symlink (Railway setup)
        if (is_link($fullUploadDir)) {
            $symlinkTarget = readlink($fullUploadDir);
            writeLog("Directory is a SYMLINK pointing to: {$symlinkTarget}");
            
            // Verify the symlink target exists and is writable
            if (!file_exists($symlinkTarget)) {
                writeLog("ERROR: Symlink target does not exist: {$symlinkTarget}");
                throw new Exception('Upload storage volume not properly mounted.');
            }
            
            if (!is_writable($symlinkTarget)) {
                writeLog("ERROR: Symlink target not writable: {$symlinkTarget}");
                throw new Exception('Upload storage volume not writable.');
            }
            
            writeLog("Symlink target is valid and writable");
        } else {
            writeLog("Directory is a regular directory (not symlink)");
        }
        
        // Verify directory is writable
        if (!is_writable($fullUploadDir)) {
            writeLog("ERROR: Directory not writable: {$fullUploadDir}");
            // Try to fix permissions
            @chmod($fullUploadDir, 0777);
            if (!is_writable($fullUploadDir)) {
                throw new Exception('Upload directory is not writable.');
            }
            writeLog("Fixed directory permissions");
        }
        
        // Generate unique filename
        $sanitizedPlateNo = preg_replace('/[^a-zA-Z0-9]/', '_', $plateNo);
        $timestamp = time();
        $uniqueId = uniqid();
        $fileName = "gcash_{$sanitizedPlateNo}_{$timestamp}_{$uniqueId}.{$fileExtension}";
        
        $uploadPath = $fullUploadDir . $fileName;
        $paymentReceiptPath = $uploadDir . $fileName; // Path for database (relative)
        
        writeLog("Target upload path: {$uploadPath}");
        writeLog("Database path: {$paymentReceiptPath}");
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            writeLog("CRITICAL: Failed to move uploaded file");
            writeLog("Source: {$file['tmp_name']} (exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . ")");
            writeLog("Destination: {$uploadPath}");
            writeLog("Destination dir exists: " . (file_exists($fullUploadDir) ? 'YES' : 'NO'));
            writeLog("Destination dir writable: " . (is_writable($fullUploadDir) ? 'YES' : 'NO'));
            throw new Exception('Failed to save payment receipt. Please try again.');
        }
        
        // Set file permissions
        @chmod($uploadPath, 0644);
        
        // Verify file was saved
        if (file_exists($uploadPath)) {
            $savedFileSize = filesize($uploadPath);
            writeLog("✓ SUCCESS: File saved successfully");
            writeLog("  Full path: {$uploadPath}");
            writeLog("  DB path: {$paymentReceiptPath}");
            writeLog("  Size: {$savedFileSize} bytes");
            writeLog("  Readable: " . (is_readable($uploadPath) ? 'YES' : 'NO'));
        } else {
            writeLog("✗ CRITICAL: File does not exist after move_uploaded_file!");
            throw new Exception('Failed to save payment receipt.');
        }
    } else {
        writeLog("Payment method is onsite - no receipt required");
    }

    // Set payment status
    $paymentStatus = 'pending';

    // Generate reference number
    $referenceNumber = 'AT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    writeLog("Preparing database insert:");
    writeLog("  Reference: {$referenceNumber}");
    writeLog("  PaymentReceipt: " . ($paymentReceiptPath ?? 'NULL'));
    writeLog("  PaymentReceipt Type: " . gettype($paymentReceiptPath));
    writeLog("  PaymentMethod: {$paymentMethod}");
    writeLog("  PaymentStatus: {$paymentStatus}");

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO reservations (UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    writeLog("=== BINDING PARAMETERS ===");
    writeLog("Parameter 17 (PaymentReceipt): '" . ($paymentReceiptPath ?? 'NULL') . "' (Type: " . gettype($paymentReceiptPath) . ")");
    
    $stmt->bind_param("issiisssssssssssdss", 
        $userID, 
        $plateNo, 
        $brand, 
        $typeID, 
        $categoryID, 
        $firstName, 
        $lastName, 
        $middleName, 
        $contactNumber, 
        $email, 
        $date, 
        $time, 
        $address, 
        $branchName, 
        $paymentMethod, 
        $paymentStatus, 
        $paymentReceiptPath, 
        $price, 
        $referenceNumber
    );

    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        $remaining_slots = $max_slots - ($current_bookings + 1);
        
        writeLog("✓ Database INSERT successful");
        writeLog("  ReservationID: {$reservation_id}");
        
        // === VERIFY WHAT WAS ACTUALLY SAVED ===
        $verify_stmt = $conn->prepare("SELECT PaymentReceipt, PaymentMethod, PaymentStatus FROM reservations WHERE ReservationID = ?");
        $verify_stmt->bind_param("i", $reservation_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $saved_data = $verify_result->fetch_assoc();
        $verify_stmt->close();
        
        writeLog("=== POST-INSERT VERIFICATION ===");
        writeLog("What was ACTUALLY saved in database:");
        writeLog("  PaymentReceipt from DB: '" . ($saved_data['PaymentReceipt'] ?? 'NULL') . "'");
        writeLog("  PaymentMethod from DB: '" . ($saved_data['PaymentMethod'] ?? 'NULL') . "'");
        writeLog("  PaymentStatus from DB: '" . ($saved_data['PaymentStatus'] ?? 'NULL') . "'");
        writeLog("================================");
        
        writeLog("✓ Reservation created successfully");
        writeLog("  Reference: {$referenceNumber}");
        writeLog("  Slots remaining: {$remaining_slots}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully',
            'referenceNumber' => $referenceNumber,
            'reservationId' => $reservation_id,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $paymentStatus,
            'paymentReceipt' => $paymentReceiptPath,
            'slotsRemaining' => $remaining_slots
        ]);
        
    } else {
        writeLog("✗ Database insert failed: " . $stmt->error);
        
        // Delete uploaded file if database insert fails
        if ($paymentReceiptPath && file_exists($uploadPath)) {
            @unlink($uploadPath);
            writeLog("Deleted uploaded file due to database error");
        }
        
        throw new Exception("Database error: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    writeLog("=== ERROR ===");
    writeLog("Message: " . $e->getMessage());
    writeLog("File: " . $e->getFile());
    writeLog("Line: " . $e->getLine());
    writeLog("Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>