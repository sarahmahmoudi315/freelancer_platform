<?php
// client/projects.php

session_start();

// Base URL for redirects & links
$base = '/freelancer_platform/';

// Only clients allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include  __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Manage Your Projects</h1>
<p>
  <a href="<?= $base ?>client/project_add.php" class="btn btn-primary">
    + Create New Project
  </a>
</p>

<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Status</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $stmt = $conn->prepare("
      SELECT id, title, status, created_at
        FROM projects
       WHERE client_id = ?
       ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($proj = $res->fetch_assoc()):
    ?>
      <tr>
        <td><?= htmlspecialchars($proj['id']) ?></td>
        <td><?= htmlspecialchars($proj['title']) ?></td>
        <td><?= ucfirst(htmlspecialchars($proj['status'])) ?></td>
        <td><?= htmlspecialchars($proj['created_at']) ?></td>
        <td>
          <a href="<?= $base ?>client/project_edit.php?id=<?= $proj['id'] ?>"
             class="btn btn-sm btn-secondary">Edit</a>

          <a href="<?= $base ?>client/project_delete.php?id=<?= $proj['id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('Delete this project?')">
            Delete
          </a>

          <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $proj['id'] ?>"
             class="btn btn-sm btn-info">
            View Tasks
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
