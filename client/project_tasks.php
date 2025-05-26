<?php
// client/project_tasks.php
session_start();

// Base URL for links & redirects
$base = '/freelancer_platform/';

// only clients
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$project_id = (int)($_GET['project_id'] ?? 0);
if (!$project_id) {
    header("Location: {$base}client/projects.php");
    exit;
}

// 1) Verify this project belongs to current client
$chk = $conn->prepare("
    SELECT id, title 
      FROM projects 
     WHERE id = ? 
       AND client_id = ?
");
$chk->bind_param("ii", $project_id, $_SESSION['user_id']);
$chk->execute();
$proj = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$proj) {
    echo "<div class='alert alert-danger'>Unauthorized or project not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// 2) Handle deletion ?
if (isset($_GET['delete'])) {
    $task_id = (int)$_GET['delete'];
    $del = $conn->prepare("
        DELETE FROM tasks 
         WHERE id = ? 
           AND project_id = ?
    ");
    $del->bind_param("ii", $task_id, $project_id);
    $del->execute();
    $del->close();
    header("Location: {$base}client/project_tasks.php?project_id={$project_id}");
    exit;
}

// 3) Fetch all tasks + their skills
$stmt = $conn->prepare("
    SELECT
      t.id,
      t.title,
      t.deadline,
      t.status,
      IFNULL(GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', '), '') AS skills
    FROM tasks t
    LEFT JOIN task_skills ts ON ts.task_id = t.id
    LEFT JOIN skills       s  ON s.id        = ts.skill_id
    WHERE t.project_id = ?
    GROUP BY t.id
    ORDER BY t.id DESC
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>

<h1 class="mb-4"><?= htmlspecialchars($proj['title']) ?> — Tasks</h1>

<p>
  <a href="<?= $base ?>client/projects.php" class="btn btn-link">← Back to Projects</a>
  <a href="<?= $base ?>client/task_add.php?project_id=<?= $project_id ?>" class="btn btn-primary">
    + Add Task
  </a>
</p>

<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Title</th>
      <th>Skills</th>
      <th>Deadline</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($t = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $t['id'] ?></td>
        <td><?= htmlspecialchars($t['title']) ?></td>
        <td>
          <?= $t['skills']
               ? htmlspecialchars($t['skills'])
               : '<span class="text-muted">—</span>' ?>
        </td>
        <td><?= $t['deadline'] ? htmlspecialchars($t['deadline']) : '—' ?></td>
        <td><?= ucwords(str_replace('_',' ',htmlspecialchars($t['status']))) ?></td>
        <td>
          <a href="<?= $base ?>client/task_edit.php?project_id=<?= $project_id ?>&id=<?= $t['id'] ?>"
             class="btn btn-sm btn-secondary">Edit</a>
          <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $project_id ?>&delete=<?= $t['id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('Really delete this task?')">Delete</a>
          <a href="<?= $base ?>client/task_assign.php?project_id=<?= $project_id ?>&task_id=<?= $t['id'] ?>"
             class="btn btn-sm btn-primary">Assign</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
