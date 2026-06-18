<?php
/**
 * Settings
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();
requireRole('admin','manager');

$pageTitle = 'Settings';
$settings  = getSettings();
$message   = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName   = trim($_POST['company_name'] ?? '');
    $gstNumber     = trim($_POST['gst_number'] ?? '');
    $invoicePrefix = trim($_POST['invoice_prefix'] ?? 'INV');
    $currency      = trim($_POST['currency'] ?? 'INR');
    $currencySymbol= trim($_POST['currency_symbol'] ?? '₹');
    $gstRate       = (float)($_POST['gst_rate'] ?? 18);
    $address       = trim($_POST['address'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $theme         = $_POST['theme'] ?? 'light';

    if (empty($companyName)) { $message = 'Company name is required.'; $msgType = 'danger'; }
    else {
        // Handle logo upload
        $logo = $settings['logo'];
        if (!empty($_FILES['logo']['name'])) {
            $upload = uploadImage($_FILES['logo'], UPLOAD_PATH . 'logos/', 1024);
            if ($upload['success']) {
                if ($logo && file_exists(UPLOAD_PATH . 'logos/' . $logo)) unlink(UPLOAD_PATH . 'logos/' . $logo);
                $logo = $upload['filename'];
            } else {
                $message = $upload['message']; $msgType = 'danger';
            }
        }

        if (!$message) {
            $exists = db()->count('SELECT COUNT(*) c FROM settings');
            if ($exists) {
                db()->execute(
                    'UPDATE settings SET company_name=?,logo=?,gst_number=?,invoice_prefix=?,currency=?,currency_symbol=?,gst_rate=?,address=?,phone=?,email=?,theme=? WHERE id=1',
                    [$companyName,$logo,$gstNumber,$invoicePrefix,$currency,$currencySymbol,$gstRate,$address,$phone,$email,$theme]
                );
            } else {
                db()->insert(
                    'INSERT INTO settings (company_name,logo,gst_number,invoice_prefix,currency,currency_symbol,gst_rate,address,phone,email,theme) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [$companyName,$logo,$gstNumber,$invoicePrefix,$currency,$currencySymbol,$gstRate,$address,$phone,$email,$theme]
                );
            }
            logActivity('Updated system settings','settings');
            $message = 'Settings saved successfully!';
            $settings = getSettings();
        }
    }
}

include '../includes/header.php'; include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div><h1 class="page-title"><i class="bi bi-gear-fill me-2 text-primary-custom"></i>Settings</h1>
      <p class="page-subtitle">Configure your system preferences</p></div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show alert-auto-dismiss"><i class="bi bi-check-circle-fill me-2"></i><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <!-- Company Info -->
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-building me-2"></i>Company Information</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Company Name *</label>
                  <input type="text" class="form-control" name="company_name" value="<?= e($settings['company_name'] ?? '') ?>" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">GST Number</label>
                  <input type="text" class="form-control" name="gst_number" value="<?= e($settings['gst_number'] ?? '') ?>" placeholder="27AAPFU0939F1ZV" />
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <textarea class="form-control" name="address" rows="2"><?= e($settings['address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone</label>
                  <input type="text" class="form-control" name="phone" value="<?= e($settings['phone'] ?? '') ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" value="<?= e($settings['email'] ?? '') ?>" />
                </div>
              </div>
            </div>
          </div>

          <!-- Invoice Settings -->
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-receipt me-2"></i>Invoice & Tax Settings</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Invoice Prefix</label>
                  <input type="text" class="form-control" name="invoice_prefix" value="<?= e($settings['invoice_prefix'] ?? 'INV') ?>" maxlength="10" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Currency Code</label>
                  <input type="text" class="form-control" name="currency" value="<?= e($settings['currency'] ?? 'INR') ?>" maxlength="5" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Currency Symbol</label>
                  <input type="text" class="form-control" name="currency_symbol" value="<?= e($settings['currency_symbol'] ?? '₹') ?>" maxlength="5" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">GST Rate (%)</label>
                  <div class="input-group">
                    <input type="number" class="form-control" name="gst_rate" value="<?= e($settings['gst_rate'] ?? 18) ?>" min="0" max="100" step="0.01" />
                    <span class="input-group-text">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Theme -->
          <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-palette me-2"></i>Appearance</h2></div>
            <div class="card-body">
              <label class="form-label">Default Theme</label>
              <div class="d-flex gap-3">
                <label class="d-flex align-items-center gap-2 cursor-pointer" style="cursor:pointer;">
                  <input type="radio" name="theme" value="light" <?= ($settings['theme']??'light')==='light'?'checked':'' ?> />
                  <div style="width:32px;height:20px;background:#f1f5f9;border:1px solid var(--border);border-radius:4px;"></div>
                  Light Mode
                </label>
                <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                  <input type="radio" name="theme" value="dark" <?= ($settings['theme']??'')==='dark'?'checked':'' ?> />
                  <div style="width:32px;height:20px;background:#0f172a;border:1px solid var(--border);border-radius:4px;"></div>
                  Dark Mode
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Logo -->
        <div class="col-lg-4">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-image me-2"></i>Company Logo</h2></div>
            <div class="card-body text-center">
              <?php
              $logoPath = UPLOAD_PATH . 'logos/' . ($settings['logo'] ?? '');
              $logoSrc  = $settings['logo'] && file_exists($logoPath)
                ? APP_URL . '/uploads/logos/' . $settings['logo']
                : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='120' height='120' fill='%23f1f5f9' rx='8'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-size='40'%3E📦%3C/text%3E%3C/svg%3E";
              ?>
              <img id="logoPreview" src="<?= $logoSrc ?>" style="width:120px;height:120px;object-fit:contain;border-radius:12px;border:2px dashed var(--border);margin-bottom:12px;" />
              <label for="logoInput" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-upload me-1"></i>Upload Logo</label>
              <input type="file" id="logoInput" name="logo" accept="image/*" class="d-none" />
              <div class="text-muted fs-12 mt-2">PNG / SVG recommended</div>
            </div>
          </div>

          <!-- Invoice Preview -->
          <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-eye me-2"></i>Invoice No. Preview</h2></div>
            <div class="card-body text-center">
              <div class="badge badge-primary" style="font-size:14px;padding:8px 16px;" id="invoicePreview">
                <?= e($settings['invoice_prefix'] ?? 'INV') ?>-<?= date('Y') ?>-0001
              </div>
              <div class="text-muted fs-12 mt-2">Next invoice number preview</div>
            </div>
          </div>

          <div class="d-grid mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Save Settings</button>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<?php
$pageScripts = <<<'JS'
<script>
initImagePreview('logoInput', 'logoPreview');
document.querySelector('input[name="invoice_prefix"]').addEventListener('input', function() {
  document.getElementById('invoicePreview').textContent = this.value + '-' + new Date().getFullYear() + '-0001';
});
</script>
JS;
include '../includes/footer.php';
?>
