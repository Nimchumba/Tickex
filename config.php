<?php
// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "tickex_db";

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Start session for login system
session_start();

// Google sign-in client ID (replace with your Google OAuth client ID)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
?>