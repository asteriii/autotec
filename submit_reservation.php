<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Define upload directory - Railway compatible
define('UPLOAD_DIR', __DIR__ . '/uploads/payment_receipts/');
define('UPLOAD_DIR_RELATIVE', 'uploads/payment_receipts/');

try {
    // Log incoming request
    error_log("=== NEW RESERVATION SUBMISSION ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Map field names
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

    // Get and sanitize data
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

    error_log("Payment Method: $paymentMethod");

    // Get UserID from session
    $userID = null;
    if (isset($_SESSION['user_id'])) {
        $userID = $_SESSION['user_id'];
    } elseif (isset($_SESSION['UserID'])) {
        $userID = $_SESSION['UserID'];
    }

    error_log("UserID: " . ($userID ?? 'NULL'));

    // Validate formats
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception("Invalid date format");
    }

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        throw new Exception("Invalid time format");
    }

    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        $time .= ':00';
    }

    if (!in_array($paymentMethod, ['gcash', 'onsite'])) {
        throw new Exception("Invalid payment method");
    }

    // Get TypeID
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

    // Check for duplicate reservation
    $check_stmt = $conn->prepare("SELECT ReservationID FROM reservations WHERE PlateNo = ? AND Date = ?");
    $check_stmt->bind_param("ss", $plateNo, $date);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $check_stmt->close();
        throw new Exception("A reservation already exists for this plate number on the selected date");
    }
    $check_stmt->close();

    // Check slot availability
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

    // Handle GCash payment receipt upload
    $paymentReceiptPath = null;
    
    if ($paymentMethod === 'gcash') {
        error_log("Processing GCash payment...");
        
        if (!isset($_FILES['paymentReceipt']) || $_FILES['paymentReceipt']['error'] === UPLOAD_ERR_NO_FILE) {
            error_log("ERROR: GCash payment but no receipt uploaded");
            throw new Exception('Payment receipt is required for GCash payment.');
        }
        
        $file = $_FILES['paymentReceipt'];
        error_log("File uploaded: " . $file['name'] . " (Size: " . $file['size'] . " bytes)");
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds maximum size',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form size',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            $errorMessage = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Unknown upload error';
            error_log("Upload error: " . $errorMessage);
            throw new Exception('Upload error: ' . $errorMessage);
        }
        
        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        error_log("Detected MIME type: $detectedMimeType, Extension: $fileExtension");
        
        if (!in_array($detectedMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Please upload an image (JPEG, PNG, GIF, or WebP).');
        }
        
        // Check file size (5MB max)
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Verify it's an actual image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }
        
        // Create directory if needed
        if (!file_exists(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                error_log("FAILED to create directory: " . UPLOAD_DIR);
                throw new Exception('Failed to create upload directory.');
            }
            error_log("Created directory: " . UPLOAD_DIR);
        }
        
        if (!is_writable(UPLOAD_DIR)) {
            error_log("Directory NOT writable: " . UPLOAD_DIR);
            throw new Exception('Upload directory is not writable.');
        }
        
        // Generate unique filename
        $sanitizedPlateNo = preg_replace('/[^a-zA-Z0-9]/', '_', $plateNo);
        $timestamp = time();
        $uniqueId = uniqid();
        $fileName = "gcash_{$sanitizedPlateNo}_{$timestamp}_{$uniqueId}.{$fileExtension}";
        $uploadPath = UPLOAD_DIR . $fileName;
        
        error_log("Attempting to save to: " . $uploadPath);
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("FAILED to move file to: " . $uploadPath);
            throw new Exception('Failed to upload payment receipt.');
        }
        
        // Set relative path for database
        $paymentReceiptPath = UPLOAD_DIR_RELATIVE . $fileName;
        
        // Set permissions
        chmod($uploadPath, 0644);
        
        error_log("SUCCESS! Payment receipt uploaded: " . $paymentReceiptPath);
        error_log("Full path: " . $uploadPath);
        error_log("File exists after upload: " . (file_exists($uploadPath) ? 'YES' : 'NO'));
    } else {
        error_log("Payment method is onsite - no receipt needed");
    }

    // Set payment status
    $paymentStatus = 'pending';

    // Generate reference number
    $referenceNumber = 'AT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    error_log("About to insert into database...");
    error_log("PaymentReceipt value: " . ($paymentReceiptPath ?? 'NULL'));

    // Insert reservation - NOTE: PaymentReceipt can be NULL for onsite payments
    $stmt = $conn->prepare("INSERT INTO reservations (UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    // Bind parameters - IMPORTANT: Use correct types
    // i=integer, s=string, d=double
    $stmt->bind_param("issiisssssssssssdss", 
        $userID,              // i - integer
        $plateNo,             // s - string
        $brand,               // s - string
        $typeID,              // i - integer
        $categoryID,          // i - integer
        $firstName,           // s - string
        $lastName,            // s - string
        $middleName,          // s - string
        $contactNumber,       // s - string
        $email,               // s - string
        $date,                // s - string
        $time,                // s - string
        $address,             // s - string
        $branchName,          // s - string
        $paymentMethod,       // s - string
        $paymentStatus,       // s - string
        $paymentReceiptPath,  // s - string (can be NULL)
        $price,               // d - double
        $referenceNumber      // s - string
    );

    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        $remaining_slots = $max_slots - ($current_bookings + 1);
        
        error_log("SUCCESS! Reservation created with ID: $reservation_id");
        error_log("PaymentReceipt saved in DB: " . ($paymentReceiptPath ?? 'NULL'));
        
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
        error_log("Execute failed: " . $stmt->error);
        
        // If database insert fails and file was uploaded, delete it
        if ($paymentReceiptPath && file_exists(UPLOAD_DIR . basename($paymentReceiptPath))) {
            unlink(UPLOAD_DIR . basename($paymentReceiptPath));
            error_log("Cleaned up uploaded file due to DB error");
        }
        throw new Exception("Error creating reservation: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("RESERVATION ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
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