<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

include 'db.php';
require_once 'audit_trail.php';

// Get session variables
$username = $_SESSION['admin_username'] ?? 'Unknown Admin';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $header = $_POST['header'] ?? '';
    $operate = $_POST['operate'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact = $_POST['contact'] ?? '';

    try {
        // Get old values for comparison
        $stmt = $conn->prepare("SELECT title, header, operate, location, contact FROM homepage WHERE id=1");
        $stmt->execute();
        $result = $stmt->get_result();
        $oldData = $result->fetch_assoc();
        $stmt->close();

        // Update database
        $stmt = $conn->prepare("UPDATE homepage SET title=?, header=?, operate=?, location=?, contact=? WHERE id=1");
        $stmt->bind_param("sssss", $title, $header, $operate, $location, $contact);

        if ($stmt->execute()) {
            // Track what changed for audit log
            $changes = [];
            
            if ($oldData['title'] !== $title) {
                $changes[] = 'Title';
            }
            if ($oldData['header'] !== $header) {
                $changes[] = 'Header';
            }
            if ($oldData['operate'] !== $operate) {
                $changes[] = 'Operating Hours';
            }
            if ($oldData['location'] !== $location) {
                $changes[] = 'Location';
            }
            if ($oldData['contact'] !== $contact) {
                $changes[] = 'Contact';
            }

            // 🧾 Log the audit trail
            if (!empty($changes)) {
                $changedFields = implode(', ', $changes);
                logHomepageUpdate($username, $changedFields);
            }

            echo json_encode(["success" => true, "message" => "Homepage updated successfully"]);
        } else {
            throw new Exception($stmt->error);
        }

        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        // Log error
        logAction($username, 'Error', "Failed to update homepage: " . $e->getMessage());
        
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}

echo json_encode(["success" => false, "error" => "Invalid request"]);
?>