<?php
/**
 * Suppliers Management
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Suppliers';

// Save (add/update) supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $company = trim($_POST['company_name'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $gst     = trim($_POST['gst_no'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($company)) {
        // No redirect on validation error — we need $_POST values to
        // refill the form, so we fall through and render the page normally.
        $message = 'Company name is required.';
        $msgType = 'danger';
    } else {
        if ($id > 0) {
            db()->execute('UPDATE suppliers SET company_name=?,contact_person=?,phone=?,email=?,gst_no=?,address=? WHERE id=?',
                [$company,$contact,$phone,$email,$gst,$address,$id]);
            logActivity("Updated supplier: $company", 'suppliers');
            $_SESSION['flash_message'] = 'Supplier updated successfully!';
        } else {
            db()->insert('INSERT INTO suppliers (company_name,contact_person,phone,email,gst_no,address) VALUES (?,?,?,?,?,?)',
                [$company,$contact,$phone,$email,$gst,$address]);
            logActivity("Added supplier: $company", 'suppliers');
            $_SESSION['flash_message'] = 'Supplier added successfully!';
        }

        // ⭐ Redirect so refresh re-runs a GET, not the POST → no duplicate inserts
        header('Location: '.APP_URL.'/admin/suppliers.php?saved=1'); exit;
    }
}

// Delete supplier
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $sup = db()->fetchOne('SELECT company_name FROM suppliers WHERE id=?',[$delId]);
    db()->execute('DELETE FROM suppliers WHERE id=?',[$delId]);
    logActivity("Deleted supplier: ".($sup['company_name']??''), 'suppliers');
    header('Location: '.APP_URL.'/admin/suppliers.php?deleted=1'); exit;
}

// Pull flash message set by a previous request (survives the redirect)
$message = $message ?? ($_SESSION['flash_message'] ?? '');
$msgType = $msgType ?? 'success';
unset($_SESSION['flash_message']);

$editSupplier = null;
if (isset($_GET['edit'])) $editSupplier = db()->fetchOne('SELECT * FROM suppliers WHERE id=?',[(int)$_GET['edit']]);

$suppliers = db()->fetchAll("
    SELECT s.*, COUNT(p.id) AS purchase_count, COALESCE(SUM(p.total_amount),0) AS total_purchased
    FROM suppliers s
    LEFT JOIN purchases p ON p.supplier_id = s.id
    GROUP BY s.id ORDER BY s.created_at DESC
");

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-truck me-2 text-primary-custom"></i>Suppliers</h1>
      <p class="page-subtitle">Manage your vendor and supplier accounts</p></div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" id="addSupBtn">
        <i class="bi bi-plus-lg"></i> Add Supplier
      </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-check-circle-fill me-2"></i><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Supplier deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h2 class="card-title"><i class="bi bi-table me-2"></i>Supplier List <span class="badge badge-primary ms-1"><?= count($suppliers) ?></span></h2></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="suppliersTable">
            <thead><tr><th>#</th><th>Company</th><th>Contact</th><th>Phone</th><th>Email</th><th>GST No.</th><th>Purchases</th><th>Total Bought</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($suppliers as $i => $s): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#dbeafe,#ede9fe);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">🏭</div>
                    <strong><?= e($s['company_name']) ?></strong>
                  </div>
                </td>
                <td><?= e($s['contact_person'] ?: '—') ?></td>
                <td><?= e($s['phone'] ?: '—') ?></td>
                <td><?= e($s['email'] ?: '—') ?></td>
                <td><code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:11px;"><?= e($s['gst_no'] ?: '—') ?></code></td>
                <td><span class="badge badge-info"><?= $s['purchase_count'] ?></span></td>
                <td class="fw-700"><?= formatCurrency($s['total_purchased']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                    <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon" onclick="return confirm('Delete this supplier?')" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash-fill"></i></a>
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

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="supModalTitle"><i class="bi bi-truck me-2"></i>Add Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="supId" value="0" />
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Company Name *</label><input type="text" class="form-control" name="company_name" id="supCompany" required /></div>
            <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" class="form-control" name="contact_person" id="supContact" /></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" id="supPhone" /></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="supEmail" /></div>
            <div class="col-md-6"><label class="form-label">GST Number</label><input type="text" class="form-control" name="gst_no" id="supGst" /></div>
            <div class="col-md-12"><label class="form-label">Address</label><textarea class="form-control" name="address" id="supAddress" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><span id="supSubmit">Add Supplier</span></button>
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
  initDataTable('#suppliersTable');
  document.getElementById('addSupBtn').addEventListener('click', function() {
    document.getElementById('supModalTitle').innerHTML = '<i class="bi bi-truck me-2"></i>Add Supplier';
    document.getElementById('supId').value='0';
    ['supCompany','supContact','supPhone','supEmail','supGst','supAddress'].forEach(id => document.getElementById(id).value='');
    document.getElementById('supSubmit').textContent='Add Supplier';
  });
  <?php if ($editSupplier): ?>
  const modal = new bootstrap.Modal(document.getElementById('supplierModal'));
  document.getElementById('supModalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Supplier';
  document.getElementById('supId').value='<?= $editSupplier['id'] ?>';
  document.getElementById('supCompany').value='<?= e($editSupplier['company_name']) ?>';
  document.getElementById('supContact').value='<?= e($editSupplier['contact_person']) ?>';
  document.getElementById('supPhone').value='<?= e($editSupplier['phone']) ?>';
  document.getElementById('supEmail').value='<?= e($editSupplier['email']) ?>';
  document.getElementById('supGst').value='<?= e($editSupplier['gst_no']) ?>';
  document.getElementById('supAddress').value='<?= e($editSupplier['address']) ?>';
  document.getElementById('supSubmit').textContent='Update Supplier';
  modal.show();
  <?php endif; ?>
});
</script>
<?php
$pageScripts = ob_get_clean();
include '../includes/footer.php';
?>