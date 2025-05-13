<?php
// Include database connection
require_once './connect.php';

// Initialize response array
$response = array(
    'status' => false,
    'message' => '',
    'data' => null
);

// Check if form is submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $response['message'] = "Email and password are required";
    } else {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = '$email' AND active = 1";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Success
                $response['status'] = true;
                $response['message'] = "Login successful";
                $response['data'] = array(
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                );
                
                // Update last login time
                $updateSql = "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id'];
                $conn->query($updateSql);
                
                // Start session and set user data
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
            } else {
                $response['message'] = "Invalid password";
            }
        } else {
            $response['message'] = "User not found or account is inactive";
        }
    }
} else {
    $response['message'] = "Invalid request method";
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close connection
$conn->close();
?>