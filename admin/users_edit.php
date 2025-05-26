<?php
// admin/users_edit.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include   __DIR__ . '/../includes/header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: users.php");
    exit;
}

// Fetch existing user data
$stmt = $conn->prepare("
    SELECT username, email, role
      FROM users
     WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
if (!$stmt->fetch()) {
    header("Location: users.php");
    exit;
}
$stmt->close();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'] ?? '';
    $password = $_POST['password'];

    // Validate inputs
    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (!in_array($role, ['client','freelancer','admin'], true)) {
        $errors[] = 'Invalid role selection.';
    }

    if (empty($errors)) {
        // Prepare update (with or without password change)
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users
                   SET username = ?, email = ?, role = ?, password = ?
                 WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $username, $email, $role, $hash, $id);
        } else {
            $stmt = $conn->prepare("
                UPDATE users
                   SET username = ?, email = ?, role = ?
                 WHERE id = ?
            ");
            $stmt->bind_param("sssi", $username, $email, $role, $id);
        }

        if ($stmt->execute()) {
            // Log the update event
            $adminId = $_SESSION['user_id'];
            $message = "Admin {$adminId} updated user {$id}";
            $logStmt = $conn->prepare("
                INSERT INTO logs (user_id, event_type, event_message)
                VALUES (?, 'user_updated', ?)
            ");
            $logStmt->bind_param('is', $adminId, $message);
            $logStmt->execute();
            $logStmt->close();

            header("Location: users.php");
            exit;
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<h1 class="mb-4 text-white">Edit User #<?= $id ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST"
      action="users_edit.php?id=<?= $id ?>"
      class="needs-validation"
      novalidate>
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
    <label for="password" class="form-label text-white">
      New Password <small>(leave blank to keep current)</small>
    </label>
    <input type="password"
           id="password"
           name="password"
           class="form-control">
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

  <button type="submit" class="btn btn-primary">Save Changes</button>
  <a href="users.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
