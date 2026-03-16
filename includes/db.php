<?php
// Start the session at the very beginning of your script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CONNECTION ---
// Replace with your actual database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password
define('DB_NAME', 'kambazar_db');

// Create a database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- HELPER FUNCTIONS ---

// Function to prevent sharing of contact information
function sanitize_text($text) {
    // Nepali phone numbers (starting with 98 or 97)
    $phone_pattern = '/\b(98|97)\d{8}\b/';
    // Common email pattern
    $email_pattern = '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i';
    
    $replacement = '[Contact Info Hidden]';
    $text = preg_replace($phone_pattern, $replacement, $text);
    return preg_replace($email_pattern, $replacement, $text);
}
?>