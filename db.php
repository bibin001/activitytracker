<?php
// Database connection
$servername = "localhost";
$username = "tistad3b_root"; // Your MySQL username
$password = "SJCETPalai*2007"; // Your MySQL password
$dbname = "tistad3b_activity"; // The database where your users table is located

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>