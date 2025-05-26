<?php
// admin/logs.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include   __DIR__ . '/../includes/header.php';

// Fetch recent logs
$logs = $conn->query("
  SELECT l.created_at, u.username, l.event_type, l.event_message
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
   ORDER BY l.created_at DESC
   LIMIT 50
");
?>

<h1 class="mb-4 text-white">System Logs</h1>

<table class="table table-dark table-striped">
  <thead>
    <tr>
      <th>When</th>
      <th>User</th>
      <th>Type</th>
      <th>Details</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $logs->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['created_at']) ?></td>
      <td><?= htmlspecialchars($row['username'] ?? '—') ?></td>
      <td><?= htmlspecialchars($row['event_type']) ?></td>
      <td><?= htmlspecialchars($row['event_message']) ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
