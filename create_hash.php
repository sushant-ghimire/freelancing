<?php
// Put the password you want to use here
$passwordToHash = 'admin@123';

// This function creates the secure hash
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
?>