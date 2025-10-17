<?php
header('Content-Type: application/json');

// Include the database connection
include 'db.php';

// Check if database connection is established
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// Query the homepage table for the row with ID 1
$sql = "SELECT title, header, operate, location, contact, service1_img, service2_img, service3_img FROM homepage WHERE id = 1";
$result = $conn->query($sql);

// Check if the query was successful and return the data
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Return the result as JSON
    echo json_encode([
        'title' => $row['title'],
        'header' => $row['header'],
        'operate' => $row['operate'],
        'location' => $row['location'],
        'contact' => $row['contact'],
        'service1_img' => $row['service1_img'],
        'service2_img' => $row['service2_img'],
        'service3_img' => $row['service3_img']
    ]);
} else {
    echo json_encode(['error' => 'No homepage data found.']);
}
?>
