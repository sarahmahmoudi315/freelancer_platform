<?php
// client/dashboard.php

session_start();

// Base URL for links & redirects
$base = '/freelancer_platform/';

// Only clients allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include  __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Client Dashboard</h1>
<p>Welcome, Client! Here you can:</p>
<ul>
  <li>
    <a href="<?= $base ?>client/projects.php">
      Manage your projects
    </a>
  </li>
  <li>
    <a href="<?= $base ?>client/payments.php">
      Process payments
    </a>
  </li>
</ul>

<?php include __DIR__ . '/../includes/footer.php'; ?>
