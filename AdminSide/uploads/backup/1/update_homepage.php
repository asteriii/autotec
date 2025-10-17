<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $header = $_POST['header'] ?? '';
    $operate = $_POST['operate'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';

    $stmt = $conn->prepare("UPDATE homepage SET title=?, header=?, operate=?, location=?, contact=? WHERE id=1");
    $stmt->bind_param("sssss", $title, $header, $operate, $location, $contact);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}
echo json_encode(["success" => false, "error" => "Invalid request"]);
