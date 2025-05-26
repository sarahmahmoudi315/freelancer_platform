<?php
// freelancer/earnings.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: {$base}login.php");
    exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">My Earnings</h1>

<?php
$stmt = $conn->prepare("
  SELECT
    pay.id,
    pr.title         AS project,
    pay.amount,
    pay.currency,
    pay.payment_date AS paid_at
  FROM payments pay
  JOIN tasks    t  ON t.id          = pay.task_id
  JOIN projects pr ON pr.id         = t.project_id
  WHERE t.freelancer_id = ?
    AND pay.status      = 'paid'
  ORDER BY pay.payment_date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0):
?>
  <div class="alert alert-info">You have no earnings yet.</div>
<?php else: ?>
  <table class="table table-hover">
    <thead class="table-secondary">
      <tr>
        <th>#</th>
        <th>Project</th>
        <th>Amount</th>
        <th>Currency</th>
        <th>Paid At</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($r['id']) ?></td>
        <td><?= htmlspecialchars($r['project']) ?></td>
        <td><?= htmlspecialchars($r['currency']) ?> <?= number_format($r['amount'],2) ?></td>
        <td><?= htmlspecialchars($r['currency']) ?></td>
        <td><?= date('M j, Y H:i', strtotime($r['paid_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
