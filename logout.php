<?php
// FILE: /logout.php
// PURPOSE: To log the user out by destroying their session.

// --- STEP 1: START THE SESSION ---
// You must start the session to be able to access and modify it.
session_start();

// --- STEP 2: UNSET ALL SESSION VARIABLES ---
// $_SESSION = array(); is a reliable way to clear all data from the session.
$_SESSION = array();

// --- STEP 3: DESTROY THE SESSION ---
// This function completely removes the session from the server.
session_destroy();

// --- STEP 4: REDIRECT TO THE LANDING PAGE ---
// After logging out, send the user back to the main page.
header("Location: index.php");
exit(); // Stop the script.
?>