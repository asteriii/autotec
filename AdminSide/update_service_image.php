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
    $filename = basename($_FILES['image']['name']); // ✅ FIXED: define $filename
    $uniqueName = uniqid("service{$service}_") . "_" . $filename;
    $targetFile = $targetDir . $uniqueName;

    $column = "service{$service}_img";

    // 🔍 Get current image filename from DB
    $stmt = $conn->prepare("SELECT $column FROM homepage WHERE id = 1");
    $stmt->execute();
    $stmt->bind_result($currentImagePath);
    $stmt->fetch();
    $stmt->close();

    // 🧹 Delete old image if it exists
    if (!empty($currentImagePath) && file_exists("uploads/" . $currentImagePath)) {
        unlink("uploads/" . $currentImagePath);
    }

    // 💾 Upload new image
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("UPDATE homepage SET $column = ? WHERE id = 1");
        $stmt->bind_param("s", $uniqueName); // Save filename only

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'filePath' => 'uploads/' . $uniqueName . '?v=' . time()]);
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
