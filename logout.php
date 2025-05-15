<?php
// Start the session
session_start();

// Destroy all session data
session_destroy();

// Write session data and end session
session_write_close();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to home page
header("Location: index.php");
exit();
?>