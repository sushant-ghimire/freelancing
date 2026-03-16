<?php
$conn = new mysqli("localhost", "root", "", "kambazar_db");

$email = "admin@admin.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";

$sql = "INSERT INTO users (email, password, role) VALUES ('$email', '$password', '$role')";

if ($conn->query($sql) === TRUE) {
    echo "Admin created successfully";
} else {
    echo "Error: " . $conn->error;
}
?>