<?php
// client/task_assign.php
session_start();
$base = '/freelancer_platform/';

// only clients
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: {$base}login.php");
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

// validate IDs
$project_id = (int)($_GET['project_id'] ?? 0);
$task_id    = (int)($_GET['task_id']    ?? 0);
if (!$project_id || !$task_id) {
    header("Location: {$base}client/projects.php");
    exit;
}

// confirm ownership
$chk = $conn->prepare("
    SELECT t.title AS task_title, p.title AS project_title
      FROM tasks t
      JOIN projects p ON p.id = t.project_id
     WHERE t.id = ? AND p.id = ? AND p.client_id = ?
");
$chk->bind_param("iii", $task_id, $project_id, $_SESSION['user_id']);
$chk->execute();
$task = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$task) {
    echo "<div class='alert alert-danger'>Task not found or unauthorized.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// get required skill IDs
$got = $conn->prepare("SELECT skill_id FROM task_skills WHERE task_id = ?");
$got->bind_param("i", $task_id);
$got->execute();
$skill_ids = array_column(
    $got->get_result()->fetch_all(MYSQLI_ASSOC),
    'skill_id'
);
$got->close();

// when the form posts: insert a request record (leaving the task un-assigned until approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['freelancer_id'])) {
    $fid = (int)$_POST['freelancer_id'];

    // 1) insert into requests
    $ins = $conn->prepare("
      INSERT INTO requests
        (task_id, freelancer_id, client_id, project_id, detail)
      VALUES (?,?,?,?,?)
    ");
    $detail = "You have a new assignment for task #{$task_id}";
    $ins->bind_param(
      "iiiis",
      $task_id,
      $fid,
      $_SESSION['user_id'],
      $project_id,
      $detail
    );
    $ins->execute();
    $ins->close();

    // 2) redirect back
    header("Location: {$base}client/project_tasks.php?project_id={$project_id}");
    exit;
}

// fetch matching freelancers (as before)…
$freelancers = [];
if (count($skill_ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($skill_ids), '?'));
    $types        = str_repeat('i', count($skill_ids));
    $sql = "
      SELECT DISTINCT u.id,u.username,f.experience,f.hourly_rate
        FROM users u
        JOIN freelancers f  ON f.user_id  = u.id
        JOIN user_skills us ON us.user_id = u.id
       WHERE u.role = 'freelancer'
         AND us.skill_id IN ({$placeholders})
       ORDER BY f.experience DESC, f.hourly_rate ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$skill_ids);
    $stmt->execute();
    $freelancers = $stmt->get_result();
    $stmt->close();
}
?>

<h1 class="mb-4">Assign Task: <?= htmlspecialchars($task['task_title']) ?></h1>
<p><strong>Project:</strong> <?= htmlspecialchars($task['project_title']) ?></p>

<?php if (empty($skill_ids)): ?>
  <div class="alert alert-warning">
    This task has no skills defined. Please edit the task and select at least one required skill.
  </div>
  <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $project_id ?>"
     class="btn btn-link">← Back to Tasks</a>

<?php elseif ($freelancers->num_rows === 0): ?>
  <div class="alert alert-warning">
    No freelancers found with the required skills.
  </div>
  <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $project_id ?>"
     class="btn btn-link">← Back to Tasks</a>

<?php else: ?>
  <form method="POST" class="mb-4">
    <div class="mb-3">
      <label for="freelancer_id" class="form-label">Choose Freelancer</label>
      <select id="freelancer_id" name="freelancer_id" class="form-select" required>
        <option value="" disabled selected>— Select a freelancer —</option>
        <?php while ($f = $freelancers->fetch_assoc()): ?>
          <option value="<?= $f['id'] ?>">
            <?= htmlspecialchars($f['username']) ?>
            (<?= htmlspecialchars($f['experience']) ?> yrs,
             $<?= number_format($f['hourly_rate'],2) ?>/hr)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Assign</button>
    <a href="<?= $base ?>client/project_tasks.php?project_id=<?= $project_id ?>"
       class="btn btn-link">← Back to Tasks</a>
  </form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
