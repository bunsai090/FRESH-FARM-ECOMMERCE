<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'fresh_farm';  // Change to match your actual database name
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
$first_name = filter_var($_POST['first_name'] ?? '', FILTER_SANITIZE_STRING);
$last_name = filter_var($_POST['last_name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone_number = filter_var($_POST['phone_number'] ?? '', FILTER_SANITIZE_STRING);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Generate a username (required by your DB schema)
$username = strtolower(str_replace(' ', '', $first_name) . substr(uniqid(), -5));

// Validate input
if(empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please fill in all required fields'
    ]);
    exit;
}

// Validate email format
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

// Check if passwords match
if($password !== $confirm_password) {
    echo json_encode([
        'status' => false,
        'message' => 'Passwords do not match'
    ]);
    exit;
}

try {
    // Check if email already exists - use the correct column name
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        echo json_encode([
            'status' => false,
            'message' => 'Email already registered'
        ]);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user - include username field
    $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, phone_number, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $first_name, $last_name, $email, $phone_number, $hashed_password]);

    // Get the new user's ID
    $user_id = $pdo->lastInsertId();

    // Create session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
    $_SESSION['user_email'] = $email;

    echo json_encode([
        'status' => true,
        'message' => 'Registration successful! Redirecting...',
        'user' => [
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ]
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>