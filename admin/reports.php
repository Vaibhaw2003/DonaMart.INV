<?php
/**
 * Reports Module
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Reports';
$reportType = $_GET['type'] ?? 'sales';
$dateFrom   = $_GET['date_from'] ?? date('Y-m-01');
$dateTo     = $_GET['date_to']   ?? date('Y-m-d');

$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'sales':
        $reportTitle = 'Sales Report';
        $reportData  = db()->fetchAll("
            SELECT s.invoice_number, s.sale_date, c.name AS party,
                   s.subtotal, s.discount, s.gst, s.grand_total, s.payment_method
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.sale_date BETWEEN ? AND ?
            ORDER BY s.sale_date DESC
        ", [$dateFrom, $dateTo]);
        break;

    case 'purchases':
        $reportTitle = 'Purchase Report';
        $reportData  = db()->fetchAll("
            SELECT p.purchase_number, p.purchase_date, s.company_name AS party, p.total_amount
            FROM purchases p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.purchase_date BETWEEN ? AND ?
            ORDER BY p.purchase_date DESC
        ", [$dateFrom, $dateTo]);
        break;

    case 'inventory':
        $reportTitle = 'Inventory Report';
        $reportData  = db()->fetchAll("
            SELECT p.name, p.sku, c.name AS category, p.stock, p.minimum_stock,
                   p.purchase_price, p.selling_price, p.unit
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.status = 1
            ORDER BY p.stock ASC
        ");
        break;

    case 'customers':
        $reportTitle = 'Customer Report';
        $reportData  = db()->fetchAll("
            SELECT c.name, c.phone, c.email,
                   COUNT(s.id) AS total_orders,
                   COALESCE(SUM(s.grand_total),0) AS total_spent
            FROM customers c
            LEFT JOIN sales s ON s.customer_id = c.id AND s.sale_date BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY total_spent DESC
        ", [$dateFrom, $dateTo]);
        break;

    case 'profit':
        $reportTitle = 'Profit & Loss Report';
        $reportData  = db()->fetchAll("
            SELECT
                DATE_FORMAT(s.sale_date,'%b %Y') AS month,
                DATE_FORMAT(s.sale_date,'%Y-%m') AS month_key,
                COALESCE(SUM(s.grand_total),0) AS revenue,
                COALESCE((SELECT SUM(pi.quantity*pi.price) FROM purchase_items pi
                           JOIN purchases pu ON pu.id=pi.purchase_id
                           WHERE DATE_FORMAT(pu.purchase_date,'%Y-%m')=DATE_FORMAT(s.sale_date,'%Y-%m')),0) AS cost
            FROM sales s
            WHERE s.sale_date BETWEEN ? AND ?
            GROUP BY month_key, month
            ORDER BY month_key ASC
        ", [$dateFrom, $dateTo]);
        // Add profit
        foreach ($reportData as &$row) {
            $row['profit'] = $row['revenue'] - $row['cost'];
        }
        break;
}

// Summary
$totalRevenue   = array_sum(array_column(
    $reportType === 'sales' ? $reportData : [],
    'grand_total'
));

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-bar-chart-line-fill me-2 text-primary-custom"></i>Reports</h1>
      <p class="page-subtitle">Generate and export business reports</p></div>
    </div>

    <!-- Report Type Tabs -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
          <?php
          $types = [
            'sales'     => ['Sales Report','bi-receipt'],
            'purchases' => ['Purchase Report','bi-cart-plus'],
            'inventory' => ['Inventory','bi-boxes'],
            'customers' => ['Customer Report','bi-people-fill'],
            'profit'    => ['Profit & Loss','bi-graph-up'],
          ];
          foreach ($types as $t => [$label, $icon]):
          ?>
          <a href="?type=<?= $t ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
             class="btn btn-sm <?= $reportType===$t ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
          <input type="hidden" name="type" value="<?= e($reportType) ?>" />
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 fs-12 fw-600">From:</label>
            <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($dateFrom) ?>" style="width:150px;" />
          </div>
          <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 fs-12 fw-600">To:</label>
            <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($dateTo) ?>" style="width:150px;" />
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter me-1"></i>Apply</button>
          <a href="?type=<?= $reportType ?>&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">This Month</a>
          <a href="?type=<?= $reportType ?>&date_from=<?= date('Y-01-01') ?>&date_to=<?= date('Y-12-31') ?>" class="btn btn-outline-secondary btn-sm">This Year</a>
        </form>
      </div>
    </div>

    <!-- Report Table -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><i class="bi bi-table me-2"></i><?= e($reportTitle) ?> <span class="badge badge-primary ms-1"><?= count($reportData) ?> records</span></h2>
        <div class="d-flex gap-2">
          <button onclick="exportTable('csv')" class="btn btn-sm btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</button>
          <button onclick="window.print()" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead>
              <tr>
                <?php if ($reportType === 'sales'): ?>
                <th>#</th><th>Invoice No.</th><th>Date</th><th>Customer</th><th>Subtotal</th><th>Discount</th><th>GST</th><th>Grand Total</th><th>Payment</th>
                <?php elseif ($reportType === 'purchases'): ?>
                <th>#</th><th>PO Number</th><th>Date</th><th>Supplier</th><th>Total Amount</th>
                <?php elseif ($reportType === 'inventory'): ?>
                <th>#</th><th>Product</th><th>SKU</th><th>Category</th><th>Purchase Price</th><th>Selling Price</th><th>Stock</th><th>Min Stock</th><th>Unit</th><th>Status</th>
                <?php elseif ($reportType === 'customers'): ?>
                <th>#</th><th>Customer</th><th>Phone</th><th>Email</th><th>Total Orders</th><th>Total Spent</th>
                <?php elseif ($reportType === 'profit'): ?>
                <th>#</th><th>Month</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin %</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reportData as $i => $row): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <?php if ($reportType === 'sales'): ?>
                <td><span class="badge badge-primary"><?= e($row['invoice_number']) ?></span></td>
                <td><?= formatDate($row['sale_date']) ?></td>
                <td><?= e($row['party'] ?? 'Walk-in') ?></td>
                <td><?= formatCurrency($row['subtotal']) ?></td>
                <td class="text-danger"><?= formatCurrency($row['discount']) ?></td>
                <td class="text-success"><?= formatCurrency($row['gst']) ?></td>
                <td class="fw-700"><?= formatCurrency($row['grand_total']) ?></td>
                <td><span class="badge badge-info"><?= ucfirst(e($row['payment_method'])) ?></span></td>
                <?php elseif ($reportType === 'purchases'): ?>
                <td><span class="badge badge-secondary"><?= e($row['purchase_number']) ?></span></td>
                <td><?= formatDate($row['purchase_date']) ?></td>
                <td><?= e($row['party'] ?? 'N/A') ?></td>
                <td class="fw-700"><?= formatCurrency($row['total_amount']) ?></td>
                <?php elseif ($reportType === 'inventory'): ?>
                <td><?= e($row['name']) ?></td>
                <td><code style="background:var(--bg);padding:1px 5px;border-radius:3px;font-size:11px;"><?= e($row['sku']) ?></code></td>
                <td><?= e($row['category'] ?? '—') ?></td>
                <td><?= formatCurrency($row['purchase_price']) ?></td>
                <td class="fw-700"><?= formatCurrency($row['selling_price']) ?></td>
                <td><?php $st=stockStatus($row['stock'],$row['minimum_stock']); ?><span class="fw-700 text-<?= $st['class'] ?>"><?= $row['stock'] ?></span></td>
                <td><?= $row['minimum_stock'] ?></td>
                <td><?= e($row['unit']) ?></td>
                <td><span class="badge badge-<?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                <?php elseif ($reportType === 'customers'): ?>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['phone'] ?: '—') ?></td>
                <td><?= e($row['email'] ?: '—') ?></td>
                <td><span class="badge badge-info"><?= $row['total_orders'] ?></span></td>
                <td class="fw-700 text-primary-custom"><?= formatCurrency($row['total_spent']) ?></td>
                <?php elseif ($reportType === 'profit'): ?>
                <td><?= e($row['month']) ?></td>
                <td><?= formatCurrency($row['revenue']) ?></td>
                <td><?= formatCurrency($row['cost']) ?></td>
                <td class="fw-700 <?= $row['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= formatCurrency($row['profit']) ?></td>
                <td><?php $margin = $row['revenue'] > 0 ? round(($row['profit']/$row['revenue'])*100,1) : 0; ?>
                  <span class="badge badge-<?= $margin >= 0 ? 'success' : 'danger' ?>"><?= $margin ?>%</span>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($reportData)): ?>
              <tr><td colspan="20" class="text-center text-muted py-4"><i class="bi bi-inbox d-block fs-22 mb-2"></i>No records found for the selected period.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<?php
$pageScripts = <<<'JS'
<script>
$(document).ready(function() {
  initDataTable('#reportTable', {
    pageLength: 25,
    buttons: ['csv', 'print'],
    dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>rtip"
  });
});

function exportTable(format) {
  const table = document.getElementById('reportTable');
  const rows  = table.querySelectorAll('tr');
  let csv = '';
  rows.forEach(row => {
    const cells = row.querySelectorAll('th,td');
    csv += [...cells].map(c => '"' + c.innerText.replace(/"/g,'""') + '"').join(',') + '\n';
  });
  const blob = new Blob([csv], {type: 'text/csv'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'report_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
}
</script>
JS;
include '../includes/footer.php';
?>
