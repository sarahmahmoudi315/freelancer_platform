<?php
// freelancer/payouts.php

session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
  header("Location: {$base}login.php"); exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// list all paid & unclaimed payments for this freelancer
$stmt = $conn->prepare("
  SELECT
    pay.id,
    pr.title      AS project,
    pay.amount,
    pay.currency,
    pay.payment_date AS paid_at,
    pay.card_brand,
    pay.card_last4,
    pay.status      AS payment_status
  FROM payments pay
  JOIN tasks   t  ON t.id         = pay.task_id
  JOIN projects pr ON pr.id       = t.project_id
  WHERE pay.freelancer_id = ?
    AND pay.status = 'paid'
  ORDER BY pay.payment_date DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>

<h1 class="mb-4">Your Earnings</h1>
<?php if ($res->num_rows === 0): ?>
  <div class="alert alert-info">You have no earnings yet.</div>
<?php else: ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Project</th>
        <th>Amount</th>
        <th>Currency</th>
        <th>Paid At</th>
        <th>Method</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($p = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['project']) ?></td>
        <td><?= number_format($p['amount'],2) ?></td>
        <td><?= htmlspecialchars($p['currency']) ?></td>
        <td><?= date('M j, Y H:i', strtotime($p['paid_at'])) ?></td>
        <td>
          <?= htmlspecialchars($p['card_brand']) ?> •••• <?= htmlspecialchars($p['card_last4']) ?>
        </td>
        <td>
          <a href="<?= $base ?>freelancer/payout_request.php?id=<?= $p['id'] ?>"
             class="btn btn-sm btn-success"
             onclick="return confirm('Claim this payout?')">
            Claim
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
