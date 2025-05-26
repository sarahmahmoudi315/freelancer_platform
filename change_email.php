<?php
// change_email.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

$uid    = $_SESSION['user_id'];
$errors = [];
$success = '';

// fetch current email
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc()['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = trim($_POST['email']);
    if (!filter_var($new, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // check uniqueness
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $chk->bind_param("si", $new, $uid);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows) {
            $errors[] = 'That email is already in use.';
        } else {
            $upd = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $upd->bind_param("si", $new, $uid);
            $upd->execute();
            $success = 'Email updated successfully!';
            $current = $new;
        }
    }
}
?>
<h1 class="mb-4">Change Email</h1>

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

<form method="POST" action="change_email.php">
  <div class="mb-3">
    <label for="current" class="form-label">Current Email</label>
    <input type="text" id="current" class="form-control" value="<?= htmlspecialchars($current) ?>" disabled>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">New Email</label>
    <input type="email" name="email" id="email" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Update Email</button>
  <a href="profile.php" class="btn btn-link">← Back to Profile</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
