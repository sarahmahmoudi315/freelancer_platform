<?php
// freelancer/payout_request.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id'],$_GET['id'])||$_SESSION['role']!=='freelancer') {
  header("Location: {$base}login.php"); exit;
}
require __DIR__ . '/../includes/db.php';

$pay_id = (int)$_GET['id'];
// fetch payment to verify ownership & amount
$stmt = $conn->prepare("
  SELECT amount,currency
    FROM payments
   WHERE id = ? AND freelancer_id = ?
");
$stmt->bind_param("ii",$pay_id,$_SESSION['user_id']);
$stmt->execute();
if (!$row = $stmt->get_result()->fetch_assoc()) {
  header("Location: {$base}freelancer/payouts.php"); exit;
}

// insert a payout request
$ins = $conn->prepare("
  INSERT INTO payouts (freelancer_id,amount,currency)
  VALUES (?,?,?)
");
$ins->bind_param("ids",$_SESSION['user_id'],$row['amount'],$row['currency']);
$ins->execute();

// mark payment as claimed
$upd = $conn->prepare("UPDATE payments SET status='claimed' WHERE id = ?");
$upd->bind_param("i",$pay_id);
$upd->execute();

header("Location: {$base}freelancer/payout_history.php");
exit;
