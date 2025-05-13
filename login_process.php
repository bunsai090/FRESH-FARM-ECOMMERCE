<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'connect.php';

try {
    // Get and sanitize input
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $user_password = $_POST['password'] ?? ''; // Renamed to avoid confusion with DB password

    // Debug log
    error_log("Login attempt started - Email: " . $email);

    // Validate input
    if(empty($email) || empty($user_password)) {
        error_log("Login failed: Empty email or password");
        echo json_encode([
            'status' => false,
            'message' => 'Please enter both email and password'
        ]);
        exit;
    }

    // Create PDO connection using credentials from connect.php
    // The variables $host, $username, $password, and $dbname should be defined in connect.php
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful");

    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    error_log("Query prepared: SELECT * FROM users WHERE email = " . $email);
    
    $stmt->execute([$email]);
    error_log("Query executed");
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("User data fetched: " . ($user ? "User found" : "No user found"));

    if ($user) {
        error_log("Attempting password verification for user: " . $email);
        if (password_verify($user_password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            error_log("Login successful for user: " . $email);

            echo json_encode([
                'status' => true,
                'message' => 'Login successful! Redirecting...',
                'user' => [
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email'],
                    'role' => $_SESSION['user_role']
                ],
                'redirect' => $_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'
            ]);
        } else {
            error_log("Password verification failed for user: " . $email);
            echo json_encode([
                'status' => false,
                'message' => 'Invalid email or password'
            ]);
        }
    } else {
        error_log("No user found with email: " . $email);
        echo json_encode([
            'status' => false,
            'message' => 'Invalid email or password'
        ]);
    }
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>