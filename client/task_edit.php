<?php
// client/task_edit.php
session_start();
// only clients
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$project_id = (int)($_GET['project_id'] ?? 0);
$task_id    = (int)($_GET['id']        ?? 0);
if (!$project_id || !$task_id) {
    header('Location: projects.php');
    exit;
}

// verify project ownership
$chk = $conn->prepare("SELECT id FROM projects WHERE id = ? AND client_id = ?");
$chk->bind_param("ii", $project_id, $_SESSION['user_id']);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
    echo "<div class='alert alert-danger'>Unauthorized.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// available currencies
$currencies = [
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'TND' => 'Tunisian Dinar',
    'GBP' => 'British Pound'
];

// fetch existing task
$stmt = $conn->prepare("
    SELECT title, amount, currency, description, deadline, status
      FROM tasks
     WHERE id = ? AND project_id = ?
");
$stmt->bind_param("ii", $task_id, $project_id);
$stmt->execute();
if (!($task = $stmt->get_result()->fetch_assoc())) {
    echo "<div class='alert alert-danger'>Task not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$stmt->close();

$errors      = [];
$title       = $task['title'];
$amount      = $task['amount'];
$currency    = $task['currency'];
$description = $task['description'];
$deadline    = $task['deadline'];
$status      = $task['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $amount      = $_POST['amount'];
    $currency    = $_POST['currency'] ?? 'USD';
    $description = trim($_POST['description']);
    $deadline    = $_POST['deadline'];
    $status      = $_POST['status'];

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($amount === '' || !is_numeric($amount) || $amount < 0) {
        $errors[] = 'A valid amount is required.';
    }
    if (!isset($currencies[$currency])) {
        $errors[] = 'Invalid currency selected.';
    }
    if (!in_array($status, ['pending','in_progress','completed','canceled'], true)) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {
        $upd = $conn->prepare("
            UPDATE tasks
               SET title       = ?,
                   amount      = ?,
                   currency    = ?,
                   description = ?,
                   deadline    = ?,
                   status      = ?
             WHERE id = ? AND project_id = ?
        ");
        $upd->bind_param(
            "sdssssii",
            $title,
            $amount,
            $currency,
            $description,
            $deadline,
            $status,
            $task_id,
            $project_id
        );
        if ($upd->execute()) {
            header("Location: project_tasks.php?project_id={$project_id}");
            exit;
        } else {
            $errors[] = 'DB error: ' . $upd->error;
        }
    }
}
?>

<h1 class="mb-4">Edit Task #<?= $task_id ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST">
  <div class="mb-3">
    <label class="form-label" for="title">Title</label>
    <input
      type="text"
      id="title"
      name="title"
      class="form-control"
      value="<?= htmlspecialchars($title) ?>"
      required
    >
  </div>

  <div class="mb-3">
    <label class="form-label" for="amount">Amount</label>
    <input
      type="number"
      step="0.01"
      min="0"
      id="amount"
      name="amount"
      class="form-control"
      value="<?= htmlspecialchars($amount) ?>"
      required
    >
  </div>

  <div class="mb-3">
    <label class="form-label" for="currency">Currency</label>
    <select id="currency" name="currency" class="form-select" required>
      <?php foreach ($currencies as $code => $label): ?>
        <option value="<?= $code ?>"
          <?= $currency === $code ? 'selected' : '' ?>>
          <?= "$code – $label" ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea
      id="description"
      name="description"
      class="form-control"
      rows="4"
    ><?= htmlspecialchars($description) ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label" for="deadline">Deadline</label>
    <input
      type="date"
      id="deadline"
      name="deadline"
      class="form-control"
      value="<?= htmlspecialchars($deadline) ?>"
    >
  </div>

  <div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <select id="status" name="status" class="form-select" required>
      <?php foreach (['pending','in_progress','completed','canceled'] as $st): ?>
        <option value="<?= $st ?>"
          <?= $status === $st ? 'selected' : '' ?>>
          <?= ucwords(str_replace('_',' ',$st)) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button class="btn btn-primary" type="submit">Save Changes</button>
  <a href="project_tasks.php?project_id=<?= $project_id ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
