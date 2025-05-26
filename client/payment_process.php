<?php
// client/payment_process.php

session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id'], $_GET['id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}
require __DIR__ . '/../includes/db.php';

$pay_id   = (int)$_GET['id'];
$currency = $_POST['currency'];
$method   = $_POST['method'];

// determine card to charge
if ($method === 'saved') {
    // use saved card
    $saved_card_id = (int)$_POST['saved_card_id'];
    $cardStmt = $conn->prepare("
      SELECT card_brand, card_last4
        FROM saved_cards
       WHERE id = ? AND user_id = ?
    ");
    $cardStmt->bind_param("ii", $saved_card_id, $_SESSION['user_id']);
    $cardStmt->execute();
    $card = $cardStmt->get_result()->fetch_assoc();
    $card_brand = $card['card_brand'];
    $card_last4 = $card['card_last4'];
    $cardStmt->close();
} else {
    // save new card
    list($m,$y) = explode('/', $_POST['exp']);
    $card_brand = 'Mastercard';
    $card_last4 = substr(preg_replace('/\D/','', $_POST['card_number']), -4);

    $ins = $conn->prepare("
      INSERT INTO saved_cards
        (user_id, card_brand, card_last4, exp_month, exp_year)
      VALUES (?,?,?,?,?)
    ");
    $ins->bind_param(
      "issii",
      $_SESSION['user_id'],
      $card_brand,
      $card_last4,
      $m,
      $y
    );
    $ins->execute();
    $saved_card_id = $ins->insert_id;
    $ins->close();
}

// simulate random decline (~10%)
$status = (mt_rand(1, 10) === 1) ? 'failed' : 'paid';
$now    = date('Y-m-d H:i:s');

// fetch freelancer_id
$fetch = $conn->prepare("
  SELECT t.freelancer_id
    FROM payments p
    JOIN tasks t ON t.id = p.task_id
   WHERE p.id = ?
");
$fetch->bind_param("i", $pay_id);
$fetch->execute();
$row           = $fetch->get_result()->fetch_assoc() ?: [];
$freelancer_id = $row['freelancer_id'];
$fetch->close();

// update payments
$upd = $conn->prepare("
  UPDATE payments
     SET status         = ?,
         payment_date   = ?,
         currency       = ?,
         card_brand     = ?,
         card_last4     = ?,
         freelancer_id  = ?,
         saved_card_id  = ?
   WHERE id = ?
");
$upd->bind_param(
  "ssssiiii",
  $status,
  $now,
  $currency,
  $card_brand,
  $card_last4,
  $freelancer_id,
  $saved_card_id,
  $pay_id
);
$upd->execute();
$upd->close();

// done
$_SESSION['payment_result'] = $status;
header("Location: {$base}client/payments.php");
exit;
