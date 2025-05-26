<?php
// register.php

require __DIR__ . '/includes/db.php';
include  __DIR__ . '/includes/header.php';

$errors   = [];
$username = '';
$email    = '';
$role     = 'client';
$success  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize inputs
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'] ?? 'client';

    // 1) Basic validation
    if ($username === '') {
        $errors[] = 'Please enter a username.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    // allow all three roles
    if (!in_array($role, ['client','freelancer','admin'], true)) {
        $errors[] = 'Invalid role selection.';
    }

    if (empty($errors)) {
        // 2) Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with that email already exists.';
        } else {
            // 3) Insert new user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $username, $email, $hash, $role);
            if ($stmt->execute()) {
                $success = 'Registration successful! <a href="login.php">Login here</a>.';
                // Clear form values
                $username = $email = '';
                $role     = 'client';
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}
?>

<h2 class="mb-4">Register an Account</h2>

<?php if ($success): ?>
  <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!$success): ?>
<form method="POST" action="register.php" class="needs-validation" novalidate>
  <div class="form-group mb-3">
    <label for="username">Username</label>
    <input
      type="text"
      class="form-control"
      id="username"
      name="username"
      required
      value="<?= htmlspecialchars($username) ?>"
    >
    <div class="invalid-feedback">Enter a username.</div>
  </div>

  <div class="form-group mb-3">
    <label for="email">Email address</label>
    <input
      type="email"
      class="form-control"
      id="email"
      name="email"
      required
      value="<?= htmlspecialchars($email) ?>"
    >
    <div class="invalid-feedback">Enter a valid email.</div>
  </div>

  <div class="form-group mb-3">
    <label for="password">Password</label>
    <input
      type="password"
      class="form-control"
      id="password"
      name="password"
      required
    >
    <div class="invalid-feedback">Enter a password (min 6 characters).</div>
  </div>

  <div class="form-group mb-3">
    <label for="confirm_password">Confirm Password</label>
    <input
      type="password"
      class="form-control"
      id="confirm_password"
      name="confirm_password"
      required
    >
    <div class="invalid-feedback">Passwords must match.</div>
  </div>

  <div class="form-group mb-4">
    <label for="role">Register As</label>
    <select class="form-control" id="role" name="role">
      <option value="client"    <?= $role==='client'    ? 'selected' : '' ?>>Client</option>
      <option value="freelancer" <?= $role==='freelancer' ? 'selected' : '' ?>>Freelancer</option>
      <option value="admin"      <?= $role==='admin'      ? 'selected' : '' ?>>Admin</option>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">Register</button>
  <a href="login.php" class="btn btn-link">Already have an account?</a>
</form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
