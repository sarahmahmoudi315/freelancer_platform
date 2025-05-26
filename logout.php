<?php
// logout.php

session_start();

// grab user ID before we destroy the session
$userId = $_SESSION['user_id'] ?? null;

// connect and record the logout
if ($userId) {
    require __DIR__ . '/includes/db.php';

    $evt  = 'logout';
    $msg  = "User {$userId} logged out";
    $stmt = $conn->prepare("
      INSERT INTO logs (user_id, event_type, event_message)
      VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iss', $userId, $evt, $msg);
    $stmt->execute();
    $stmt->close();
}

// now destroy session
session_unset();
session_destroy();

// redirect back to login
header('Location: login.php');
exit;
