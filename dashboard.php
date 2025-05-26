<?php
// dashboard.php

session_start();

// If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect based on role
switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    case 'freelancer':
        header('Location: freelancer/dashboard.php');
        break;
    case 'client':
    default:
        header('Location: client/dashboard.php');
        break;
}
exit;
