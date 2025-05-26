<?php
// admin/dashboard.php

// Start session & enforce admin-only access
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Database connection
require __DIR__ . '/../includes/db.php';

// Page header (defines $base and renders <head> + nav)
include __DIR__ . '/../includes/header.php';
?>

<div class="text-white text-center mt-5">
  <h1 class="display-4">Admin Dashboard</h1>
  <p class="lead mb-4">Welcome, Admin! Use the buttons below to manage the platform.</p>
</div>

<div class="row g-3 mb-5">
  <div class="col-md-4">
    <a href="<?= $base ?>admin/users.php" class="btn btn-light w-100 py-3">
      Manage All Users
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= $base ?>admin/analytics.php" class="btn btn-light w-100 py-3">
      View Platform Analytics
    </a>
  </div>
  <div class="col-md-4">
    <a href="<?= $base ?>admin/logs.php" class="btn btn-light w-100 py-3">
      Monitor System Logs
    </a>
  </div>
</div>

<?php
// Page footer
include __DIR__ . '/../includes/footer.php';
?>
