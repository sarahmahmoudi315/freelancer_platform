<?php
// client/project_edit.php
session_start();

// only clients may access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: projects.php');
    exit;
}

// fetch the existing project, ensure it belongs to this client
$stmt = $conn->prepare("
    SELECT title, description, client_id
      FROM projects
     WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($title, $description, $ownerId);
if (!$stmt->fetch() || $ownerId !== $_SESSION['user_id']) {
    // either not found or not owned by this client
    $stmt->close();
    header('Location: projects.php');
    exit;
}
$stmt->close();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = 'Please enter a project title.';
    }

    if (empty($errors)) {
        $upd = $conn->prepare("
            UPDATE projects
               SET title = ?, description = ?
             WHERE id = ?
        ");
        $upd->bind_param("ssi", $title, $description, $id);
        if ($upd->execute()) {
            header('Location: projects.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $upd->error;
        }
    }
}
?>

<h1 class="mb-4">Edit Project</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="project_edit.php?id=<?= $id ?>" class="needs-validation" novalidate>
  <div class="mb-3">
    <label for="title" class="form-label">Project Title</label>
    <input
      type="text"
      id="title"
      name="title"
      class="form-control"
      required
      value="<?= htmlspecialchars($title) ?>"
    >
    <div class="invalid-feedback">Title is required.</div>
  </div>

  <div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea
      id="description"
      name="description"
      rows="5"
      class="form-control"
    ><?= htmlspecialchars($description) ?></textarea>
  </div>

  <button type="submit" class="btn btn-primary">Save Changes</button>
  <a href="projects.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
