<?php
// client/freelancer_portfolio.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$fid = (int)($_GET['id'] ?? 0);
if (!$fid) {
    echo "<div class='alert alert-danger'>Missing freelancer ID.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $conn->prepare("
  SELECT title, description, link, created_at
    FROM portfolio
   WHERE freelancer_id = ?
   ORDER BY created_at DESC
");
$stmt->bind_param("i", $fid);
$stmt->execute();
$res = $stmt->get_result();
?>

<h1 class="mb-4">Portfolio of Freelancer #<?= $fid ?></h1>
<table class="table table-striped">
  <thead>
    <tr><th>Title</th><th>Description</th><th>Link</th><th>When</th></tr>
  </thead>
  <tbody>
    <?php while ($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
        <td>
          <?php if ($row['link']): ?>
            <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank">View</a>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
