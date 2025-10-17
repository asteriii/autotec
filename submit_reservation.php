<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autotec";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
    exit;
}

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
        'scheduleDate' => 'date',  // JavaScript sends 'scheduleDate', PHP expects 'date'
        'scheduleTime' => 'time'   // JavaScript sends 'scheduleTime', PHP expects 'time'
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
    $required_fields = ['plateNumber', 'brand', 'vehicleType', 'vehicleCategory', 'firstName', 'lastName', 'contactNumber', 'email', 'date', 'time', 'address'];
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

    // Get UserID from session (assuming user is logged in)
    session_start();
    $userID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

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

    // Check if there's already a booking for the same date and time (prevent double booking)
    $time_check_stmt = $conn->prepare("SELECT ReservationID FROM reservations WHERE Date = ? AND Time = ?");
    $time_check_stmt->bind_param("ss", $date, $time);
    $time_check_stmt->execute();
    $time_result = $time_check_stmt->get_result();

    if ($time_result->num_rows > 0) {
        $time_check_stmt->close();
        throw new Exception("This time slot is already booked for the selected date. Please choose a different time.");
    }
    $time_check_stmt->close();

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

    // Prepare and bind - updated to include BranchName
    $stmt = $conn->prepare("INSERT INTO reservations (UserID, PlateNo, Brand, TypeID, CategoryID, Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiisssssssss", $userID, $plateNo, $brand, $typeID, $categoryID, $firstName, $lastName, $middleName, $contactNumber, $email, $date, $time, $address, $branchName);

    // Execute the statement
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        
        // Generate reference number based on reservation ID
        $referenceNumber = 'ATE-' . date('Ymd') . '-' . str_pad($reservation_id, 4, '0', STR_PAD_LEFT);
        
        // Success response in JSON format
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'referenceNumber' => $referenceNumber,
            'reservationId' => $reservation_id
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
?>