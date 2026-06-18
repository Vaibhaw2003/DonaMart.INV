<?php
/**
 * Sales Management — Invoice Builder
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Sales & Invoices';
$action = $_GET['action'] ?? 'list';
$error  = '';

$settings  = getSettings();
$gstRate   = (float)($settings['gst_rate'] ?? 18);

// Save new sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sale'])) {
    $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
    $saleDate   = $_POST['sale_date'] ?? date('Y-m-d');
    $discount   = (float)($_POST['discount'] ?? 0);
    $gstAmt     = (float)($_POST['gst_amount'] ?? 0);
    $grandTotal = (float)($_POST['grand_total'] ?? 0);
    $subtotal   = (float)($_POST['subtotal'] ?? 0);
    $payMethod  = $_POST['payment_method'] ?? 'cash';
    $notes      = trim($_POST['notes'] ?? '');
    $items      = $_POST['items'] ?? [];

    if (empty($items)) {
        $error = 'Add at least one product.'; $action = 'new';
    } else {
        $invoiceNo = generateInvoiceNumber();
        try {
            db()->beginTransaction();
            $saleId = db()->insert(
                'INSERT INTO sales (customer_id,invoice_number,sale_date,subtotal,discount,gst,grand_total,payment_method,notes) VALUES (?,?,?,?,?,?,?,?,?)',
                [$customerId,$invoiceNo,$saleDate,$subtotal,$discount,$gstAmt,$grandTotal,$payMethod,$notes]
            );
            foreach ($items as $item) {
                $pId   = (int)($item['product_id'] ?? 0);
                $qty   = (int)($item['qty'] ?? 0);
                $price = (float)($item['price'] ?? 0);
                $disc  = (float)($item['discount'] ?? 0);
                if (!$pId || !$qty) continue;
                $lineTotal = max(0, $qty * $price - $disc);
                db()->insert('INSERT INTO sale_items (sale_id,product_id,quantity,price,discount,total) VALUES (?,?,?,?,?,?)',
                    [$saleId,$pId,$qty,$price,$disc,$lineTotal]);
                db()->execute('UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?', [$qty,$pId,$qty]);
            }
            db()->commit();
            logActivity("Created sale invoice: $invoiceNo",'sales');
            header('Location: '.APP_URL.'/admin/invoice.php?id='.$saleId.'&new=1'); exit;
        } catch (Exception $e) {
            db()->rollback();
            $error = 'Failed to save invoice: '.$e->getMessage();
            $action = 'new';
        }
    }
}

// Delete sale
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $sale  = db()->fetchOne('SELECT invoice_number FROM sales WHERE id=?',[$delId]);
    $items = db()->fetchAll('SELECT * FROM sale_items WHERE sale_id=?',[$delId]);
    foreach ($items as $si) {
        db()->execute('UPDATE products SET stock = stock + ? WHERE id=?',[$si['quantity'],$si['product_id']]);
    }
    db()->execute('DELETE FROM sales WHERE id=?',[$delId]);
    logActivity("Deleted sale: ".($sale['invoice_number']??''),'sales');
    header('Location: '.APP_URL.'/admin/sales.php?deleted=1'); exit;
}

$customers = db()->fetchAll('SELECT * FROM customers ORDER BY name');
$sales     = db()->fetchAll("
    SELECT s.*, c.name AS customer_name
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    ORDER BY s.sale_date DESC, s.created_at DESC
");

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">

  <?php if ($action === 'new'): ?>
    <!-- ===== NEW SALE / INVOICE BUILDER ===== -->
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-receipt me-2 text-primary-custom"></i>New Invoice</h1>
      <div class="breadcrumb-custom"><a href="sales.php">Sales</a><span class="sep">/</span><span>New Invoice</span></div></div>
      <a href="sales.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="saleForm">
      <input type="hidden" name="save_sale" value="1" />
      <input type="hidden" name="subtotal"    id="subtotalInput" value="0" />
      <input type="hidden" name="gst_amount"  id="gstAmount"     value="0" />
      <input type="hidden" name="grand_total" id="grandTotalInput" value="0" />

      <div class="row g-3">
        <!-- Left: Invoice builder -->
        <div class="col-lg-8">
          <!-- Customer & Date -->
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-person-fill me-2"></i>Invoice Details</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Customer</label>
                  <select class="form-select" name="customer_id">
                    <option value="">Walk-in Customer</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> <?= $c['phone'] ? '('.$c['phone'].')' : '' ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Sale Date *</label>
                  <input type="date" class="form-control" name="sale_date" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Payment Method</label>
                  <select class="form-select" name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="card">💳 Card</option>
                    <option value="upi">📱 UPI</option>
                    <option value="bank">🏦 Bank Transfer</option>
                    <option value="credit">📋 Credit</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Notes</label>
                  <input type="text" class="form-control" name="notes" placeholder="Optional note..." />
                </div>
              </div>
            </div>
          </div>

          <!-- Items -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title"><i class="bi bi-list-ul me-2"></i>Products</h2>
              <button type="button" class="btn btn-sm btn-primary" onclick="InvoiceBuilder.addRow()">
                <i class="bi bi-plus-lg me-1"></i>Add Product
              </button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table mb-0">
                  <thead>
                    <tr>
                      <th style="min-width:220px;">Product</th>
                      <th style="width:80px;">Qty</th>
                      <th style="width:110px;">Price (₹)</th>
                      <th style="width:100px;">Discount</th>
                      <th style="width:100px;">Total</th>
                      <th style="width:50px;"></th>
                    </tr>
                  </thead>
                  <tbody id="invoiceItems"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Totals -->
        <div class="col-lg-4">
          <div class="card sticky-top" style="top:80px;">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-calculator me-2"></i>Invoice Summary</h2></div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <span class="fw-600">₹<span id="subtotalDisplay">0.00</span></span>
              </div>
              <div class="mb-3">
                <label class="form-label fs-12">Global Discount (₹)</label>
                <input type="number" class="form-control form-control-sm" id="globalDiscount" name="discount" value="0" min="0" step="0.01"
                       oninput="InvoiceBuilder.recalcTotals()" />
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Discount</span>
                <span class="fw-600 text-danger">- ₹<span id="discountDisplay">0.00</span></span>
              </div>
              <div class="mb-3">
                <label class="form-label fs-12">GST Rate (%)</label>
                <input type="number" class="form-control form-control-sm" id="gstRate" name="gst_rate" value="<?= $gstRate ?>" min="0" step="0.01"
                       oninput="InvoiceBuilder.recalcTotals()" />
              </div>
              <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                <span class="text-muted">GST (<?= $gstRate ?>%)</span>
                <span class="fw-600 text-success">+ ₹<span id="gstDisplay">0.00</span></span>
              </div>
              <div class="d-flex justify-content-between mb-4">
                <span class="fw-700 fs-13">Grand Total</span>
                <span class="fw-800 text-primary-custom" style="font-size:22px;">₹<span id="grandTotalDisplay">0.00</span></span>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">
                  <i class="bi bi-check-circle-fill me-1"></i>Save & Generate Invoice
                </button>
                <a href="sales.php" class="btn btn-outline-secondary">Cancel</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

  <?php else: ?>
    <!-- ===== SALES LIST ===== -->
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-receipt me-2 text-primary-custom"></i>Sales & Invoices</h1>
      <p class="page-subtitle">All sales transactions</p></div>
      <a href="?action=new" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Invoice</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i>Sale deleted &amp; stock restored.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
      <?php
      $todaySales   = db()->fetchOne("SELECT COALESCE(SUM(grand_total),0) t, COUNT(*) c FROM sales WHERE sale_date=CURDATE()");
      $monthSales   = db()->fetchOne("SELECT COALESCE(SUM(grand_total),0) t, COUNT(*) c FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
      ?>
      <div class="col-md-3">
        <div class="stat-card green"><div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="stat-body"><div class="stat-label">Today's Sales</div><div class="stat-value" style="font-size:18px;"><?= formatCurrency($todaySales['t']) ?></div><div class="stat-change"><?= $todaySales['c'] ?> invoices</div></div></div>
      </div>
      <div class="col-md-3">
        <div class="stat-card blue"><div class="stat-icon"><i class="bi bi-calendar-month-fill"></i></div>
        <div class="stat-body"><div class="stat-label">This Month</div><div class="stat-value" style="font-size:18px;"><?= formatCurrency($monthSales['t']) ?></div><div class="stat-change"><?= $monthSales['c'] ?> invoices</div></div></div>
      </div>
      <div class="col-md-3">
        <div class="stat-card purple"><div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="stat-body"><div class="stat-label">Total Invoices</div><div class="stat-value"><?= count($sales) ?></div><div class="stat-change">All Time</div></div></div>
      </div>
      <div class="col-md-3">
        <div class="stat-card orange"><div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
        <div class="stat-body"><div class="stat-label">Total Revenue</div><div class="stat-value" style="font-size:18px;"><?= formatCurrency(array_sum(array_column($sales,'grand_total'))) ?></div><div class="stat-change">All Time</div></div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2 class="card-title"><i class="bi bi-table me-2"></i>Invoice List</h2></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="salesTable">
            <thead><tr><th>#</th><th>Invoice No.</th><th>Customer</th><th>Date</th><th>Subtotal</th><th>Discount</th><th>GST</th><th>Grand Total</th><th>Payment</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($sales as $i => $s):
                $pmColors = ['cash'=>'success','card'=>'primary','upi'=>'info','bank'=>'secondary','credit'=>'warning'];
              ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge badge-primary"><?= e($s['invoice_number']) ?></span></td>
                <td><?= e($s['customer_name'] ?? 'Walk-in Customer') ?></td>
                <td><?= formatDate($s['sale_date']) ?></td>
                <td><?= formatCurrency($s['subtotal']) ?></td>
                <td class="text-danger"><?= formatCurrency($s['discount']) ?></td>
                <td class="text-success"><?= formatCurrency($s['gst']) ?></td>
                <td class="fw-700 text-primary-custom"><?= formatCurrency($s['grand_total']) ?></td>
                <td><span class="badge badge-<?= $pmColors[$s['payment_method']] ?? 'secondary' ?>"><?= ucfirst(e($s['payment_method'])) ?></span></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="invoice.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="View Invoice"><i class="bi bi-eye-fill"></i></a>
                    <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon" onclick="return confirm('Delete this invoice? Stock will be restored.')" data-bs-toggle="tooltip" title="Delete"><i class="bi bi-trash-fill"></i></a>
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

<?php
$pageScripts = <<<'JS'
<script>
$(document).ready(function() {
  initDataTable('#salesTable', { order:[[3,'desc']] });
  if (document.getElementById('invoiceItems')) {
    InvoiceBuilder.addRow();
  }
  document.getElementById('saleForm')?.addEventListener('submit', function() {
    if (!document.querySelectorAll('#invoiceItems tr').length) {
      event.preventDefault();
      SmartINV.toast('Please add at least one product', 'warning');
      return;
    }
    const btn = document.getElementById('saveInvoiceBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    btn.disabled = true;
  });
});
</script>
JS;
include '../includes/footer.php';
?>
