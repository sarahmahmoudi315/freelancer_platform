<?php
// profile.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

$base = '/freelancer_platform/';
$uid  = $_SESSION['user_id'];

// 1) Fetch core user + freelancer data
$stmt = $conn->prepare("
    SELECT
      u.username,
      u.display_name,
      u.email,
      u.role,
      u.created_at,
      COALESCE(f.location,   '') AS location,
      COALESCE(f.experience, '') AS experience,
      COALESCE(f.hourly_rate,'') AS hourly_rate,
      COALESCE(f.bio,        '') AS bio
    FROM users u
    LEFT JOIN freelancers f  
      ON f.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2) Fetch this user’s skills
$sk = $conn->prepare("
  SELECT s.name
    FROM skills s
    JOIN user_skills us ON us.skill_id = s.id
   WHERE us.user_id = ?
   ORDER BY s.name
");
$sk->bind_param("i", $uid);
$sk->execute();
$skills = array_column(
  $sk->get_result()->fetch_all(MYSQLI_ASSOC),
  'name'
);
$sk->close();
?>
<h1 class="mb-4">My Profile</h1>

<?php if (!empty($_GET['updated'])): ?>
  <div class="alert alert-success">✅ Profile updated!</div>
<?php endif; ?>

<div class="alert alert-light p-4">
  <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
  <p><strong>Display Name:</strong> <?= htmlspecialchars($user['display_name']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
  <p><strong>Role:</strong> <?= ucfirst(htmlspecialchars($user['role'])) ?></p>
  <p><strong>Joined:</strong> <?= htmlspecialchars($user['created_at']) ?></p>

  <?php if ($user['role'] === 'freelancer'): ?>
    <hr>
    <p><strong>Location:</strong> <?= htmlspecialchars($user['location']) ?></p>
    <p><strong>Experience:</strong> <?= htmlspecialchars($user['experience']) ?> yrs</p>
    <p><strong>Hourly Rate:</strong> $<?= htmlspecialchars($user['hourly_rate']) ?>/hr</p>
    <p><strong>Bio:</strong><br><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
    <hr>
    <p>
      <strong>Skills:</strong>
      <?= $skills
          ? implode(', ', array_map('htmlspecialchars', $skills))
          : '— none selected —' ?>
    </p>
  <?php endif; ?>
</div>

<p>
  <a href="<?= $base ?>profile_edit.php"    class="btn btn-primary">Edit Profile</a>
  <a href="<?= $base ?>change_email.php"    class="btn btn-secondary">Change Email</a>
  <a href="<?= $base ?>change_password.php" class="btn btn-secondary">Change Password</a>
  <?php if ($user['role'] === 'freelancer'): ?>
    <a href="<?= $base ?>freelancer/portfolio.php" class="btn btn-info">My Portfolio</a>
  <?php endif; ?>
  <a href="<?= $base ?>dashboard.php"       class="btn btn-link">← Back to Dashboard</a>
</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
