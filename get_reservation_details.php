<?php
header('Content-Type: application/json');

include 'db.php';

// Validate input
if (!isset($_POST['reservation_id']) || empty($_POST['reservation_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Reservation ID is required'
    ]);
    exit;
}

$reservation_id = intval($_POST['reservation_id']);

// Prepare SQL query with JOINs to get all related data
$sql = "SELECT 
    r.ReservationID,
    r.UserID,
    r.PlateNo,
    r.Brand,
    r.Fname,
    r.Lname,
    r.Mname,
    r.PhoneNum,
    r.Email,
    r.Date,
    r.Time,
    r.Address,
    vt.Name as VehicleTypeName,
    vt.Price,
    vc.Name as CategoryName
FROM reservations r
LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID
LEFT JOIN vehicle_categories vc ON r.CategoryID = vc.CategoryID
WHERE r.ReservationID = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to prepare statement: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Reservation not found'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$reservation_data = $result->fetch_assoc();

// Return the data as JSON
echo json_encode([
    'success' => true,
    'data' => $reservation_data
]);

$stmt->close();
$conn->close();
?>