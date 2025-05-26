<?php
// freelancer/dashboard.php

session_start();

// Base URL for links & redirects
$base = '/freelancer_platform/';

// Only freelancers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header('Location: ' . $base . 'login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Freelancer Dashboard</h1>
<p>Welcome, Freelancer! Here you can:</p>
<ul>
  <li>
    <a href="<?= $base ?>freelancer/requests.php">
      Manage requests
    </a>
  </li>
  <li>
    <a href="<?= $base ?>freelancer/portfolio.php">
      Manage your portfolio
    </a>
  </li>
  <li>
    <a href="<?= $base ?>freelancer/earnings.php">
      Track your earnings
    </a>
  </li>
  <li>
    <a href="<?= $base ?>freelancer/payout_history.php">
      Payout history
    </a>
  </li>
</ul>

<?php include __DIR__ . '/../includes/footer.php'; ?>
