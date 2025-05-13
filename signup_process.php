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
    $first_name = $conn->real_escape_string(trim($_POST['first_name']));
    $last_name = $conn->real_escape_string(trim($_POST['last_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone_number = $conn->real_escape_string(trim($_POST['phone_number']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Generate a username based on email (before the @ symbol)
    $username = explode('@', $email)[0];
    
    // Simple validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $response['message'] = "All fields are required";
    } else if ($password !== $confirm_password) {
        $response['message'] = "Passwords do not match";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        // Check if email already exists
        $checkEmail = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if ($checkEmail->num_rows > 0) {
            $response['message'] = "Email already exists";
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user data into database
            $sql = "INSERT INTO users (username, first_name, last_name, email, phone_number, password, full_name, role, active) 
                    VALUES ('$username', '$first_name', '$last_name', '$email', '$phone_number', '$hashed_password', CONCAT('$first_name', ' ', '$last_name'), 'customer', 1)";
            
            if ($conn->query($sql) === TRUE) {
                // Success
                $response['status'] = true;
                $response['message'] = "Account created successfully";
                $response['data'] = array(
                    'user_id' => $conn->insert_id,
                    'username' => $username,
                    'email' => $email
                );
                
                // Start session and set user data
                session_start();
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
            } else {
                $response['message'] = "Error: " . $conn->error;
            }
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