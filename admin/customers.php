<?php
/**
 * Customers Management
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Customers';
$message = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) { $message = 'Customer name is required.'; $msgType = 'danger'; }
    else {
        if ($id > 0) {
            db()->execute('UPDATE customers SET name=?,phone=?,email=?,address=? WHERE id=?',[$name,$phone,$email,$address,$id]);
            logActivity("Updated customer: $name",'customers');
            $message = 'Customer updated successfully!';
        } else {
            db()->insert('INSERT INTO customers (name,phone,email,address) VALUES (?,?,?,?)',[$name,$phone,$email,$address]);
            logActivity("Added customer: $name",'customers');
            $message = 'Customer added successfully!';
        }
    }
}

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $c = db()->fetchOne('SELECT name FROM customers WHERE id=?',[$delId]);
    db()->execute('DELETE FROM customers WHERE id=?',[$delId]);
    logActivity("Deleted customer: ".($c['name']??''),'customers');
    header('Location: '.APP_URL.'/admin/customers.php?deleted=1'); exit;
}

$editCustomer = null;
if (isset($_GET['edit'])) $editCustomer = db()->fetchOne('SELECT * FROM customers WHERE id=?',[(int)$_GET['edit']]);

// Customer with purchase history for modal
$historyCustomer = null;
$purchaseHistory = [];
if (isset($_GET['history'])) {
    $historyCustomer = db()->fetchOne('SELECT * FROM customers WHERE id=?',[(int)$_GET['history']]);
    if ($historyCustomer) {
        $purchaseHistory = db()->fetchAll(
            'SELECT s.*, COUNT(si.id) AS items FROM sales s LEFT JOIN sale_items si ON si.sale_id=s.id WHERE s.customer_id=? GROUP BY s.id ORDER BY s.sale_date DESC',
            [$historyCustomer['id']]
        );
    }
}

$customers = db()->fetchAll("
    SELECT c.*, COUNT(s.id) AS order_count, COALESCE(SUM(s.grand_total),0) AS total_spent
    FROM customers c
    LEFT JOIN sales s ON s.customer_id = c.id
    GROUP BY c.id ORDER BY c.created_at DESC
");

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-people-fill me-2 text-primary-custom"></i>Customers</h1>
      <p class="page-subtitle">Manage your customer database</p></div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" id="addCustBtn">
        <i class="bi bi-person-plus-fill"></i> Add Customer
      </button>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-check-circle-fill me-2"></i><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Customer deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h2 class="card-title"><i class="bi bi-table me-2"></i>Customer List <span class="badge badge-primary ms-1"><?= count($customers) ?></span></h2></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="customersTable">
            <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Orders</th><th>Total Spent</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($customers as $i => $c): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#22c55e33,#06b6d433);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:var(--primary);flex-shrink:0;">
                      <?= strtoupper(substr($c['name'],0,1)) ?>
                    </div>
                    <strong><?= e($c['name']) ?></strong>
                  </div>
                </td>
                <td><?= e($c['phone'] ?: '—') ?></td>
                <td><?= e($c['email'] ?: '—') ?></td>
                <td class="text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($c['address'] ?: '—') ?></td>
                <td><span class="badge badge-info"><?= $c['order_count'] ?></span></td>
                <td class="fw-700 text-primary-custom"><?= formatCurrency($c['total_spent']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="?history=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info btn-icon" data-bs-toggle="tooltip" title="Purchase History"><i class="bi bi-clock-history"></i></a>
                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                    <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon" onclick="return confirm('Delete this customer?')" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash-fill"></i></a>
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

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="custModalTitle"><i class="bi bi-person-plus-fill me-2"></i>Add Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="custId" value="0" />
          <div class="row g-3">
            <div class="col-12"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="name" id="custName" required /></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" id="custPhone" /></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="custEmail" /></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" id="custAddress" rows="2"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><span id="custSubmit">Add Customer</span></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Purchase History Modal -->
<?php if ($historyCustomer): ?>
<div class="modal fade" id="historyModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Purchase History — <?= e($historyCustomer['name']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Invoice #</th><th>Date</th><th>Items</th><th>Discount</th><th>GST</th><th>Total</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($purchaseHistory as $ph): ?>
            <tr>
              <td><span class="badge badge-primary"><?= e($ph['invoice_number']) ?></span></td>
              <td><?= formatDate($ph['sale_date']) ?></td>
              <td><?= $ph['items'] ?></td>
              <td><?= formatCurrency($ph['discount']) ?></td>
              <td><?= formatCurrency($ph['gst']) ?></td>
              <td class="fw-700"><?= formatCurrency($ph['grand_total']) ?></td>
              <td><a href="invoice.php?id=<?= $ph['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-eye-fill"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($purchaseHistory)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No purchases yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
ob_start();
?>
<script>
$(document).ready(function() {
  initDataTable('#customersTable');
  document.getElementById('addCustBtn').addEventListener('click', function() {
    document.getElementById('custModalTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Add Customer';
    document.getElementById('custId').value='0';
    ['custName','custPhone','custEmail','custAddress'].forEach(id => document.getElementById(id).value='');
    document.getElementById('custSubmit').textContent='Add Customer';
  });
  <?php if ($editCustomer): ?>
  const modal = new bootstrap.Modal(document.getElementById('customerModal'));
  document.getElementById('custModalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Customer';
  document.getElementById('custId').value='<?= $editCustomer['id'] ?>';
  document.getElementById('custName').value='<?= e($editCustomer['name']) ?>';
  document.getElementById('custPhone').value='<?= e($editCustomer['phone']) ?>';
  document.getElementById('custEmail').value='<?= e($editCustomer['email']) ?>';
  document.getElementById('custAddress').value='<?= e($editCustomer['address']) ?>';
  document.getElementById('custSubmit').textContent='Update Customer';
  modal.show();
  <?php endif; ?>
  <?php if ($historyCustomer): ?>
  new bootstrap.Modal(document.getElementById('historyModal')).show();
  <?php endif; ?>
});
</script>
<?php
$pageScripts = ob_get_clean();
include '../includes/footer.php';
?>
