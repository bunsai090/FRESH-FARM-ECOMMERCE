<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require '../connect.php';

$login_error = false;

// Check if connection was successful
if (!$conn) {
    $login_error = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the form and prevent undefined index errors
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = true;
    } else {
        try {
            // Prepare and execute query to find the user
            $sql = "SELECT * FROM admin WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $storedHash = $row['password'];

                // Verify password - keeping password_verify for better security
                if (password_verify($password, $storedHash)) {
                    // Start session and store necessary data
                    $_SESSION['username'] = $username;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['is_admin'] = true;
                    
                    // Redirect to dashboard like in the second code
                    header("Location: ../dashboard.php");
                    exit();
                } else {
                    $login_error = true;
                }
            } else {
                $login_error = true;
            }

            // Close the statement
            $stmt->close();
        } catch (Exception $e) {
            $login_error = true;
        }
    }
    
    // Close the connection if it exists and we're done with it
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fresh Farm</title>
  <link rel="stylesheet" href="../css/login.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
  <div class="login-wrapper">
    <div class="login-form">
      <img src="../assets/farmfresh.png" alt="Fresh Farm Logo" class="logo" />
      <h2>Welcome back, Admin</h2>

      <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <div class="password-field">
          <input type="password" name="password" placeholder="Password" id="password" required />
          <i class="fa-solid fa-eye" id="togglePassword"></i>
        </div>

        <div class="forgot-password">
          <a href="#">Forgot Password?</a>
        </div>

        <button type="submit" class="login-btn">Log in</button>
      </form>
    </div>
  </div>

  <script>
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");

    togglePassword.addEventListener("click", () => {
      const type = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = type;
      togglePassword.classList.toggle("fa-eye");
      togglePassword.classList.toggle("fa-eye-slash");
    });
    
    // Display error using JavaScript alert like in the second code
    <?php if($login_error): ?>
      window.onload = function() {
        alert('Invalid username or password. Please try again.');
      };
    <?php endif; ?>
  </script>
</body>
</html>