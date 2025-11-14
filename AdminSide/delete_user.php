<?php // needed for user_acc_manage.php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['UserID'];
  $stmt = $conn->prepare("DELETE FROM users WHERE UserID = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header("Location: user_acc_manage.php?deleted=1");
  } else {
    header("Location: user_acc_manage.php?error=db");
  }
}
?>
