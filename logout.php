<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set a success message
session_start(); // Start a new session to store the message
$_SESSION['success_message'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: user_login.php");
exit();
?> 