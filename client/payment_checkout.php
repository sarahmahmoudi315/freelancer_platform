<?php
// client/payment_checkout.php

session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id'], $_GET['id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$pay_id = (int)$_GET['id'];
// 1) Fetch the payment & verify ownership
$stmt = $conn->prepare("
  SELECT
    pay.id,
    pr.title    AS project,
    pay.amount,
    pay.currency
  FROM payments pay
  JOIN tasks   t  ON t.id       = pay.task_id
  JOIN projects pr ON pr.id     = t.project_id
  WHERE pay.id = ? AND pr.client_id = ?
");
$stmt->bind_param("ii", $pay_id, $_SESSION['user_id']);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
if (!$payment) {
    echo "<div class='alert alert-danger'>Payment not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// 2) Fetch saved cards
$cardsStmt = $conn->prepare("
  SELECT id, card_brand, card_last4, exp_month, exp_year
    FROM saved_cards
   WHERE user_id = ?
   ORDER BY created_at DESC
");
$cardsStmt->bind_param("i", $_SESSION['user_id']);
$cardsStmt->execute();
$savedCards = $cardsStmt->get_result();

// 3) Rates for currency dropdown (as before)
$rates = ['USD' => 1.00, 'EUR' => 0.93, 'TND' => 3.1, 'GBP' => 0.80];
?>

<h1 class="mb-4">Complete Your Payment</h1>

<div class="card p-4 mb-4">
  <h5>Project: <?= htmlspecialchars($payment['project']) ?></h5>
  <p>Amount due: <strong><?= number_format($payment['amount'],2) ?></strong> <?= htmlspecialchars($payment['currency']) ?></p>

  <form method="POST" action="<?= $base ?>client/payment_process.php?id=<?= $pay_id ?>">
    <!-- Currency selector -->
    <div class="mb-3">
      <label class="form-label">Currency</label>
      <select name="currency" class="form-select">
        <?php foreach ($rates as $cur => $rate): ?>
          <option value="<?= $cur ?>"<?= $cur === $payment['currency'] ? ' selected' : '' ?>>
            <?= $cur ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Payment Method selector -->
    <div class="mb-3">
      <label class="form-label">Payment Method</label>
      <select name="method" id="method" class="form-select">
        <?php if ($savedCards->num_rows): ?>
          <option value="saved">Use a saved card…</option>
        <?php endif; ?>
        <option value="new">Add new card…</option>
      </select>
    </div>

    <!-- Saved‐card fields -->
    <div id="savedCardFields" style="display:none;">
      <label class="form-label">Choose a saved card</label>
      <select name="saved_card_id" class="form-select mb-3">
        <?php while ($c = $savedCards->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>">
            <?= htmlspecialchars($c['card_brand']) ?> •••• <?= htmlspecialchars($c['card_last4']) ?>
            (exp <?= sprintf('%02d',$c['exp_month']) ?>/<?= $c['exp_year'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- New‐card fields -->
    <div id="newCardFields" style="display:none;">
      <div class="mb-3">
        <input name="card_name" class="form-control" placeholder="Cardholder Name">
      </div>
      <div class="mb-3">
        <input name="card_number" class="form-control" placeholder="Card Number">
      </div>
      <div class="row">
        <div class="col mb-3">
          <input name="exp" class="form-control" placeholder="MM/YY">
        </div>
        <div class="col mb-3">
          <input name="cvc" class="form-control" placeholder="CVC">
        </div>
      </div>
      <small class="form-text text-muted">
        New cards will be saved for future use.
      </small>
    </div>

    <button type="submit" class="btn btn-primary">Pay Now</button>
    <a href="<?= $base ?>client/payments.php" class="btn btn-link">← Back</a>
  </form>
</div>

<script>
  const method = document.getElementById('method');
  const savedF = document.getElementById('savedCardFields');
  const newF   = document.getElementById('newCardFields');

  function toggleFields() {
    if (method.value === 'saved') {
      savedF.style.display = 'block';
      newF.style.display   = 'none';
    } else {
      savedF.style.display = 'none';
      newF.style.display   = 'block';
    }
  }

  // Run on initial load
  toggleFields();

  // Listen for user changes
  method.addEventListener('change', toggleFields);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
