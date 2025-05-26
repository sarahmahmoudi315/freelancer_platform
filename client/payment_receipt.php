<?php
// client/payment_receipt.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id'], $_GET['id']) || $_SESSION['role']!=='client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$id = (int)$_GET['id'];
$stmt = $conn->prepare("
  SELECT 
    pay.id,
    pr.title         AS project,
    pay.amount,
    pay.currency,
    pay.payment_date AS paid_at,
    pay.card_brand,
    pay.card_last4
  FROM payments pay
  JOIN tasks    t  ON t.id       = pay.task_id
  JOIN projects pr ON pr.id      = t.project_id
  WHERE pay.id = ? AND pr.client_id = ?
");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    echo "<div class='alert alert-danger'>Receipt not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<h1 class="mb-4">Payment Receipt</h1>
<ul class="list-group mb-4">
  <li class="list-group-item"><strong>Invoice #:</strong> <?= $receipt['id'] ?></li>
  <li class="list-group-item"><strong>Project:</strong> <?= htmlspecialchars($receipt['project']) ?></li>
  <li class="list-group-item"><strong>Amount:</strong> <?= htmlspecialchars($receipt['currency']) ?> <?= number_format($receipt['amount'],2) ?></li>
  <li class="list-group-item"><strong>Date:</strong> <?= date('M j, Y \a\t H:i', strtotime($receipt['paid_at'])) ?></li>
  <li class="list-group-item"><strong>Paid With:</strong> <?= htmlspecialchars($receipt['card_brand'] . ' •••• ' . $receipt['card_last4']) ?></li>
</ul>

<a href="<?= $base ?>client/payments.php" class="btn btn-secondary">← Back to Invoices</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
