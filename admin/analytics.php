<?php
// admin/analytics.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include   __DIR__ . '/../includes/header.php';

// Gather metrics
$totalUsers      = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$totalClients    = $conn->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetch_row()[0];
$totalFreelancers= $conn->query("SELECT COUNT(*) FROM users WHERE role='freelancer'")->fetch_row()[0];
$totalProjects   = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$totalTasks      = $conn->query("SELECT COUNT(*) FROM tasks")->fetch_row()[0];
$totalPayments   = $conn->query("SELECT COUNT(*) FROM payments")->fetch_row()[0];
$totalRevenue    = $conn->query("SELECT IFNULL(SUM(amount),0) FROM payments")->fetch_row()[0];
?>

<h1 class="mb-4 text-white">Platform Analytics</h1>

<div class="row g-4">
  <?php 
    $cards = [
      'Users'       => $totalUsers,
      'Clients'     => $totalClients,
      'Freelancers' => $totalFreelancers,
      'Projects'    => $totalProjects,
      'Tasks'       => $totalTasks,
      'Payments'    => $totalPayments,
      'Revenue'     => '$' . number_format($totalRevenue,2),
    ];
    foreach ($cards as $label => $value): 
  ?>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title"><?= $label ?></h5>
          <p class="display-6"><?= $value ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
