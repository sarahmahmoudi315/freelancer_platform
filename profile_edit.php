<?php
// profile_edit.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/includes/db.php';
include   __DIR__ . '/includes/header.php';

$base     = '/freelancer_platform/';
$uid      = $_SESSION['user_id'];
$errors   = [];
$warnings = [];

// 1) Fetch current user & freelancer info
$stmt = $conn->prepare("
    SELECT
      u.display_name,
      u.email,
      u.bio,
      COALESCE(f.location,   '') AS location,
      COALESCE(f.experience, '') AS experience,
      COALESCE(f.hourly_rate,'') AS hourly_rate
    FROM users u
    LEFT JOIN freelancers f ON f.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i",$uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2) Fetch all skills for the multi-select
$allSkills = $conn->query("SELECT id,name FROM skills ORDER BY name");

// 3) Fetch your existing skill IDs
$got = $conn->prepare("SELECT skill_id FROM user_skills WHERE user_id = ?");
$got->bind_param("i",$uid);
$got->execute();
$have = array_column(
  $got->get_result()->fetch_all(MYSQLI_ASSOC),
  'skill_id'
);
$got->close();

// 4) If the form was submitted, process it
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // pull in all the form fields, using ?? '' to avoid undefined notices
    $display    = trim($_POST['display_name']  ?? '');
    $email      = trim($_POST['email']         ?? '');
    $bio        = trim($_POST['bio']           ?? '');
    $location   = trim($_POST['location']      ?? '');
    $experience = trim($_POST['experience']    ?? '');
    $hourly     = trim($_POST['hourly_rate']   ?? '');
    $selected   = $_POST['skills'] ?? [];    // array of existing skill_ids
    $newRaw     = trim($_POST['new_skills']    ?? '');

    // basic validation
    if ($display==='') {
        $errors[] = 'Display name cannot be empty.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        // A) Update users table
        $u = $conn->prepare("UPDATE users SET display_name=?,email=?,bio=? WHERE id=?");
        $u->bind_param("sssi",$display,$email,$bio,$uid);
        $u->execute();
        $u->close();

        // B) Upsert freelancers table
        $f = $conn->prepare("
          INSERT INTO freelancers
            (user_id,location,experience,hourly_rate,bio)
          VALUES (?,?,?,?,?)
          ON DUPLICATE KEY UPDATE
            location=VALUES(location),
            experience=VALUES(experience),
            hourly_rate=VALUES(hourly_rate),
            bio=VALUES(bio)
        ");
        $f->bind_param("issss",$uid,$location,$experience,$hourly,$bio);
        $f->execute();
        $f->close();

        // --- DEDUPE NEW SKILLS ---

        // Build lowercase master list
        $masterNamesRes = $conn->query("SELECT name FROM skills");
        $masterNames    = array_map('mb_strtolower',
            array_column($masterNamesRes->fetch_all(MYSQLI_ASSOC),'name')
        );

        // Build lowercase your current names
        if (!empty($have)) {
            $in  = implode(',',array_map('intval',$have));
            $usr = $conn->query("SELECT name FROM skills WHERE id IN ($in)");
            $yourNames = array_map('mb_strtolower',
                array_column($usr->fetch_all(MYSQLI_ASSOC),'name')
            );
        } else {
            $yourNames = [];
        }

        // Start final union of IDs with whatever you already had + re-selected
        $final = array_unique(array_merge($have, array_map('intval',$selected)));

        // Parse new skill names
        if ($newRaw!=='') {
            $names = array_unique(
              array_filter(array_map('trim',explode(',',$newRaw)))
            );
            foreach ($names as $nm) {
                $low = mb_strtolower($nm);
                if (in_array($low,$masterNames,true)) {
                    // Already in master list?
                    // find its ID
                    $c = $conn->prepare("SELECT id FROM skills WHERE name = ?");
                    $c->bind_param("s",$nm);
                    $c->execute();
                    $row = $c->get_result()->fetch_assoc();
                    $sid = $row['id'];
                    $c->close();

                    if (in_array($sid,$final,true)) {
                        $warnings[] = "You already have “{$nm}” on your profile.";
                    } else {
                        $warnings[] = "Skill “{$nm}” already exists—please select it from above.";
                    }
                } else {
                    // Truly new: insert and grab ID
                    $i = $conn->prepare("INSERT INTO skills (name) VALUES (?)");
                    $i->bind_param("s",$nm);
                    $i->execute();
                    $sid = $conn->insert_id;
                    $i->close();
                    $final[] = $sid;
                }
            }
        }

        // E) Rewrite your user_skills to match $final
        $conn->query("DELETE FROM user_skills WHERE user_id = $uid");
        $ins = $conn->prepare("INSERT INTO user_skills (user_id,skill_id) VALUES (?,?)");
        foreach ($final as $sid) {
            $ins->bind_param("ii",$uid,$sid);
            $ins->execute();
        }
        $ins->close();

        // Redirect back up with a flag
        header("Location: profile.php?updated=1");
        exit;
    }
}

?>
<h1 class="mb-4">Edit Profile</h1>

<?php if ($warnings): ?>
  <div class="alert alert-warning"><ul class="mb-0">
    <?php foreach ($warnings as $w): ?>
      <li><?= htmlspecialchars($w) ?></li>
    <?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars($e) ?></li>
    <?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<form method="POST" action="profile_edit.php">
  <div class="mb-3">
    <label class="form-label">Display Name</label>
    <input name="display_name" class="form-control" required
      value="<?= htmlspecialchars($user['display_name']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" required
      value="<?= htmlspecialchars($user['email']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Bio</label>
    <textarea name="bio" rows="3" class="form-control"><?= htmlspecialchars($user['bio']) ?></textarea>
  </div>

  <?php if (isset($user['location'])): // freelancer-only ?>
    <hr><h5>Freelancer Details</h5>

    <div class="mb-3">
      <label class="form-label">Location</label>
      <input name="location" class="form-control"
        value="<?= htmlspecialchars($user['location']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Experience</label>
      <input name="experience" class="form-control"
        value="<?= htmlspecialchars($user['experience']) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Hourly Rate ($)</label>
      <input name="hourly_rate" type="number" step="0.01" class="form-control"
        value="<?= htmlspecialchars($user['hourly_rate']) ?>">
    </div>

    <hr><h5>Your Skills</h5>
    <div class="mb-3">
      <label class="form-label">Select Existing Skills</label>
      <select name="skills[]" class="form-select" multiple size="6">
        <?php while($row = $allSkills->fetch_assoc()): ?>
          <option value="<?=$row['id']?>"
            <?= in_array($row['id'],$have) ? 'selected' : '' ?>>
            <?= htmlspecialchars($row['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <small class="form-text text-muted">
        (Hold Ctrl/Cmd to select multiple)
      </small>
    </div>

    <div class="mb-3">
      <label class="form-label">Add New Skills <small>(comma-separated)</small></label>
      <input name="new_skills" class="form-control"
        placeholder="e.g. GraphQL, Kubernetes">
      <small class="form-text text-muted">
        Any names here get created & linked—but if it already exists,
        you'll be prompted to pick it from above.
      </small>
    </div>
  <?php endif; ?>

  <button type="submit" class="btn btn-primary">Save Changes</button>
  <a href="profile.php" class="btn btn-link">← Back to Profile</a>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
