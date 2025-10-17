<?php 
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fname = trim($_POST['fullName']);         // Changed from regUsername
    $username = trim($_POST['regUsername']);
    $email = trim($_POST['regEmail']);
    $password = $_POST['regPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $address = trim($_POST['address']);        // NEW: get address

    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    $stmt = $conn->prepare("INSERT INTO users (Fname, Username, password, Email, Address) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $fname, $username, $password, $email, $address);

    if (!$stmt->execute()) {
        if ($conn->errno === 1062) {
            die("Email already exists.");
        } else {
            die("Execute failed: " . $stmt->error);
        }
    } else {
        echo '<script>
            alert("Registration successful!");
            window.location.href = "http://localhost/autotec/index.php";
        </script>';
        exit;
    }

    $stmt->close();
}

$conn->close();
?>