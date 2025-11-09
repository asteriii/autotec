<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

try {
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

    // Remap the POST data
    $mapped_post = [];
    foreach ($field_mapping as $js_field => $php_field) {
        if (isset($_POST[$js_field])) {
            $mapped_post[$php_field] = $_POST[$js_field];
        }
    }

    // Replace $_POST with mapped data for validation
    $_POST = array_merge($_POST, $mapped_post);

    // Validate that required fields are present
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

    // Get data from the form and sanitize
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
    $paymentMethod = trim($_POST['paymentMethod']);
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;

    // Get UserID from session - CHECK BOTH POSSIBLE SESSION NAMES
    $userID = null;
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
    } elseif (isset($_SESSION['UserID'])) {
        $userID = $_SESSION['UserID'];
    } elseif (isset($_SESSION['userid'])) {
        $userID = $_SESSION['userid'];
    }
    
    // Debug: Log session info
    error_log("=== RESERVATION SUBMISSION DEBUG ===");
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("UserID value: " . ($userID ?? 'NULL'));
    error_log("Payment Method: " . $paymentMethod);
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Invalid date format");
    }

    // Validate time format (HH:MM or HH:MM:SS)
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        throw new Exception("Invalid time format");
    }

    // Add seconds if not provided
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        $time .= ':00';
    }

    // Validate payment method
    if (!in_array($paymentMethod, ['gcash', 'onsite'])) {
        throw new Exception("Invalid payment method");
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

    // Get CategoryID from vehicle_categories table
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

    // Set BranchName if branchId is provided
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

    // Check slot availability - allow up to 3 bookings per time slot per branch
    $max_slots = 3;
    
    $slot_check_stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM reservations WHERE Date = ? AND Time = ? AND BranchName = ?");
    $slot_check_stmt->bind_param("sss", $date, $time, $branchName);
    $slot_check_stmt->execute();
    $slot_result = $slot_check_stmt->get_result();
    $slot_row = $slot_result->fetch_assoc();
    $current_bookings = intval($slot_row['booking_count']);
    $slot_check_stmt->close();

    // Check if slot is full
    if ($current_bookings >= $max_slots) {
        throw new Exception("This time slot is fully booked (" . $current_bookings . "/" . $max_slots . " slots taken). Please choose a different time.");
    }

    // ===== IMPROVED GCASH PAYMENT RECEIPT UPLOAD =====
    $paymentReceiptPath = null;
    
    if ($paymentMethod === 'gcash') {
        error_log("Processing GCash payment - checking for receipt file");
        
        // Check if file was uploaded
        if (!isset($_FILES['paymentReceipt']) || $_FILES['paymentReceipt']['error'] === UPLOAD_ERR_NO_FILE) {
            error_log("GCash payment but no receipt file uploaded");
            error_log("FILES array: " . print_r($_FILES, true));
            throw new Exception('Payment receipt is required for GCash payment.');
        }
        
        $file = $_FILES['paymentReceipt'];
        
        error_log("Receipt file details:");
        error_log("- Name: " . $file['name']);
        error_log("- Type: " . $file['type']);
        error_log("- Size: " . $file['size']);
        error_log("- Tmp: " . $file['tmp_name']);
        error_log("- Error: " . $file['error']);
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $errorMessage = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Unknown upload error';
            error_log("Upload error: " . $errorMessage);
            throw new Exception('Upload error: ' . $errorMessage);
        }
        
        // Validate file type using both MIME type and extension
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $fileMimeType = strtolower($file['type']);
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        error_log("File MIME type: " . $fileMimeType);
        error_log("File extension: " . $fileExtension);
        
        // Verify file type using finfo (more reliable than $_FILES['type'])
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        error_log("Detected MIME type: " . $detectedMimeType);
        
        if (!in_array($detectedMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Please upload an image (JPEG, PNG, GIF, or WebP).');
        }
        
        // Check file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxFileSize) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Check if file is actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }
        
        error_log("Image validation passed - Width: " . $imageInfo[0] . ", Height: " . $imageInfo[1]);
        
        // Define upload directory (this will work with your Railway symlinks)
        $uploadDir = 'uploads/payment_receipts/';
        $fullUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir;
        
        error_log("Upload directory paths:");
        error_log("- Relative: " . $uploadDir);
        error_log("- Full: " . $fullUploadDir);
        error_log("- Document root: " . $_SERVER['DOCUMENT_ROOT']);
        
        // Create upload directory if it doesn't exist
        if (!file_exists($fullUploadDir)) {
            error_log("Creating upload directory...");
            if (!mkdir($fullUploadDir, 0755, true)) {
                error_log("FAILED to create upload directory: " . $fullUploadDir);
                throw new Exception('Failed to create upload directory. Please contact support.');
            }
            error_log("Successfully created upload directory");
        } else {
            error_log("Upload directory already exists");
        }
        
        // Verify directory is writable
        if (!is_writable($fullUploadDir)) {
            error_log("ERROR: Upload directory is not writable: " . $fullUploadDir);
            // Try to fix permissions
            @chmod($fullUploadDir, 0755);
            if (!is_writable($fullUploadDir)) {
                throw new Exception('Upload directory is not writable. Please contact support.');
            }
            error_log("Fixed permissions on upload directory");
        }
        
        // Generate unique filename with sanitized plate number
        $sanitizedPlateNo = preg_replace('/[^a-zA-Z0-9]/', '_', $plateNo);
        $timestamp = time();
        $uniqueId = uniqid();
        $fileName = "gcash_{$sanitizedPlateNo}_{$timestamp}_{$uniqueId}.{$fileExtension}";
        $uploadPath = $fullUploadDir . $fileName;
        
        error_log("Target upload path: " . $uploadPath);
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("FAILED to move uploaded file from {$file['tmp_name']} to {$uploadPath}");
            error_log("Possible reasons:");
            error_log("- Tmp file exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
            error_log("- Target dir writable: " . (is_writable($fullUploadDir) ? 'YES' : 'NO'));
            error_log("- Target dir exists: " . (file_exists($fullUploadDir) ? 'YES' : 'NO'));
            throw new Exception('Failed to upload payment receipt. Please try again.');
        }
        
        // Store relative path for database (relative to document root)
        $paymentReceiptPath = $uploadDir . $fileName;
        
        // Set correct permissions
        chmod($uploadPath, 0644);
        
        // Verify file was actually saved
        if (file_exists($uploadPath)) {
            $savedFileSize = filesize($uploadPath);
            error_log("=== PAYMENT RECEIPT UPLOAD SUCCESS ===");
            error_log("File saved to: " . $uploadPath);
            error_log("Database path: " . $paymentReceiptPath);
            error_log("File size: " . $savedFileSize . " bytes");
            error_log("File readable: " . (is_readable($uploadPath) ? 'YES' : 'NO'));
            error_log("=====================================");
        } else {
            error_log("ERROR: File was not saved at: " . $uploadPath);
            throw new Exception('Failed to save payment receipt.');
        }
    } else {
        error_log("Payment method is onsite - no receipt required");
    }

    // Set payment status - both gcash and onsite start as 'pending'
    $paymentStatus = 'pending';

    // Generate reference number
    $referenceNumber = 'AT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    error_log("=== INSERTING INTO DATABASE ===");
    error_log("PaymentReceipt value: " . ($paymentReceiptPath ?? 'NULL'));
    error_log("Reference Number: " . $referenceNumber);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO reservations (UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiisssssssssssdss", $userID, $plateNo, $brand, $typeID, $categoryID, $firstName, $lastName, $middleName, $contactNumber, $email, $date, $time, $address, $branchName, $paymentMethod, $paymentStatus, $paymentReceiptPath, $price, $referenceNumber);

    // Execute the statement
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        // Calculate remaining slots
        $remaining_slots = $max_slots - ($current_bookings + 1);
        
        error_log("=== RESERVATION CREATED SUCCESSFULLY ===");
        error_log("Reservation ID: " . $reservation_id);
        error_log("Payment Receipt Path in DB: " . ($paymentReceiptPath ?? 'NULL'));
        error_log("========================================");
        
        // Success response
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
        error_log("Database insert failed: " . $stmt->error);
        // If database insert fails and file was uploaded, delete the uploaded file
        if ($paymentReceiptPath && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $paymentReceiptPath)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $paymentReceiptPath);
            error_log("Deleted uploaded file due to database error");
        }
        throw new Exception("Error creating reservation: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    // Log the error
    error_log("=== RESERVATION ERROR ===");
    error_log("Error message: " . $e->getMessage());
    error_log("Error file: " . $e->getFile());
    error_log("Error line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("========================");
    
    // Error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>