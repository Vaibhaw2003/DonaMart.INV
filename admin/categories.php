<?php
/**
 * Categories Management
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Categories';
$message = ''; $msgType = 'success';

// Handle save (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $message = 'Category name is required.'; $msgType = 'danger';
    } else {
        if ($id > 0) {
            db()->execute('UPDATE categories SET name=?, description=? WHERE id=?', [$name, $desc, $id]);
            logActivity("Updated category: $name", 'categories');
            $message = 'Category updated successfully!';
        } else {
            db()->insert('INSERT INTO categories (name, description) VALUES (?,?)', [$name, $desc]);
            logActivity("Added category: $name", 'categories');
            $message = 'Category added successfully!';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $cat = db()->fetchOne('SELECT name FROM categories WHERE id=?', [$delId]);
    db()->execute('DELETE FROM categories WHERE id=?', [$delId]);
    logActivity("Deleted category: " . ($cat['name'] ?? ''), 'categories');
    header('Location: ' . APP_URL . '/admin/categories.php?deleted=1');
    exit;
}

// Fetch for edit
$editCat = null;
if (isset($_GET['edit'])) {
    $editCat = db()->fetchOne('SELECT * FROM categories WHERE id=?', [(int)$_GET['edit']]);
}

$categories = db()->fetchAll("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id AND p.status = 1
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<?php include '../includes/app_header.php'; ?>

  <main class="page-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="bi bi-tag-fill me-2 text-primary-custom"></i>Categories</h1>
        <p class="page-subtitle">Manage your product categories</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" id="addCatBtn">
        <i class="bi bi-plus-lg"></i> Add Category
      </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i><?= e($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-check-circle-fill me-2"></i>Category deleted successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Category Cards -->
    <div class="row g-3 mb-4">
      <?php foreach ($categories as $cat): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100" style="transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow-lg)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#2563eb22,#818cf844);display:flex;align-items:center;justify-content:center;font-size:20px;">
                🏷️
              </div>
              <div class="dropdown">
                <button class="btn btn-icon btn-outline-secondary btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end" style="background:var(--surface);border:1px solid var(--border);">
                  <li><a class="dropdown-item" href="?edit=<?= $cat['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                  <li><a class="dropdown-item text-danger" href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Delete category?')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                </ul>
              </div>
            </div>
            <h3 class="fw-700 mb-1" style="font-size:15px;"><?= e($cat['name']) ?></h3>
            <p class="text-muted fs-12 mb-2"><?= e($cat['description'] ?: 'No description') ?></p>
            <div class="d-flex align-items-center gap-1">
              <i class="bi bi-boxes text-primary-custom fs-12"></i>
              <span class="fs-12 fw-600"><?= $cat['product_count'] ?> Products</span>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($categories)): ?>
      <div class="col-12">
        <div class="card text-center py-5">
          <div class="text-muted"><i class="bi bi-tag-fill d-block fs-22 mb-2"></i>No categories found. Add your first category!</div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Categories Table -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><i class="bi bi-table me-2"></i>All Categories</h2>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="categoriesTable">
            <thead>
              <tr><th>#</th><th>Name</th><th>Description</th><th>Products</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $i => $cat): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= e($cat['name']) ?></strong></td>
                <td class="text-muted"><?= e($cat['description'] ?: '—') ?></td>
                <td><span class="badge badge-primary"><?= $cat['product_count'] ?></span></td>
                <td><?= formatDate($cat['created_at']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit">
                      <i class="bi bi-pencil-fill"></i>
                    </a>
                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon"
                       onclick="return confirm('Delete this category?')" data-bs-toggle="tooltip" title="Delete">
                      <i class="bi bi-trash-fill"></i>
                    </a>
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

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="catModalTitle"><i class="bi bi-tag-fill me-2"></i>Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="catId" value="0" />
          <div class="mb-3">
            <label class="form-label">Category Name *</label>
            <input type="text" class="form-control" name="name" id="catName" required placeholder="e.g. Electronics" />
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="catDesc" rows="3" placeholder="Optional description..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><span id="catSubmitText">Add Category</span></button>
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
  initDataTable('#categoriesTable', { order: [[4, 'desc']] });

  // Reset modal for Add
  document.getElementById('addCatBtn').addEventListener('click', function() {
    document.getElementById('catModalTitle').innerHTML = '<i class="bi bi-tag-fill me-2"></i>Add Category';
    document.getElementById('catId').value = '0';
    document.getElementById('catName').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('catSubmitText').textContent = 'Add Category';
  });

  // Prefill for Edit
  <?php if ($editCat): ?>
  const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
  document.getElementById('catModalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Category';
  document.getElementById('catId').value = '<?= $editCat['id'] ?>';
  document.getElementById('catName').value = '<?= e($editCat['name']) ?>';
  document.getElementById('catDesc').value = '<?= e($editCat['description']) ?>';
  document.getElementById('catSubmitText').textContent = 'Update Category';
  modal.show();
  <?php endif; ?>
});
</script>
<?php
$pageScripts = ob_get_clean();
include '../includes/footer.php';
?>
