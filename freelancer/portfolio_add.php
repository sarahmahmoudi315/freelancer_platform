<?php
// freelancer/portfolio_add.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$errors      = [];
$title       = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (empty($_FILES['image']['name'])) {
        $errors[] = 'Please choose an image to upload.';
    }

    if (empty($errors)) {
        // handle the upload
        $uploadDir = __DIR__ . '/../uploads/portfolio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pf_', true) . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $errors[] = 'Failed to upload the image.';
        } else {
            // insert into DB
            $stmt = $conn->prepare("
              INSERT INTO portfolio
                (freelancer_id, title, description, image)
              VALUES (?,?,?,?)
            ");
            $relPath = "uploads/portfolio/{$filename}";
            $stmt->bind_param(
              'isss',
              $_SESSION['user_id'],
              $title,
              $description,
              $relPath
            );
            $stmt->execute();
            $stmt->close();

            header("Location: {$base}freelancer/portfolio.php");
            exit;
        }
    }
}
?>

<h1 class="mb-4">Add Portfolio Item</h1>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input name="title" class="form-control"
           value="<?= htmlspecialchars($title) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Image</label>
    <input type="file" name="image" class="form-control" accept="image/*" required>
  </div>

  <button class="btn btn-success">Save Item</button>
  <a href="<?= $base ?>freelancer/portfolio.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
