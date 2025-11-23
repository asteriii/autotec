<?php
require '../db.php'; // adjust if needed

header('Content-Type: application/json');

if (!isset($_POST['completed_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$completedID = intval($_POST['completed_id']);
$status = $_POST['status']; // "Accomplished" or "No Show"

// VALIDATE STATUS
if (!in_array($status, ['Accomplished', 'No Show'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// 1. Fetch completed data
$query = $conn->prepare("SELECT * FROM completed WHERE CompletedID = ?");
$query->bind_param("i", $completedID);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Record not found']);
    exit;
}

// 2. Insert into records table
$insert = $conn->prepare("
    INSERT INTO records (
        CompletedID, ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
        Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address,
        BranchName, PaymentMethod, PaymentStatus, PaymentReceipt,
        Price, ReferenceNumber, RecordStatus
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param("iiissiiisssssssssssdss",
    $data['CompletedID'],
    $data['ReservationID'],
    $data['UserID'],
    $data['PlateNo'],
    $data['Brand'],
    $data['TypeID'],
    $data['CategoryID'],
    $data['Fname'],
    $data['Lname'],
    $data['Mname'],
    $data['PhoneNum'],
    $data['Email'],
    $data['Date'],
    $data['Time'],
    $data['Address'],
    $data['BranchName'],
    $data['PaymentMethod'],
    $data['PaymentStatus'],
    $data['PaymentReceipt'],
    $data['Price'],
    $data['ReferenceNumber'],
    $status
);

if (!$insert->execute()) {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $insert->error]);
    exit;
}

// 3. Delete from completed table
$delete = $conn->prepare("DELETE FROM completed WHERE CompletedID = ?");
$delete->bind_param("i", $completedID);
$delete->execute();

echo json_encode(['success' => true]);
?>