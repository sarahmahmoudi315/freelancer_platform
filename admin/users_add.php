<?php
// admin/users_add.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include   __DIR__ . '/../includes/header.php';

$errors   = [];
$username = '';
$email    = '';
$role     = 'client';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Validate inputs
    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if (!in_array($role, ['client','freelancer','admin'], true)) {
        $errors[] = 'Invalid role selection.';
    }

    if (empty($errors)) {
        // Insert new user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $username, $email, $hash, $role);

        if ($stmt->execute()) {
            // Log the creation event
            $adminId   = $_SESSION['user_id'];
            $newUserId = $stmt->insert_id;
            $message   = "Admin {$adminId} created user {$newUserId}";
            $logStmt   = $conn->prepare("
                INSERT INTO logs (user_id, event_type, event_message)
                VALUES (?, 'user_created', ?)
            ");
            $logStmt->bind_param('is', $adminId, $message);
            $logStmt->execute();

            header("Location: users.php");
            exit;
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
    }
}
?>

<h1 class="mb-4 text-white">Add New User</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="users_add.php" class="needs-validation" novalidate>
  <div class="mb-3">
    <label for="username" class="form-label text-white">Username</label>
    <input type="text"
           id="username"
           name="username"
           class="form-control"
           required
           value="<?= htmlspecialchars($username) ?>">
  </div>
  <div class="mb-3">
    <label for="email" class="form-label text-white">Email</label>
    <input type="email"
           id="email"
           name="email"
           class="form-control"
           required
           value="<?= htmlspecialchars($email) ?>">
  </div>
  <div class="mb-3">
    <label for="password" class="form-label text-white">Password</label>
    <input type="password"
           id="password"
           name="password"
           class="form-control"
           required>
  </div>
  <div class="mb-3">
    <label for="role" class="form-label text-white">Role</label>
    <select id="role"
            name="role"
            class="form-select"
            required>
      <option value="client"    <?= $role === 'client'    ? 'selected' : '' ?>>Client</option>
      <option value="freelancer" <?= $role === 'freelancer' ? 'selected' : '' ?>>Freelancer</option>
      <option value="admin"      <?= $role === 'admin'      ? 'selected' : '' ?>>Admin</option>
    </select>
  </div>
  <button type="submit" class="btn btn-success">Create User</button>
  <a href="users.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
