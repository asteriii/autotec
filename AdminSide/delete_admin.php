<?php //needed sa admin_acc_manage
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['admin_id'];
    $stmt = $conn->prepare("DELETE FROM admin WHERE admin_id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_acc_manage.php?deleted=1");
    } else {
        echo "Error deleting admin.";
    }
}
?>
