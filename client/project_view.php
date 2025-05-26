<?php
// client/project_view.php
session_start();

// Base URL for links & redirects
$base = '/freelancer_platform/';

// only clients
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include  __DIR__ . '/../includes/header.php';

$project_id = (int)($_GET['id'] ?? 0);
if (!$project_id) {
    header("Location: {$base}client/projects.php");
    exit;
}

// fetch project and verify ownership
$stmt = $conn->prepare("
  SELECT title, description, created_at
    FROM projects
   WHERE id = ? AND client_id = ?
");
$stmt->bind_param("ii", $project_id, $_SESSION['user_id']);
$stmt->execute();
$proj = $stmt->get_result()->fetch_assoc();

if (!$proj) {
    echo "<div class='alert alert-danger'>Project not found or unauthorized.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<div class="p-5 text-white rounded" style="background: linear-gradient(135deg,#667eea,#764ba2)">
  <h1><?= htmlspecialchars($proj['title']) ?></h1>
  <p><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
  <p><em>Created at <?= htmlspecialchars($proj['created_at']) ?></em></p>

  <a href="<?= $base ?>client/project_edit.php?id=<?= $project_id ?>" class="btn btn-light">
    Edit Project
  </a>
  <a href="<?= $base ?>client/projects.php" class="btn btn-link text-white">
    ← Back to Projects
  </a>
  <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $project_id ?>" class="btn btn-info">
    View Tasks
  </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
