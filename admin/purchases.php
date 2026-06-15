<?php
/**
 * Purchases Management
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Purchases';
$action = $_GET['action'] ?? 'list';
$error = ''; $success = '';

// Save new purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase'])) {
    $supplierId   = (int)($_POST['supplier_id'] ?? 0) ?: null;
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $notes        = trim($_POST['notes'] ?? '');
    $items        = $_POST['items'] ?? [];

    if (empty($items)) {
        $error = 'Please add at least one product.';
    } else {
        $poNumber  = generatePurchaseNumber();
        $totalAmt  = 0;
        foreach ($items as $item) {
            $totalAmt += (float)($item['qty'] ?? 0) * (float)($item['price'] ?? 0);
        }

        try {
            db()->beginTransaction();
            $purchaseId = db()->insert(
                'INSERT INTO purchases (supplier_id, purchase_number, purchase_date, total_amount, notes) VALUES (?,?,?,?,?)',
                [$supplierId, $poNumber, $purchaseDate, $totalAmt, $notes]
            );
            foreach ($items as $item) {
                $pId  = (int)($item['product_id'] ?? 0);
                $qty  = (int)($item['qty'] ?? 0);
                $price= (float)($item['price'] ?? 0);
                if (!$pId || !$qty) continue;
                db()->insert('INSERT INTO purchase_items (purchase_id, product_id, quantity, price) VALUES (?,?,?,?)',
                    [$purchaseId, $pId, $qty, $price]);
                db()->execute('UPDATE products SET stock = stock + ? WHERE id=?', [$qty, $pId]);
            }
            db()->commit();
            logActivity("Created purchase: $poNumber", 'purchases');
            header('Location: '.APP_URL.'/admin/purchases.php?saved=1'); exit;
        } catch (Exception $e) {
            db()->rollback();
            $error = 'Failed to save purchase: ' . $e->getMessage();
        }
    }
}

// Delete purchase
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $purch = db()->fetchOne('SELECT purchase_number FROM purchases WHERE id=?',[$delId]);
    // Reverse stock
    $pItems = db()->fetchAll('SELECT * FROM purchase_items WHERE purchase_id=?',[$delId]);
    foreach ($pItems as $pi) {
        db()->execute('UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?', [$pi['quantity'],$pi['product_id'],$pi['quantity']]);
    }
    db()->execute('DELETE FROM purchases WHERE id=?',[$delId]);
    logActivity("Deleted purchase: ".($purch['purchase_number']??''),'purchases');
    header('Location: '.APP_URL.'/admin/purchases.php?deleted=1'); exit;
}

$suppliers = db()->fetchAll('SELECT * FROM suppliers ORDER BY company_name');
$purchases = db()->fetchAll("
    SELECT p.*, s.company_name AS supplier_name
    FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    ORDER BY p.purchase_date DESC, p.created_at DESC
");

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<div class="main-content">
  <header class="app-header">
    <button class="header-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="header-search"><div class="search-wrapper"><i class="bi bi-search search-icon"></i><input type="text" class="form-control" placeholder="Search..." /></div></div>
    <div class="header-actions">
      <button class="header-btn" id="darkModeToggle"><i class="bi bi-moon-stars-fill" id="darkModeIcon"></i></button>
      <a href="<?= APP_URL ?>/logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </header>
  <main class="page-content">

    <?php if ($action === 'new'): ?>
    <!-- ===== NEW PURCHASE FORM ===== -->
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-cart-plus-fill me-2 text-primary-custom"></i>New Purchase</h1>
      <div class="breadcrumb-custom"><a href="purchases.php">Purchases</a><span class="sep">/</span><span>New</span></div></div>
      <a href="purchases.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="save_purchase" value="1" />
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-info-circle me-2"></i>Purchase Details</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Supplier</label>
                  <select class="form-select" name="supplier_id">
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $sup): ?>
                    <option value="<?= $sup['id'] ?>"><?= e($sup['company_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Purchase Date *</label>
                  <input type="date" class="form-control" name="purchase_date" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="col-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title"><i class="bi bi-list-ul me-2"></i>Products</h2>
              <button type="button" class="btn btn-sm btn-primary" onclick="PurchaseBuilder.addRow()">
                <i class="bi bi-plus-lg me-1"></i>Add Row
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table mb-0">
                  <thead><tr><th style="width:40%">Product</th><th>Qty</th><th>Price (₹)</th><th>Total</th><th></th></tr></thead>
                  <tbody id="purchaseItems"></tbody>
                </table>
              </div>
            </div>
            <div class="card-body border-top">
              <div class="d-flex justify-content-end">
                <div class="text-end">
                  <div class="fs-13 text-muted">Grand Total</div>
                  <div class="fw-800" style="font-size:24px;">₹<span id="purchaseTotal">0.00</span></div>
                  <input type="hidden" name="total_amount" id="purchaseTotalInput" value="0" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h3 class="fw-700 mb-3" style="font-size:15px;">Summary</h3>
              <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                <span class="text-muted">Total Amount</span>
                <span class="fw-700">₹<span id="summaryTotal">0.00</span></span>
              </div>
              <div class="d-grid gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Save Purchase</button>
                <a href="purchases.php" class="btn btn-outline-secondary">Cancel</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ===== PURCHASE LIST ===== -->
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-cart-plus-fill me-2 text-primary-custom"></i>Purchases</h1>
      <p class="page-subtitle">Track all stock purchases</p></div>
      <a href="?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Purchase</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Purchase saved &amp; stock updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Purchase deleted &amp; stock reversed.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h2 class="card-title"><i class="bi bi-table me-2"></i>Purchase Records <span class="badge badge-primary ms-1"><?= count($purchases) ?></span></h2></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="purchasesTable">
            <thead><tr><th>#</th><th>PO Number</th><th>Supplier</th><th>Date</th><th>Total Amount</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($purchases as $i => $p): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge badge-secondary"><?= e($p['purchase_number']) ?></span></td>
                <td><?= e($p['supplier_name'] ?? 'Unknown') ?></td>
                <td><?= formatDate($p['purchase_date']) ?></td>
                <td class="fw-700"><?= formatCurrency($p['total_amount']) ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-info btn-icon" onclick="viewPurchaseDetail(<?= $p['id'] ?>)" data-bs-toggle="tooltip" title="View Items"><i class="bi bi-eye-fill"></i></button>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon" onclick="return confirm('Delete this purchase? Stock will be reversed.')" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash-fill"></i></a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>

<!-- Purchase Detail Modal -->
<div class="modal fade" id="purchaseDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Purchase Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body p-0" id="purchaseDetailBody">
        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
$(document).ready(function() {
  initDataTable('#purchasesTable', { order:[[3,'desc']] });
  // Add one row by default on new purchase
  if (document.getElementById('purchaseItems')) {
    PurchaseBuilder.addRow();
  }
  // Sync summary total
  const origRecalc = PurchaseBuilder.recalc.bind(PurchaseBuilder);
  PurchaseBuilder.recalc = function() {
    origRecalc();
    const v = document.getElementById('purchaseTotal')?.textContent || '0.00';
    const s = document.getElementById('summaryTotal');
    if (s) s.textContent = v;
  };
});

function viewPurchaseDetail(id) {
  const modal = new bootstrap.Modal(document.getElementById('purchaseDetailModal'));
  document.getElementById('purchaseDetailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  modal.show();
  fetch('../ajax/get_purchase_detail.php?id=' + id)
    .then(r => r.text())
    .then(html => { document.getElementById('purchaseDetailBody').innerHTML = html; })
    .catch(() => { document.getElementById('purchaseDetailBody').innerHTML = '<div class="text-center text-danger p-3">Failed to load.</div>'; });
}
</script>
JS;
include '../includes/footer.php';
?>
