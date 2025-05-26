<?php
// freelancer/payout_history.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!=='freelancer') {
  header("Location: {$base}login.php"); exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$stmt = $conn->prepare("
  SELECT id,amount,currency,status,requested_at,processed_at
    FROM payouts
   WHERE freelancer_id = ?
   ORDER BY requested_at DESC
");
$stmt->bind_param("i",$_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>

<h1 class="mb-4">Payout History</h1>
<table class="table table-striped">
  <thead><tr>
    <th>ID</th><th>Amount</th><th>Currency</th>
    <th>Status</th><th>Requested</th><th>Processed</th>
  </tr></thead>
  <tbody>
  <?php while($r=$res->fetch_assoc()): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td>$<?= number_format($r['amount'],2) ?></td>
      <td><?= htmlspecialchars($r['currency']) ?></td>
      <td><?= ucfirst(htmlspecialchars($r['status'])) ?></td>
      <td><?= htmlspecialchars($r['requested_at']) ?></td>
      <td><?= $r['processed_at'] 
                ? htmlspecialchars($r['processed_at']) 
                : '—' ?></td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
