<?php
/**
 * Register / Manage Users (Admin Only)
 * Smart Inventory & Billing Management System
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireRole('admin');

$pageTitle = 'Manage Users';
$error   = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'staff';
    $editId   = (int)($_POST['edit_id'] ?? 0);

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif ($editId === 0 && empty($password)) {
        $error = 'Password is required for new users.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        if ($editId > 0) {
            // Update
            $sql = 'UPDATE users SET name=?, email=?, role=?' . (!empty($password) ? ', password=?' : '') . ' WHERE id=?';
            $params = !empty($password)
                ? [$name, $email, $role, password_hash($password, PASSWORD_BCRYPT), $editId]
                : [$name, $email, $role, $editId];
            db()->execute($sql, $params);
            logActivity("Updated user: $name", 'users');
            $success = 'User updated successfully!';
        } else {
            // Check duplicate email
            $exists = db()->count('SELECT COUNT(*) c FROM users WHERE email=?', [$email]);
            if ($exists) {
                $error = 'Email already registered.';
            } else {
                db()->insert(
                    'INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)',
                    [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role]
                );
                logActivity("Created user: $name", 'users');
                $success = 'User created successfully!';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId !== (int)currentUser()['id']) {
        db()->execute('DELETE FROM users WHERE id=?', [$delId]);
        logActivity("Deleted user ID: $delId", 'users');
        header('Location: ' . APP_URL . '/register.php?deleted=1');
        exit;
    }
}

// Handle edit prefill
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = db()->fetchOne('SELECT * FROM users WHERE id=?', [(int)$_GET['edit']]);
}

$users = db()->fetchAll('SELECT * FROM users ORDER BY created_at DESC');

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
  <!-- Header -->
  <header class="app-header">
    <button class="header-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="header-search">
      <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control" placeholder="Search..." />
      </div>
    </div>
    <div class="header-actions">
      <button class="header-btn" id="darkModeToggle" data-bs-toggle="tooltip" title="Toggle Dark Mode">
        <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
      </button>
      <a href="<?= APP_URL ?>/logout.php" class="header-btn" data-bs-toggle="tooltip" title="Logout">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </header>

  <main class="page-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="bi bi-people-fill me-2 text-primary-custom"></i>Manage Users</h1>
        <p class="page-subtitle">Create and manage system user accounts</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
        <i class="bi bi-person-plus-fill"></i> Add User
      </button>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i>User deleted successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><i class="bi bi-list-ul me-2"></i>All Users</h2>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="usersTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $i => $u): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#818cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                      <?= strtoupper(substr($u['name'], 0, 1)) ?>
                    </div>
                    <strong><?= e($u['name']) ?></strong>
                  </div>
                </td>
                <td><?= e($u['email']) ?></td>
                <td>
                  <?php
                  $roleClass = ['admin'=>'badge-primary','manager'=>'badge-info','staff'=>'badge-secondary'][$u['role']] ?? 'badge-secondary';
                  ?>
                  <span class="badge <?= $roleClass ?>"><?= ucfirst(e($u['role'])) ?></span>
                </td>
                <td>
                  <span class="badge <?= $u['status'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $u['status'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td><?= formatDate($u['created_at']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit">
                      <i class="bi bi-pencil-fill"></i>
                    </a>
                    <?php if ($u['id'] !== (int)currentUser()['id']): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon"
                       onclick="return confirm('Delete this user?')" data-bs-toggle="tooltip" title="Delete">
                      <i class="bi bi-trash-fill"></i>
                    </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalTitle">
            <i class="bi bi-person-plus-fill me-2"></i>Add New User
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="editId" value="0" />
          <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" id="userName" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address *</label>
            <input type="email" class="form-control" name="email" id="userEmail" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Password <span id="pwdNote" class="text-muted fs-12">(leave blank to keep current)</span></label>
            <input type="password" class="form-control" name="password" id="userPassword" placeholder="Min. 6 characters" />
          </div>
          <div class="mb-3">
            <label class="form-label">Role *</label>
            <select class="form-select" name="role" id="userRole">
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
              <option value="staff" selected>Staff</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><span id="submitBtnText">Create User</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
ob_start();
?>
<script>
$(document).ready(function() {
  initDataTable('#usersTable');

  // If edit user in URL, open modal
  <?php if ($editUser): ?>
  const modal = new bootstrap.Modal(document.getElementById('userModal'));
  document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit User';
  document.getElementById('editId').value = '<?= $editUser['id'] ?>';
  document.getElementById('userName').value = '<?= e($editUser['name']) ?>';
  document.getElementById('userEmail').value = '<?= e($editUser['email']) ?>';
  document.getElementById('userRole').value = '<?= e($editUser['role']) ?>';
  document.getElementById('submitBtnText').textContent = 'Update User';
  document.getElementById('pwdNote').style.display = 'inline';
  modal.show();
  <?php endif; ?>

  // Reset modal on open for add
  document.getElementById('addUserBtn').addEventListener('click', function() {
    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Add New User';
    document.getElementById('editId').value = '0';
    document.getElementById('userName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'staff';
    document.getElementById('submitBtnText').textContent = 'Create User';
    document.getElementById('pwdNote').style.display = 'none';
  });
});
</script>
<?php
$pageScripts = ob_get_clean();
include 'includes/footer.php';
?>
