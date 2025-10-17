<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['AboutID'];
    $name = $_POST['BranchName'];
    $map = $_POST['MapLink'];
    $desc = $_POST['Description'];

    $targetDir = "uploads/";
    $fileName = $_FILES["Picture"]["name"];
    $targetFilePath = $targetDir . basename($fileName);

    if (!empty($fileName)) {
        move_uploaded_file($_FILES["Picture"]["tmp_name"], $targetFilePath);
        $query = "UPDATE about_us SET BranchName=?, MapLink=?, Description=?, Picture=? WHERE AboutID=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $name, $map, $desc, $fileName, $id);
    } else {
        $query = "UPDATE about_us SET BranchName=?, MapLink=?, Description=? WHERE AboutID=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $map, $desc, $id);
    }

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }
    exit;
} else {
    echo "error: invalid request";
    exit;
}
