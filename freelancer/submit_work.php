<?php
// freelancer/submit_work.php
session_start();
if (!isset($_SESSION['user_id'])||$_SESSION['role']!=='freelancer') {
  header('Location: ../login.php'); exit;
}
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// mark in-progress requests as completed
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['id'])) {
  $id = (int)$_POST['id'];
  $stmt = $conn->prepare("UPDATE requests SET status='completed' WHERE id=? AND freelancer_id=?");
  $stmt->bind_param("ii",$id,$_SESSION['user_id']);
  $stmt->execute();
  header('Location: submit_work.php'); exit;
}

// list accepted (in progress)
$stmt = $conn->prepare("
  SELECT r.id,p.title,r.created_at
    FROM requests r
    JOIN projects p ON p.id=r.project_id
   WHERE r.freelancer_id=? AND r.status='accepted'
");
$stmt->bind_param("i",$_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>
<h1 class="mb-4">Submit Completed Work</h1>
<table class="table table-striped">
<thead><tr><th>#</th><th>Project</th><th>Requested</th><th>Action</th></tr></thead>
<tbody>
  <?php while($r=$res->fetch_assoc()): ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td><?= $r['created_at'] ?></td>
    <td>
      <form method="POST" style="display:inline">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-sm btn-success">Mark Completed</button>
      </form>
    </td>
  </tr>
  <?php endwhile; ?>
</tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
