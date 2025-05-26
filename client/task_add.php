<?php
// client/task_add.php
session_start();
// only clients
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$project_id = (int)($_GET['project_id'] ?? 0);
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

// verify that project belongs to this client
$chk = $conn->prepare("SELECT id FROM projects WHERE id = ? AND client_id = ?");
$chk->bind_param("ii", $project_id, $_SESSION['user_id']);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
    echo "<div class='alert alert-danger'>Unauthorized or project not found.</div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// fetch all skills for multi-select
$allSkills = $conn->query("SELECT id,name FROM skills ORDER BY name");

// available currencies
$currencies = [
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'TND' => 'Tunisian Dinar',
    'GBP' => 'British Pound'
];

$errors      = [];
$title       = '';
$description = '';
$deadline    = '';
$amount      = '';
$currency    = 'USD';
$selected    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline    = $_POST['deadline'];
    $amount      = $_POST['amount'];
    $currency    = $_POST['currency'] ?? 'USD';
    $selected    = $_POST['skills'] ?? [];

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($amount === '' || !is_numeric($amount) || $amount < 0) {
        $errors[] = 'A valid amount is required.';
    }
    if (!isset($currencies[$currency])) {
        $errors[] = 'Invalid currency selected.';
    }
    if (empty($selected)) {
        $errors[] = 'At least one required skill must be selected.';
    }

    if (empty($errors)) {
        // 1) Insert the new task (with amount + currency)
        $stmt = $conn->prepare("
            INSERT INTO tasks
              (project_id, title, amount, currency, description, deadline, status)
            VALUES (?,?,?,?,?,'pending')
        ");
        $stmt->bind_param(
            "isdsss",
            $project_id,
            $title,
            $amount,
            $currency,
            $description,
            $deadline
        );
        $stmt->execute();
        $task_id = $stmt->insert_id;
        $stmt->close();

        // 2) Link skills
        $ins = $conn->prepare("
            INSERT INTO task_skills (task_id, skill_id) VALUES (?,?)
        ");
        foreach ($selected as $sid) {
            $sid = (int)$sid;
            $ins->bind_param("ii", $task_id, $sid);
            $ins->execute();
        }
        $ins->close();

        header("Location: project_tasks.php?project_id={$project_id}");
        exit;
    }
}
?>

<h1 class="mb-4">Add Task</h1>

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
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control"
           value="<?= htmlspecialchars($title) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Amount</label>
    <input type="number" step="0.01" min="0" name="amount"
           class="form-control"
           value="<?= htmlspecialchars($amount) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Currency</label>
    <select name="currency" class="form-select" required>
      <?php foreach ($currencies as $code => $label): ?>
        <option value="<?= $code ?>"
          <?= $code === $currency ? 'selected' : '' ?>>
          <?= "$code – $label" ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Required Skills</label>
    <select name="skills[]" class="form-select" multiple size="6" required>
      <?php while ($row = $allSkills->fetch_assoc()): ?>
        <option value="<?= $row['id'] ?>"
          <?= in_array($row['id'], $selected) ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
    <small class="form-text text-muted">(Hold Ctrl/Cmd to select multiple)</small>
  </div>

  <div class="mb-3">
    <label class="form-label">Deadline</label>
    <input type="date" name="deadline" class="form-control"
           value="<?= htmlspecialchars($deadline) ?>">
  </div>

  <button class="btn btn-success">Create Task</button>
  <a href="project_tasks.php?project_id=<?= $project_id ?>" class="btn btn-secondary">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
