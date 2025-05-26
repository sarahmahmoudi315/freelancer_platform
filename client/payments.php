<?php
// client/payments.php

session_start();
// Base URL for redirects & links
$base = '/freelancer_platform/';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// Exchange rates for display (to USD)
$exchangeRates = [
    'USD' => 1.00,
    'EUR' => 1.08,   // 1 EUR = 1.08 USD
    'TND' => 0.32,
    'GBP' => 1.25
];

// Currency symbols
$symbols = [
    'USD' => '$',
    'EUR' => '€',
    'TND' => 'د.ت',
    'GBP' => '£'
];

// Flash message for checkout result
if (!empty($_SESSION['payment_result'])) {
    $res = $_SESSION['payment_result'];
    echo '<div class="alert alert-'.($res==='paid'?'success':'danger').'">'
       . ($res==='paid' ? '✅ Payment successful!' : '❌ Payment failed, please try again.')
       . '</div>';
    unset($_SESSION['payment_result']);
}
?>

<h1 class="mb-4">Your Invoices & Payments</h1>

<table class="table table-hover align-middle">
  <thead class="table-secondary">
    <tr>
      <th>#</th>
      <th>Project</th>
      <th>Amount</th>
      <th>Currency</th>
      <th>In USD</th>
      <th>Payment Status</th>
      <th>Task Status</th>
      <th>Method</th>
      <th>Due / Paid At</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $conn->prepare("
      SELECT 
        pay.id,
        pr.title         AS project,
        pay.amount,
        pay.currency,
        pay.status       AS payment_status,
        pay.payment_date AS paid_at,
        pay.card_brand,
        pay.card_last4,
        t.status         AS task_status,
        t.deadline
      FROM payments pay
      JOIN tasks    t   ON t.id         = pay.task_id
      JOIN projects pr ON pr.id        = t.project_id
      WHERE pr.client_id = ?
      ORDER BY COALESCE(pay.payment_date, t.deadline) DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($p = $res->fetch_assoc()):
        $amt         = $p['amount'];
        $cur         = $p['currency'];
        $symbol      = $symbols[$cur] ?? $cur.' ';
        $rate        = $exchangeRates[$cur] ?? 1;
        $inUsd       = number_format($amt * $rate, 2);
        $payStatus   = $p['payment_status'];
        $taskStatus  = $p['task_status'];
        $paidAt      = $p['paid_at']
                        ? date('M j, Y H:i', strtotime($p['paid_at']))
                        : '—';
        $method      = $payStatus==='paid'
                        ? htmlspecialchars($p['card_brand'].' •••• '.$p['card_last4'])
                        : '–';
        $dueDate     = $p['paid_at']
                        ? $paidAt
                        : ($p['deadline']
                           ? date('M j, Y', strtotime($p['deadline']))
                           : '—');
        // Decide the button label for pending payment
        if ($payStatus==='pending') {
            $btnLabel = $taskStatus==='completed'
                        ? 'Pay Now'
                        : 'Advance';
        }
    ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['project']) ?></td>
      <td><?= $symbol . number_format($amt, 2) ?></td>
      <td><?= $cur ?></td>
      <td>$<?= $inUsd ?></td>
      <td>
        <?php if ($payStatus==='pending'): ?>
          <span class="badge bg-warning text-dark">Pending</span>
        <?php elseif ($payStatus==='paid'): ?>
          <span class="badge bg-success">Paid</span>
        <?php else: ?>
          <span class="badge bg-danger">Failed</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($taskStatus==='pending'): ?>
          <span class="badge bg-secondary">Pending</span>
        <?php elseif ($taskStatus==='completed'): ?>
          <span class="badge bg-primary">Completed</span>
        <?php else: ?>
          <span class="badge bg-dark"><?= ucfirst(htmlspecialchars($taskStatus)) ?></span>
        <?php endif; ?>
      </td>
      <td><?= $method ?></td>
      <td><?= $dueDate ?></td>
      <td>
        <?php if ($payStatus==='pending'): ?>
          <a href="<?= $base ?>client/payment_checkout.php?id=<?= $p['id'] ?>"
             class="btn btn-sm btn-<?= $taskStatus==='completed' ? 'primary' : 'info' ?>">
            <?= $btnLabel ?>
          </a>
        <?php elseif ($payStatus==='paid'): ?>
          <a href="<?= $base ?>client/payment_receipt.php?id=<?= $p['id'] ?>"
             class="btn btn-sm btn-outline-info">
            View Receipt
          </a>
        <?php else: /* failed */ ?>
          <a href="<?= $base ?>client/payment_checkout.php?id=<?= $p['id'] ?>"
             class="btn btn-sm btn-danger">
            Retry
          </a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
