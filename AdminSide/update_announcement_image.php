<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'error' => 'Missing image']);
        exit;
    }

    $targetDir = "uploads/";
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid("announcement_") . "." . $ext;
    $targetFile = $targetDir . $uniqueName;

    // 🔍 Get current image path from DB
    $stmt = $conn->prepare("SELECT announcement_img FROM homepage WHERE id = 1");
    $stmt->execute();
    $stmt->bind_result($currentImagePath);
    $stmt->fetch();
    $stmt->close();

    // 🧹 Delete old image if it exists
    if (!empty($currentImagePath) && file_exists("uploads/" . $currentImagePath)) {
        unlink("uploads/" . $currentImagePath);
    }

    // 💾 Save new uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("UPDATE homepage SET announcement_img = ? WHERE id = 1");
        $stmt->bind_param("s", $uniqueName);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'filePath' => 'uploads/' . $uniqueName . '?v=' . time()
            ]);
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
