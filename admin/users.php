<?php
// admin/users.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include   __DIR__ . '/../includes/header.php';

// Fetch all users
$result = $conn->query("
    SELECT id, username, email, role, created_at
      FROM users
     ORDER BY created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center py-3">
  <h1 class="text-white">Manage All Users</h1>
  <a href="users_add.php" class="btn btn-success">+ Add New User</a>
</div>

<table class="table table-dark table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Email</th>
      <th>Role</th>
      <th>Joined</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['id']) ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= htmlspecialchars($row['email']) ?></td>
      <td><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
      <td><?= htmlspecialchars($row['created_at']) ?></td>
      <td>
        <a href="users_edit.php?id=<?= $row['id'] ?>"
           class="btn btn-sm btn-primary">
          Edit
        </a>
        <a href="users_delete.php?id=<?= $row['id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete user #<?= $row['id'] ?>?');">
          Delete
        </a>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
