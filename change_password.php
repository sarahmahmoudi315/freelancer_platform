<?php
// change_password.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

$uid     = $_SESSION['user_id'];
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $conf    = $_POST['confirm_password'] ?? '';

    // fetch existing hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['password'];

    if (!password_verify($current, $hash)) {
        $errors[] = 'Current password is incorrect.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($new !== $conf) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        $newhash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $newhash, $uid);
        $upd->execute();
        $success = 'Password changed successfully!';
    }
}
?>
<h1 class="mb-4">Change Password</h1>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<form method="POST" action="change_password.php">
  <div class="mb-3">
    <label for="current_password" class="form-label">Current Password</label>
    <input type="password" name="current_password" id="current_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label for="new_password" class="form-label">New Password</label>
    <input type="password" name="new_password" id="new_password" class="form-control" required>
  </div>
  <div class="mb-3">
    <label for="confirm_password" class="form-label">Confirm New Password</label>
    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Change Password</button>
  <a href="profile.php" class="btn btn-link">← Back to Profile</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
