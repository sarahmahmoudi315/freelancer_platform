<?php
// login.php

require __DIR__ . '/includes/db.php';
include  __DIR__ . '/includes/header.php';  // defines $base and renders <head> + nav

$error         = '';
$email         = '';
$selectedRole  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];
    $selectedRole = $_POST['role'] ?? '';

    // 1) Validate role
    if (! in_array($selectedRole, ['client','freelancer','admin'], true)) {
        $error = 'Please select a valid role.';
    } else {
        // 2) Fetch user by email
        $stmt = $conn->prepare("
            SELECT id, password, role
              FROM users
             WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hashed_password, $dbRole);
            $stmt->fetch();

            // 3) Role must match
            if ($selectedRole !== $dbRole) {
                $error = 'Selected role does not match your account.';
            }
            // 4) Then verify password
            elseif (! password_verify($password, $hashed_password)) {
                $error = 'Incorrect password.';
            }
            else {
                // 5) Success: set session + redirect
                $_SESSION['user_id'] = $id;
                $_SESSION['role']    = $dbRole;

                switch ($dbRole) {
                    case 'admin':
                        header("Location: {$base}admin/dashboard.php");
                        break;
                    case 'freelancer':
                        header("Location: {$base}freelancer/dashboard.php");
                        break;
                    default:
                        header("Location: {$base}client/dashboard.php");
                }
                exit;
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>

<h2 class="mb-4">Login to FreelanceApp</h2>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST"
      action="<?= $base ?>login.php"
      class="needs-validation"
      novalidate>
  <div class="form-group mb-3">
    <label for="email">Email address</label>
    <input
      type="email"
      class="form-control"
      id="email"
      name="email"
      required
      value="<?= htmlspecialchars($email) ?>"
    >
    <div class="invalid-feedback">Please enter your email.</div>
  </div>

  <div class="form-group mb-3">
    <label for="password">Password</label>
    <input
      type="password"
      class="form-control"
      id="password"
      name="password"
      required
    >
    <div class="invalid-feedback">Please enter your password.</div>
  </div>

  <div class="form-group mb-4">
    <label for="role">Login as</label>
    <select
      class="form-control"
      id="role"
      name="role"
      required
    >
      <option value="" disabled <?= $selectedRole === '' ? 'selected' : '' ?>>
        — Select role —
      </option>
      <option value="client"
        <?= $selectedRole === 'client' ? 'selected' : '' ?>>
        Client
      </option>
      <option value="freelancer"
        <?= $selectedRole === 'freelancer' ? 'selected' : '' ?>>
        Freelancer
      </option>
      <option value="admin"
        <?= $selectedRole === 'admin' ? 'selected' : '' ?>>
        Admin
      </option>
    </select>
    <div class="invalid-feedback">Please select your role.</div>
  </div>

  <button type="submit" class="btn btn-primary">Login</button>
  <a href="<?= $base ?>register.php" class="btn btn-link">Register</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
