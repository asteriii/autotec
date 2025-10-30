<?php
header('Content-Type: application/json');

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
    
    // Debug: Log session info (remove this after fixing)
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("UserID value: " . ($userID ?? 'NULL'));

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

    // FIXED: Check slot availability - allow up to 3 bookings per time slot per branch
    $max_slots = 3; // Maximum bookings per time slot (3 machines available)
    
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

    // Handle payment receipt upload for GCash
    $paymentReceiptPath = null;
    if ($paymentMethod === 'gcash' && isset($_FILES['paymentReceipt'])) {
        $file = $_FILES['paymentReceipt'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = strtolower($file['type']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Please upload an image (JPEG, PNG, or GIF).');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = 'uploads/payment_receipts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload payment receipt. Please try again.');
        }
        
        $paymentReceiptPath = $uploadPath;
    } elseif ($paymentMethod === 'gcash' && !isset($_FILES['paymentReceipt'])) {
        throw new Exception('Payment receipt is required for GCash payment.');
    }

    // Set payment status based on payment method
    $paymentStatus = ($paymentMethod === 'gcash') ? 'paid' : 'pending';

    // Generate reference number
    $referenceNumber = 'AT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Prepare and bind - updated to include payment fields
    $stmt = $conn->prepare("INSERT INTO reservations (UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiissssssssssssds", $userID, $plateNo, $brand, $typeID, $categoryID, $firstName, $lastName, $middleName, $contactNumber, $email, $date, $time, $address, $branchName, $paymentMethod, $paymentStatus, $paymentReceiptPath, $price, $referenceNumber);

    // Execute the statement
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        // Calculate remaining slots
        $remaining_slots = $max_slots - ($current_bookings + 1);
        
        // Success response in JSON format
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'referenceNumber' => $referenceNumber,
            'reservationId' => $reservation_id,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $paymentStatus,
            'slotsRemaining' => $remaining_slots
        ]);
    } else {
        throw new Exception("Error creating reservation: " . $stmt->error);
    }

    // Close connections
    $stmt->close();

} catch (Exception $e) {
    // Error response in JSON format
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}

// Optional: Email confirmation function
function sendConfirmationEmail($email, $refNumber, $firstName, $lastName, $date, $time, $branch) {
    $to = $email;
    $subject = "AutoTEC Appointment Confirmation - " . $refNumber;
    
    $message = "
    <html>
    <head>
        <title>Appointment Confirmation</title>
    </head>
    <body>
        <h2>Thank you for your reservation!</h2>
        <p>Dear $firstName $lastName,</p>
        <p>Your appointment has been successfully scheduled.</p>
        
        <h3>Appointment Details:</h3>
        <ul>
            <li><strong>Reference Number:</strong> $refNumber</li>
            <li><strong>Branch:</strong> $branch</li>
            <li><strong>Date:</strong> $date</li>
            <li><strong>Time:</strong> $time</li>
        </ul>
        
        <p>Please bring this reference number and your vehicle documents on your appointment date.</p>
        
        <p>Best regards,<br>AutoTEC Team</p>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@autotec.com" . "\r\n";
    
    // Send email
    mail($to, $subject, $message, $headers);
}
?>