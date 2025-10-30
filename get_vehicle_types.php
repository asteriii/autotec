<?php
// get_vehicle_types.php needed to for reservation-edit.php
header('Content-Type: application/json');
require 'db.php';

$result = $conn->query("SELECT * FROM vehicle_types");

$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $vehicles
]);

$conn->close();

// ✅ This is enough — no need for ob_end_flush() or flush()
exit;
