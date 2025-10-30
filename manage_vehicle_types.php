<?php
// manage_vehicle_types.php needed to for reservation-edit.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php'; // expects $conn (mysqli)

// Read raw input first (JSON). If that's empty, fall back to $_POST.
$raw = file_get_contents('php://input');
$input = null;
if ($raw) {
    $input = json_decode($raw, true);
    if ($input === null) {
        // not valid JSON — but maybe form-encoded was sent; fall through to $_POST
        $input = null;
    }
}

if ($input === null) {
    // fallback to $_POST normalized
    $input = [];
    foreach ($_POST as $k => $v) $input[$k] = $v;
}

// Normalize action key (allow 'action' or 'Action')
$action = $input['action'] ?? $input['Action'] ?? null;
if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No action provided']);
    exit;
}

try {
    if ($action === 'add') {
        $name = isset($input['name']) ? trim($input['name']) : (isset($input['Name']) ? trim($input['Name']) : '');
        $price = isset($input['price']) ? $input['price'] : (isset($input['Price']) ? $input['Price'] : null);

        if ($name === '' || $price === null || !is_numeric($price)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid name or price']);
            exit;
        }

        $price = number_format((float)$price, 2, '.', '');

        $stmt = $conn->prepare("INSERT INTO vehicle_types (Name, Price) VALUES (?, ?)");
        if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);

        $stmt->bind_param('sd', $name, $price);
        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            throw new Exception('Insert failed: ' . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        $res = $conn->query("SELECT VehicleTypeID, Name, Price FROM vehicle_types WHERE VehicleTypeID = " . (int)$newId);
        $row = $res ? $res->fetch_assoc() : null;

        echo json_encode(['success' => true, 'inserted' => $row]);
        exit;
    }

    elseif ($action === 'delete' || $action === 'remove') {
        // accept either 'id' or 'VehicleTypeID' or 'delete_id'
        $id = $input['id'] ?? $input['VehicleTypeID'] ?? $input['delete_id'] ?? null;
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid id']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM vehicle_types WHERE VehicleTypeID = ?");
        if ($stmt === false) throw new Exception('Prepare failed: ' . $conn->error);

        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            throw new Exception('Delete failed: ' . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            echo json_encode(['success' => true, 'deleted_id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No row deleted (id may not exist)']);
        }
        exit;
    }

    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
