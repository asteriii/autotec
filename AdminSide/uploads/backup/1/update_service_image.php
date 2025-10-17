<!-- should be in the same folder as homepage-edit.php -->
<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image']) || !isset($_POST['service'])) {
        echo json_encode(['success' => false, 'error' => 'Missing image or service number']);
        exit;
    }

    $service = intval($_POST['service']);
    if ($service < 1 || $service > 3) {
        echo json_encode(['success' => false, 'error' => 'Invalid service number']);
        exit;
    }

    $targetDir = "uploads/";
    $filename = basename($_FILES['image']['name']);
    $targetFile = $targetDir . uniqid("service{$service}_") . "_" . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $column = "service{$service}_img";

        $stmt = $conn->prepare("UPDATE homepage SET $column = ? WHERE id = 1");
        $stmt->bind_param("s", $targetFile);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'filePath' => $targetFile]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Image upload failed']);
    }

    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
