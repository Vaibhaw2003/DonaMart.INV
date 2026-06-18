<?php
/**
 * Add Product
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Add Product';
$categories = db()->fetchAll('SELECT * FROM categories ORDER BY name');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $sku           = trim($_POST['sku'] ?? '');
    $barcode       = trim($_POST['barcode'] ?? '');
    $category_id   = (int)($_POST['category_id'] ?? 0) ?: null;
    $purchase_price= (float)($_POST['purchase_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $stock         = (int)($_POST['stock'] ?? 0);
    $minimum_stock = (int)($_POST['minimum_stock'] ?? 5);
    $unit          = trim($_POST['unit'] ?? 'pcs');
    $description   = trim($_POST['description'] ?? '');

    if (empty($name) || empty($sku)) {
        $error = 'Product name and SKU are required.';
    } else {
        // Check SKU uniqueness
        $exists = db()->count('SELECT COUNT(*) c FROM products WHERE sku=?', [$sku]);
        if ($exists) {
            $error = 'SKU already exists. Please use a unique SKU.';
        } else {
            // Handle image upload
            $imageFile = null;
            if (!empty($_FILES['image']['name'])) {
                $upload = uploadImage($_FILES['image'], PRODUCT_UPLOAD_PATH);
                if (!$upload['success']) {
                    $error = $upload['message'];
                } else {
                    $imageFile = $upload['filename'];
                }
            }

            if (!$error) {
                db()->insert(
                    'INSERT INTO products (name, sku, barcode, category_id, purchase_price, selling_price, stock, minimum_stock, unit, image, description)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [$name, $sku, $barcode, $category_id, $purchase_price, $selling_price, $stock, $minimum_stock, $unit, $imageFile, $description]
                );
                logActivity("Added product: $name", 'products');
                header('Location: ' . APP_URL . '/admin/products.php?saved=1');
                exit;
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<?php include '../includes/app_header.php'; ?>
  <main class="page-content">
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="bi bi-plus-circle-fill me-2 text-primary-custom"></i>Add Product</h1>
        <div class="breadcrumb-custom"><a href="products.php">Products</a><span class="sep">/</span><span>Add New</span></div>
      </div>
      <a href="products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="productForm">
      <div class="row g-3">
        <!-- Left: Main Info -->
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-info-circle me-2"></i>Product Information</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Product Name *</label>
                  <input type="text" class="form-control" name="name" value="<?= e($_POST['name'] ?? '') ?>" required placeholder="e.g. Samsung Galaxy M14" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Unit</label>
                  <select class="form-select" name="unit">
                    <?php foreach (['pcs','kg','g','l','ml','box','pack','ream','dozen','m','cm','set'] as $u): ?>
                    <option value="<?= $u ?>" <?= ($POST['unit'] ?? 'pcs') === $u ? 'selected':'' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">SKU Code *</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="sku" id="skuInput" value="<?= e($_POST['sku'] ?? '') ?>" required placeholder="e.g. SKU-ELEC-001" />
                    <button type="button" class="btn btn-outline-secondary" onclick="generateSKU()"><i class="bi bi-shuffle"></i></button>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Barcode</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="barcode" id="barcodeInput" value="<?= e($_POST['barcode'] ?? '') ?>" placeholder="e.g. 8901212193100" />
                    <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()"><i class="bi bi-upc-scan"></i></button>
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Category</label>
                  <select class="form-select" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected':'' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" name="description" rows="3" placeholder="Product description..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing -->
          <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-currency-rupee me-2"></i>Pricing & Stock</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Purchase Price (₹) *</label>
                  <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control" name="purchase_price" value="<?= e($_POST['purchase_price'] ?? '0') ?>" min="0" step="0.01" required />
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Selling Price (₹) *</label>
                  <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control" name="selling_price" value="<?= e($_POST['selling_price'] ?? '0') ?>" min="0" step="0.01" required />
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Opening Stock</label>
                  <input type="number" class="form-control" name="stock" value="<?= e($_POST['stock'] ?? '0') ?>" min="0" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Minimum Stock Alert</label>
                  <input type="number" class="form-control" name="minimum_stock" value="<?= e($_POST['minimum_stock'] ?? '5') ?>" min="0" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Image + Barcode -->
        <div class="col-lg-4">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-image me-2"></i>Product Image</h2></div>
            <div class="card-body text-center">
              <img id="imagePreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='120' height='120' fill='%23f1f5f9' rx='8'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-size='40'%3E📦%3C/text%3E%3C/svg%3E"
                   style="width:140px;height:140px;object-fit:cover;border-radius:12px;border:2px dashed var(--border);margin-bottom:12px;" />
              <div>
                <label for="imageInput" class="btn btn-outline-primary btn-sm w-100">
                  <i class="bi bi-upload me-1"></i>Upload Image
                </label>
                <input type="file" id="imageInput" name="image" accept="image/*" class="d-none" />
              </div>
              <div class="text-muted fs-12 mt-2">JPG, PNG, WEBP — Max 2MB</div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-upc me-2"></i>Barcode Preview</h2></div>
            <div class="card-body text-center">
              <svg id="barcodePreviewSvg" style="max-width:100%;"></svg>
              <div class="text-muted fs-12 mt-2">Enter barcode above to preview</div>
            </div>
          </div>

          <div class="mt-3 d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle-fill me-1"></i>Save Product
            </button>
            <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<?php
$pageScripts = <<<'JS'
<script>
initImagePreview('imageInput', 'imagePreview');

function generateSKU() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let sku = 'SKU-';
  for (let i=0;i<3;i++) sku += chars[Math.floor(Math.random()*26)];
  sku += '-';
  for (let i=0;i<4;i++) sku += Math.floor(Math.random()*10);
  document.getElementById('skuInput').value = sku;
}

function generateBarcode() {
  const code = Date.now().toString().slice(-12);
  document.getElementById('barcodeInput').value = code;
  updateBarcodePreview(code);
}

function updateBarcodePreview(code) {
  if (!code) return;
  try {
    JsBarcode('#barcodePreviewSvg', code, {
      format: 'CODE128', width: 1.5, height: 60,
      displayValue: true, fontSize: 12, margin: 6,
      background: 'transparent', lineColor: 'currentColor'
    });
  } catch(e) {}
}

document.getElementById('barcodeInput').addEventListener('input', function() {
  updateBarcodePreview(this.value);
});

// Auto-generate SKU if empty on page load
if (!document.getElementById('skuInput').value) generateSKU();
</script>
JS;
include '../includes/footer.php';
?>
