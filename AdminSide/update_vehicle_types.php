<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require 'db.php';
require_once 'audit_trail.php';

// Get session variables
$username = $_SESSION['admin_username'] ?? 'Unknown Admin';

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['updates']) || !is_array($data['updates'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload (expected updates array)']);
    exit;
}

$updates = $data['updates'];
$updatedRows = [];
$auditLogEntries = [];

$conn->begin_transaction();

try {
    $updateStmt = $conn->prepare("UPDATE vehicle_types SET Price = ? WHERE VehicleTypeID = ?");
    $selectStmt = $conn->prepare("SELECT VehicleTypeID, Name, Price FROM vehicle_types WHERE VehicleTypeID = ?");
    $oldPriceStmt = $conn->prepare("SELECT Name, Price FROM vehicle_types WHERE VehicleTypeID = ?");

    foreach ($updates as $u) {
        $id = isset($u['id']) ? (int)$u['id'] : 0;
        $newPrice = isset($u['price']) ? $u['price'] : null;

        if ($id <= 0 || !is_numeric($newPrice)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => 'Invalid id or price for one of the items']);
            exit;
        }

        // Get old price for audit log
        $oldPriceStmt->bind_param('i', $id);
        $oldPriceStmt->execute();
        $oldData = $oldPriceStmt->get_result()->fetch_assoc();
        
        if (!$oldData) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => 'Vehicle type not found: ID ' . $id]);
            exit;
        }

        $priceFormatted = number_format((float)$newPrice, 2, '.', '');
        $oldPriceFormatted = number_format((float)$oldData['Price'], 2, '.', '');

        // Only update and log if price actually changed
        if ($priceFormatted !== $oldPriceFormatted) {
            $updateStmt->bind_param('di', $priceFormatted, $id);
            $updateStmt->execute();

            // Prepare audit log entry
            $auditLogEntries[] = [
                'name' => $oldData['Name'],
                'old_price' => $oldPriceFormatted,
                'new_price' => $priceFormatted
            ];
        }

        $selectStmt->bind_param('i', $id);
        $selectStmt->execute();
        $res = $selectStmt->get_result()->fetch_assoc();
        if ($res) $updatedRows[] = $res;
    }

    $conn->commit();

    // 🧾 Log all price changes to audit trail
    foreach ($auditLogEntries as $entry) {
        logVehiclePrice($username, "{$entry['name']}: ₱{$entry['old_price']} → ₱{$entry['new_price']}");
    }

    echo json_encode(['success' => true, 'updated' => $updatedRows]);
    exit;

} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    
    // Log error
    logAction($username, 'Error', "Failed to update vehicle prices: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>