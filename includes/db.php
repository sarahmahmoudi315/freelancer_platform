<?php
// includes/db.php

// 1) Database credentials
$host     = 'localhost';
$user     = 'root';
$password = '';             // XAMPP default
$dbname   = 'freelance_platform';

// 2) Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// 3) Check for errors
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
?>
