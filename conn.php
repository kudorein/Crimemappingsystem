<?php
// Hostinger Database Configuration
$db_host = "localhost"; 
$db_user = "u780668365_usr_hJ4cM69o"; // From your screenshot
$db_name = "u780668365_db_hJ4cM69o";  // From your screenshot
$db_pass = "5#iY3@X+";  // Enter the actual password you created here

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("System Connection Failure: " . mysqli_connect_error());
}

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>