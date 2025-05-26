<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL to ensure correct paths from any subfolder
$base = '/freelancer_platform/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FreelanceApp</title>
  <!-- 1) Your downloaded theme CSS -->
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
  <!-- 2) Your custom overrides -->
  <link rel="stylesheet" href="<?= $base ?>assets/css/custom.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="<?= $base ?>index.php">FreelanceApp</a>
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>register.php">Register</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>profile.php">My Profile</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $base ?>logout.php">Logout</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
