<?php
session_start();
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

// Redirect to the login page or home page
header("Location: user_login.php");
exit();
?>
