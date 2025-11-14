<?php // needed for user_acc_manage.php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['UserID'];
  $Fname = $_POST['Fname'];
  $Username = $_POST['Username'];
  $password = $_POST['password'];
  $Email = $_POST['Email'];
  $Address = $_POST['Address'];
  $PhoneNumber = $_POST['PhoneNumber'];

  $check = $conn->prepare("SELECT * FROM users WHERE (Username = ? OR Email = ?) AND UserID != ?");
  $check->bind_param("ssi", $Username, $Email, $id);
  $check->execute();
  $res = $check->get_result();

  if ($res->num_rows > 0) {
    header("Location: user_acc_manage.php?error=duplicate");
    exit;
  }

  $stmt = $conn->prepare("UPDATE users SET Fname=?, Username=?, password=?, Email=?, Address=?, PhoneNumber=? WHERE UserID=?");
  $stmt->bind_param("ssssssi", $Fname, $Username, $password, $Email, $Address, $PhoneNumber, $id);

  if ($stmt->execute()) {
    header("Location: user_acc_manage.php?success=1");
  } else {
    header("Location: user_acc_manage.php?error=db");
  }
}
?>
