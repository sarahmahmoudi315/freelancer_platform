<?php
// freelancer/portfolio_edit.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: {$base}freelancer/portfolio.php");
    exit;
}

// fetch current item
$stmt = $conn->prepare("
  SELECT title, description, image
    FROM portfolio
   WHERE id = ? AND freelancer_id = ?
");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$item) {
    echo "<div class='alert alert-danger'>Item not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$errors      = [];
$title       = $item['title'];
$description = $item['description'];
$currentImg  = $item['image'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    // if a new image was uploaded, handle it
    $newImage = $currentImg;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/portfolio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pf_', true) . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $errors[] = 'Failed to upload the new image.';
        } else {
            $newImage = "uploads/portfolio/{$filename}";
            // optionally, delete old file:
            @unlink(__DIR__ . "/../{$currentImg}");
        }
    }

    if (empty($errors)) {
        // update record
        $upd = $conn->prepare("
          UPDATE portfolio
             SET title       = ?,
                 description = ?,
                 image       = ?
           WHERE id = ? AND freelancer_id = ?
        ");
        $upd->bind_param(
          'sssii',
          $title,
          $description,
          $newImage,
          $id,
          $_SESSION['user_id']
        );
        $upd->execute();
        $upd->close();

        header("Location: {$base}freelancer/portfolio.php");
        exit;
    }
}
?>

<h1 class="mb-4">Edit Portfolio Item</h1>

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
    <label class="form-label">Current Image</label><br>
    <img src="<?= htmlspecialchars($base . $currentImg) ?>" style="max-width:200px;">
  </div>

  <div class="mb-3">
    <label class="form-label">Replace Image</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    <small class="text-muted">Leave empty to keep existing image</small>
  </div>

  <button class="btn btn-primary">Save Changes</button>
  <a href="<?= $base ?>freelancer/portfolio.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
