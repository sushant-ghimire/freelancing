<?php
// This script checks if a user is logged in.
// If not, it redirects them to the login page.
// Include this at the top of any page that requires a user to be logged in.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Not logged in → redirect to login page
    header("Location: login.php");
    exit();
}
?>
