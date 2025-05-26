<?php
// freelancer/portfolio.php
session_start();

// Only freelancers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include  __DIR__ . '/../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("
        DELETE FROM portfolio
         WHERE id = ?
           AND freelancer_id = ?
    ");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: portfolio.php');
    exit;
}

// Fetch portfolio items for this freelancer
$stmt = $conn->prepare("
    SELECT id, title, description, created_at
      FROM portfolio
     WHERE freelancer_id = ?
     ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>

<h1 class="mb-4">Your Portfolio</h1>
<p>
  <a href="<?= $base ?>freelancer/portfolio_add.php" class="btn btn-primary">
    + Add New Item
  </a>
</p>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Title</th>
      <th>Description</th>
      <th>When</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td>
          <a
            href="<?= $base ?>freelancer/portfolio_edit.php?id=<?= $row['id'] ?>"
            class="btn btn-sm btn-secondary"
          >
            Edit
          </a>
          <a
            href="<?= $base ?>freelancer/portfolio.php?delete=<?= $row['id'] ?>"
            class="btn btn-sm btn-danger"
            onclick="return confirm('Delete this item?')"
          >
            Delete
          </a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
