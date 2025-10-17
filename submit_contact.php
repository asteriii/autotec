<?php
// Database configuration
$host = 'localhost';
$dbname = 'autotec';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['firstName']) || !isset($input['lastName']) || 
        !isset($input['email']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize input data
    $firstName = trim($input['firstName']);
    $lastName = trim($input['lastName']);
    $email = trim($input['email']);
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $message = trim($input['message']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Validate required fields are not empty
    if (empty($firstName) || empty($lastName) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }
    
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO contact_us (first_name, last_name, email, phone_number, message) VALUES (?, ?, ?, ?, ?)");
    
    // Execute the statement
    $result = $stmt->execute([$firstName, $lastName, $email, $phone, $message]);
    
    if ($result) {
        // Success response
        echo json_encode([
            'success' => true, 
            'message' => 'Your message has been delivered successfully! We will get back to you soon.',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        // Database error
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save your message. Please try again.']);
    }
    
} catch (PDOException $e) {
    // Database connection error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    
    // Log the error for debugging (don't show to user)
    error_log("Database error: " . $e->getMessage());
    
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
    
    // Log the error for debugging
    error_log("General error: " . $e->getMessage());
}
?>