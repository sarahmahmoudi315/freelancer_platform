<?php
// admin/users_delete.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id && $id !== $_SESSION['user_id']) {
    // Log the deletion
    $adminId = $_SESSION['user_id'];
    $message = "Admin {$adminId} deleted user {$id}";
    $logStmt = $conn->prepare("
        INSERT INTO logs (user_id, event_type, event_message)
        VALUES (?, 'user_deleted', ?)
    ");
    $logStmt->bind_param('is', $adminId, $message);
    $logStmt->execute();
    $logStmt->close();

    // Perform the delete
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: users.php");
exit;
