<?php
// update_vehicle_types.php needed to sa reservation-edit.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['updates']) || !is_array($data['updates'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload (expected updates array)']);
    exit;
}

$updates = $data['updates'];
$updatedRows = [];

$conn->begin_transaction();

try {
    $updateStmt = $conn->prepare("UPDATE vehicle_types SET Price = ? WHERE VehicleTypeID = ?");
    $selectStmt = $conn->prepare("SELECT VehicleTypeID, Name, Price FROM vehicle_types WHERE VehicleTypeID = ?");

    foreach ($updates as $u) {
        $id = isset($u['id']) ? (int)$u['id'] : 0;
        $price = isset($u['price']) ? $u['price'] : null;

        if ($id <= 0 || !is_numeric($price)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => 'Invalid id or price for one of the items']);
            exit;
        }

        $priceFormatted = number_format((float)$price, 2, '.', '');

        $updateStmt->bind_param('di', $priceFormatted, $id);
        $updateStmt->execute();

        $selectStmt->bind_param('i', $id);
        $selectStmt->execute();
        $res = $selectStmt->get_result()->fetch_assoc();
        if ($res) $updatedRows[] = $res;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'updated' => $updatedRows]);
    exit;

} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
