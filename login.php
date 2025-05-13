<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$dbname = 'fresh_farm';  // Make sure this matches your actual DB name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Get and sanitize input
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validate input
if(empty($email) || empty($password)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter both email and password'
    ]);
    exit;
}

try {
    // Log the attempted login
    error_log("Login attempt: $email");
    
    // Prepare and execute query - use the correct column name
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug user data
    error_log("User data: " . print_r($user, true));
    
    // Verify user exists and password is correct
    if ($user && password_verify($password, $user['password'])) {
        // Create session with correct column names
        $_SESSION['user_id'] = $user['user_id'];  // Make sure to use user_id not id
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Log successful login
        error_log("Login successful for: $email");

        // Return success response
        echo json_encode([
            'status' => true,
            'message' => 'Login successful! Redirecting...',
            'user' => [
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    } else {
        // Invalid credentials
        error_log("Login failed for: $email - Invalid credentials");
        echo json_encode([
            'status' => false,
            'message' => 'Invalid email or password'
        ]);
    }
} catch(PDOException $e) {
    // Database error
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Login failed: ' . $e->getMessage()
    ]);
}
?>