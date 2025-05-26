<?php
// client/project_add.php
session_start();

// Base URL (adjust if you mounted in a subfolder)
$base = '/freelancer_platform/';

// only clients may access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include  __DIR__ . '/../includes/header.php';

$errors      = [];
$title       = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = 'Please enter a project title.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO projects (client_id, title, description)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss",
            $_SESSION['user_id'],
            $title,
            $description
        );

        if ($stmt->execute()) {
            header("Location: {$base}client/projects.php");
            exit;
        } else {
            $errors[] = 'Database error: '.$stmt->error;
        }
    }
}
?>

<h1 class="mb-4">Create New Project</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="project_add.php" class="needs-validation" novalidate>
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

  <button type="submit" class="btn btn-primary">Create Project</button>
  <a href="projects.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
