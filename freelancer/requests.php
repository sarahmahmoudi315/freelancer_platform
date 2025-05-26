<?php
// freelancer/requests.php
session_start();
$base = '/freelancer_platform/';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// 1) Handle Accept / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $rid    = (int) $_POST['id'];
    $status = $_POST['action'] === 'accept' ? 'accepted' : 'refused';

    // a) Update the request status
    $u = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $u->bind_param("si", $status, $rid);
    $u->execute();
    $u->close();

    // b) If accepted, mark the task in_progress and create a pending payment
    if ($status === 'accepted') {
        // update task status
        $v = $conn->prepare("
          UPDATE tasks t
          JOIN requests r ON r.task_id = t.id
          SET t.status = 'in_progress'
          WHERE r.id = ?
        ");
        $v->bind_param("i", $rid);
        $v->execute();
        $v->close();

        // insert pending payment record, pulling amount + currency + freelancer
        $p = $conn->prepare("
          INSERT INTO payments
            (task_id, freelancer_id, amount, status, currency)
          SELECT
            r.task_id,
            t.freelancer_id,
            t.amount,
            'pending',
            t.currency
          FROM requests r
          JOIN tasks    t ON t.id = r.task_id
          WHERE r.id = ?
        ");
        $p->bind_param("i", $rid);
        $p->execute();
        $p->close();
    }

    header("Location: {$base}freelancer/requests.php");
    exit;
}

// 2) Fetch all requests for this freelancer
$stmt = $conn->prepare("
    SELECT
      r.id,
      p.title           AS project,
      u.username        AS client,
      r.detail          AS details,
      r.status,
      r.created_at
    FROM requests r
    JOIN users    u ON u.id       = r.client_id
    JOIN projects p ON p.id       = r.project_id
    WHERE r.freelancer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
?>

<h1 class="mb-4">Manage Requests</h1>

<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th><th>Project</th><th>From</th>
      <th>Details</th><th>Status</th><th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($r = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><?= htmlspecialchars($r['project']) ?></td>
        <td><?= htmlspecialchars($r['client']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['details'])) ?></td>
        <td><?= ucfirst($r['status']) ?></td>
        <td>
          <?php if ($r['status'] === 'pending'): ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button name="action" value="accept" class="btn btn-sm btn-success">
                Accept
              </button>
            </form>
            <form method="POST" class="d-inline">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button name="action" value="reject" class="btn btn-sm btn-danger">
                Reject
              </button>
            </form>
          <?php elseif ($r['status'] === 'accepted'): ?>
            <span class="badge bg-info">In Progress</span>
          <?php else: ?>
            <span class="badge bg-secondary"><?= ucfirst($r['status']) ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
