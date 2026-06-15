<?php
/**
 * Invoice View — Print / PDF / Email
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: sales.php'); exit; }

$sale = db()->fetchOne("
    SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    WHERE s.id = ?
", [$id]);
if (!$sale) { header('Location: sales.php'); exit; }

$saleItems = db()->fetchAll("
    SELECT si.*, p.name AS product_name, p.sku, p.unit
    FROM sale_items si
    JOIN products p ON p.id = si.product_id
    WHERE si.sale_id = ?
", [$id]);

$settings  = getSettings();
$pageTitle = 'Invoice ' . $sale['invoice_number'];

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="main-content">
  <header class="app-header no-print">
    <button class="header-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="header-actions ms-auto">
      <button class="header-btn" id="darkModeToggle"><i class="bi bi-moon-stars-fill" id="darkModeIcon"></i></button>
      <a href="<?= APP_URL ?>/logout.php" class="header-btn"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </header>
  <main class="page-content">

    <div class="page-header no-print">
      <div>
        <h1 class="page-title"><i class="bi bi-receipt-cutoff me-2 text-primary-custom"></i>Invoice</h1>
        <div class="breadcrumb-custom"><a href="sales.php">Sales</a><span class="sep">/</span><span><?= e($sale['invoice_number']) ?></span></div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="sales.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer-fill me-1"></i>Print</button>
        <a href="../ajax/download_pdf.php?id=<?= $id ?>" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf-fill me-1"></i>PDF</a>
        <button onclick="emailInvoice(<?= $id ?>)" class="btn btn-success btn-sm" id="emailBtn"><i class="bi bi-envelope-fill me-1"></i>Email</button>
      </div>
    </div>

    <?php if (isset($_GET['new'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss no-print">
      <i class="bi bi-check-circle-fill me-2"></i>Invoice <strong><?= e($sale['invoice_number']) ?></strong> created successfully! Stock has been updated.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Invoice Card -->
    <div class="card invoice-card" style="max-width:860px;margin:0 auto;" id="invoicePrintArea">
      <!-- Header -->
      <div style="background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#fff;padding:32px;border-radius:12px 12px 0 0;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
          <div>
            <div class="d-flex align-items-center gap-3 mb-2">
              <div style="width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,#2563eb,#818cf8);display:flex;align-items:center;justify-content:center;font-size:22px;">📦</div>
              <div>
                <div style="font-size:20px;font-weight:800;"><?= e($settings['company_name']) ?></div>
                <div style="font-size:12px;opacity:.65;">GST: <?= e($settings['gst_number'] ?? 'N/A') ?></div>
              </div>
            </div>
            <?php if ($settings['address']): ?><div style="font-size:12px;opacity:.6;"><?= e($settings['address']) ?></div><?php endif; ?>
            <?php if ($settings['phone']): ?><div style="font-size:12px;opacity:.6;"><?= e($settings['phone']) ?> · <?= e($settings['email'] ?? '') ?></div><?php endif; ?>
          </div>
          <div class="text-end">
            <div style="font-size:11px;opacity:.5;text-transform:uppercase;letter-spacing:1px;">Invoice</div>
            <div style="font-size:28px;font-weight:900;letter-spacing:-1px;"><?= e($sale['invoice_number']) ?></div>
            <div style="font-size:12px;opacity:.65;">Date: <?= formatDate($sale['sale_date']) ?></div>
          </div>
        </div>
      </div>

      <div class="card-body" style="padding:28px;">
        <!-- Bill To / Payment Info -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:8px;">Bill To</div>
            <div class="fw-700 fs-13"><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></div>
            <?php if ($sale['customer_phone']): ?><div class="text-muted fs-12"><?= e($sale['customer_phone']) ?></div><?php endif; ?>
            <?php if ($sale['customer_email']): ?><div class="text-muted fs-12"><?= e($sale['customer_email']) ?></div><?php endif; ?>
            <?php if ($sale['customer_address']): ?><div class="text-muted fs-12"><?= e($sale['customer_address']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6 text-md-end">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:8px;">Payment Info</div>
            <div class="fs-12"><strong>Method:</strong> <?= ucfirst(e($sale['payment_method'])) ?></div>
            <div class="fs-12"><strong>Status:</strong> <span class="badge badge-success">Paid</span></div>
            <?php if ($sale['notes']): ?><div class="fs-12 mt-1 text-muted"><?= e($sale['notes']) ?></div><?php endif; ?>

            <!-- QR Code -->
            <div id="invoiceQR" class="mt-2 d-inline-block"></div>
          </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
          <table class="table" style="border:1px solid var(--border);border-radius:8px;overflow:hidden;">
            <thead style="background:var(--surface-2);">
              <tr>
                <th style="padding:10px 14px;">#</th>
                <th style="padding:10px 14px;">Product</th>
                <th style="padding:10px 14px;">SKU</th>
                <th style="padding:10px 14px;text-align:right;">Qty</th>
                <th style="padding:10px 14px;text-align:right;">Unit Price</th>
                <th style="padding:10px 14px;text-align:right;">Discount</th>
                <th style="padding:10px 14px;text-align:right;">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($saleItems as $i => $item): ?>
              <tr>
                <td style="padding:10px 14px;"><?= $i+1 ?></td>
                <td style="padding:10px 14px;"><strong><?= e($item['product_name']) ?></strong><div class="text-muted fs-12"><?= e($item['unit']) ?></div></td>
                <td style="padding:10px 14px;"><code style="background:var(--bg);padding:1px 5px;border-radius:3px;font-size:11px;"><?= e($item['sku']) ?></code></td>
                <td style="padding:10px 14px;text-align:right;"><?= $item['quantity'] ?></td>
                <td style="padding:10px 14px;text-align:right;"><?= formatCurrency($item['price']) ?></td>
                <td style="padding:10px 14px;text-align:right;" class="text-danger"><?= $item['discount'] > 0 ? '- '.formatCurrency($item['discount']) : '—' ?></td>
                <td style="padding:10px 14px;text-align:right;" class="fw-700"><?= formatCurrency($item['total']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Totals -->
        <div class="d-flex justify-content-end">
          <div style="min-width:280px;">
            <div class="d-flex justify-content-between py-2 border-bottom">
              <span class="text-muted">Subtotal</span>
              <span class="fw-600"><?= formatCurrency($sale['subtotal']) ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
            <div class="d-flex justify-content-between py-2 border-bottom text-danger">
              <span>Discount</span>
              <span class="fw-600">- <?= formatCurrency($sale['discount']) ?></span>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between py-2 border-bottom text-success">
              <span>GST (<?= $settings['gst_rate'] ?? 18 ?>%)</span>
              <span class="fw-600">+ <?= formatCurrency($sale['gst']) ?></span>
            </div>
            <div class="d-flex justify-content-between py-3" style="background:var(--primary);color:#fff;padding:12px 0;border-radius:8px;margin-top:4px;padding:10px 16px;">
              <span class="fw-700">Grand Total</span>
              <span class="fw-800" style="font-size:20px;"><?= formatCurrency($sale['grand_total']) ?></span>
            </div>
          </div>
        </div>

        <!-- Footer Note -->
        <div class="mt-4 pt-3 border-top text-center">
          <div class="fw-700 mb-1" style="font-size:15px;">Thank you for your business! 🙏</div>
          <div class="text-muted fs-12">This is a computer-generated invoice. No signature required.</div>
          <?php if ($settings['email']): ?><div class="text-muted fs-12">For queries: <?= e($settings['email']) ?> · <?= e($settings['phone'] ?? '') ?></div><?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
$invNo = e($sale['invoice_number']);
$grandTotal = formatCurrency($sale['grand_total']);
$pageScripts = <<<JS
<script>
// Generate QR code for invoice
try {
  new QRCode(document.getElementById('invoiceQR'), {
    text: 'Invoice: $invNo | Total: $grandTotal | Date: {$sale['sale_date']}',
    width: 80, height: 80,
    colorDark: '#0f172a', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
} catch(e) {}

function emailInvoice(id) {
  const email = prompt('Enter customer email address:');
  if (!email) return;
  const btn = document.getElementById('emailBtn');
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';
  btn.disabled = true;
  Ajax.post('../ajax/send_invoice_email.php', {sale_id: id, email: email}, json => {
    SmartINV.toast('Invoice sent to ' + email, 'success');
    btn.innerHTML = '<i class="bi bi-envelope-fill me-1"></i>Email';
    btn.disabled = false;
  }, err => {
    SmartINV.toast(err.message || 'Failed to send email', 'error');
    btn.innerHTML = '<i class="bi bi-envelope-fill me-1"></i>Email';
    btn.disabled = false;
  });
}
</script>
JS;
include '../includes/footer.php';
?>
