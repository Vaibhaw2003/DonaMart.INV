<?php
/**
 * User Profile
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'My Profile';
$user      = db()->fetchOne('SELECT * FROM users WHERE id=?', [currentUser()['id']]);
$message   = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $currPwd = $_POST['current_password'] ?? '';
    $newPwd  = $_POST['new_password'] ?? '';
    $confPwd = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email)) {
        $message = 'Name and email are required.'; $msgType = 'danger';
    } else {
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $upload = uploadImage($_FILES['avatar'], USER_UPLOAD_PATH);
            if ($upload['success']) {
                if ($avatar && file_exists(USER_UPLOAD_PATH . $avatar)) unlink(USER_UPLOAD_PATH . $avatar);
                $avatar = $upload['filename'];
                $_SESSION['user_avatar'] = $avatar;
            }
        }

        // Password change — only attempt if user actually typed a new password.
        // (Browsers autofill "current_password" automatically; basing this check
        // on $currPwd would force a password change on every profile update.)
        $pwdSql = '';
        $pwdParams = [];
        if (!empty($newPwd) || !empty($confPwd)) {
            if (empty($currPwd)) {
                $message = 'Please enter your current password to set a new one.'; $msgType = 'danger';
            } elseif (!password_verify($currPwd, $user['password'])) {
                $message = 'Current password is incorrect.'; $msgType = 'danger';
            } elseif ($newPwd !== $confPwd) {
                $message = 'New passwords do not match.'; $msgType = 'danger';
            } elseif (strlen($newPwd) < 6) {
                $message = 'Password must be at least 6 characters.'; $msgType = 'danger';
            } else {
                $pwdSql = ', password=?';
                $pwdParams = [password_hash($newPwd, PASSWORD_BCRYPT)];
            }
        }

        if (!$message) {
            db()->execute(
                "UPDATE users SET name=?, email=?, avatar=? $pwdSql WHERE id=?",
                array_merge([$name, $email, $avatar], $pwdParams, [currentUser()['id']])
            );
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            logActivity('Updated profile','profile');
            $message = 'Profile updated successfully!';
            $user = db()->fetchOne('SELECT * FROM users WHERE id=?', [currentUser()['id']]);
        }
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-person-circle me-2 text-primary-custom"></i>My Profile</h1>
      <p class="page-subtitle">Manage your account settings</p></div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3">
      <!-- Avatar Card -->
      <div class="col-lg-4">
        <div class="card text-center">
          <div class="card-body py-4">
            <?php if ($user['avatar'] && file_exists(USER_UPLOAD_PATH . $user['avatar'])): ?>
            <img id="avatarPreview" src="<?= USER_UPLOAD_URL . e($user['avatar']) ?>"
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin-bottom:12px;" />
            <?php else: ?>
            <div id="avatarPreview" style="width:100px;height:100px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:#15231D;margin:0 auto 12px;">
              <?= strtoupper(substr($user['name'],0,1)) ?>
            </div>
            <?php endif; ?>
            <h2 class="fw-700 mb-1" style="font-size:18px;"><?= e($user['name']) ?></h2>
            <div class="badge badge-primary mb-3"><?= ucfirst(e($user['role'])) ?></div>
            <div class="text-muted fs-12 mb-1"><i class="bi bi-envelope me-1"></i><?= e($user['email']) ?></div>
            <div class="text-muted fs-12"><i class="bi bi-calendar me-1"></i>Joined <?= !empty($user['created_at']) ? formatDate($user['created_at']) : '—' ?></div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
          <div class="card-header"><h3 class="card-title fs-13"><i class="bi bi-activity me-2"></i>Your Activity</h3></div>
          <div class="card-body">
            <?php
            $myLogs = db()->fetchAll('SELECT action, module, created_at FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 5', [$user['id']]);
            ?>
            <?php foreach ($myLogs as $log): ?>
            <div class="d-flex align-items-start gap-2 mb-2">
              <div style="width:6px;height:6px;border-radius:50%;background:var(--primary);margin-top:6px;flex-shrink:0;"></div>
              <div>
                <div class="fs-12 fw-600"><?= e($log['action']) ?></div>
                <div class="fs-12 text-muted"><?= formatDateTime($log['created_at']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($myLogs)): ?>
            <div class="text-muted fs-12 text-center">No activity yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Edit Form -->
      <div class="col-lg-8">
        <form method="POST" enctype="multipart/form-data" autocomplete="off">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-person-fill me-2"></i>Personal Information</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Full Name *</label>
                  <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email Address *</label>
                  <input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required />
                </div>
                <div class="col-12">
                  <label class="form-label">Profile Picture</label>
                  <input type="file" class="form-control" id="avatarInput" name="avatar" accept="image/*" />
                  <div class="text-muted fs-12 mt-1">JPG, PNG — Max 2MB</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-shield-lock me-2"></i>Change Password</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Current Password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" name="current_password" id="currentPwd" placeholder="Leave blank to keep current" autocomplete="off" />
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('currentPwd', this)" tabindex="-1"><i class="bi bi-eye-fill"></i></button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">New Password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" name="new_password" id="newPwd" placeholder="Min. 6 characters" autocomplete="off" />
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('newPwd', this)" tabindex="-1"><i class="bi bi-eye-fill"></i></button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Confirm New Password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" name="confirm_password" id="confirmPwd" placeholder="Repeat new password" autocomplete="off" />
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('confirmPwd', this)" tabindex="-1"><i class="bi bi-eye-fill"></i></button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle-fill me-1"></i>Update Profile</button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php
$pageScripts = <<<'JS'
<script>
document.getElementById('avatarInput').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const preview = document.getElementById('avatarPreview');
    if (preview.tagName === 'IMG') {
      preview.src = e.target.result;
    } else {
      // Replace div with img
      const img = document.createElement('img');
      img.id = 'avatarPreview';
      img.src = e.target.result;
      img.style = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin-bottom:12px;';
      preview.parentNode.replaceChild(img, preview);
    }
  };
  reader.readAsDataURL(file);
});

function togglePwd(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
  } else {
    input.type = 'password';
    icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
  }
}
</script>
JS;
include '../includes/footer.php';
?>