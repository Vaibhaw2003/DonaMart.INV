<?php
/**
 * Edit Product
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: products.php'); exit; }

$product = db()->fetchOne('SELECT * FROM products WHERE id=?', [$id]);
if (!$product) { header('Location: products.php'); exit; }

$pageTitle  = 'Edit Product';
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
        $dupCheck = db()->count('SELECT COUNT(*) c FROM products WHERE sku=? AND id!=?', [$sku, $id]);
        if ($dupCheck) {
            $error = 'SKU already in use by another product.';
        } else {
            $imageFile = $product['image'];
            if (!empty($_FILES['image']['name'])) {
                $upload = uploadImage($_FILES['image'], PRODUCT_UPLOAD_PATH);
                if (!$upload['success']) {
                    $error = $upload['message'];
                } else {
                    // Delete old image
                    if ($imageFile && file_exists(PRODUCT_UPLOAD_PATH . $imageFile)) unlink(PRODUCT_UPLOAD_PATH . $imageFile);
                    $imageFile = $upload['filename'];
                }
            }
            if (!$error) {
                db()->execute(
                    'UPDATE products SET name=?, sku=?, barcode=?, category_id=?, purchase_price=?, selling_price=?, stock=?, minimum_stock=?, unit=?, image=?, description=? WHERE id=?',
                    [$name, $sku, $barcode, $category_id, $purchase_price, $selling_price, $stock, $minimum_stock, $unit, $imageFile, $description, $id]
                );
                logActivity("Updated product: $name", 'products');
                header('Location: ' . APP_URL . '/admin/products.php?saved=1');
                exit;
            }
        }
    }
    // Repopulate
    $product = array_merge($product, $_POST);
}

include '../includes/header.php';
include '../includes/sidebar.php';
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
    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="bi bi-pencil-square me-2 text-primary-custom"></i>Edit Product</h1>
        <div class="breadcrumb-custom"><a href="products.php">Products</a><span class="sep">/</span><span>Edit</span></div>
      </div>
      <a href="products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-info-circle me-2"></i>Product Information</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Product Name *</label>
                  <input type="text" class="form-control" name="name" value="<?= e($product['name']) ?>" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Unit</label>
                  <select class="form-select" name="unit">
                    <?php foreach (['pcs','kg','g','l','ml','box','pack','ream','dozen','m','cm','set'] as $u): ?>
                    <option value="<?= $u ?>" <?= $product['unit'] === $u ? 'selected':'' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">SKU Code *</label>
                  <input type="text" class="form-control" name="sku" value="<?= e($product['sku']) ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Barcode</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="barcode" id="barcodeInput" value="<?= e($product['barcode']) ?>" />
                    <button type="button" class="btn btn-outline-secondary" onclick="previewBarcode()"><i class="bi bi-upc-scan"></i></button>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Category</label>
                  <select class="form-select" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected':'' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" name="description" rows="3"><?= e($product['description']) ?></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-currency-rupee me-2"></i>Pricing & Stock</h2></div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Purchase Price (₹)</label>
                  <div class="input-group"><span class="input-group-text">₹</span>
                  <input type="number" class="form-control" name="purchase_price" value="<?= e($product['purchase_price']) ?>" min="0" step="0.01" /></div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Selling Price (₹)</label>
                  <div class="input-group"><span class="input-group-text">₹</span>
                  <input type="number" class="form-control" name="selling_price" value="<?= e($product['selling_price']) ?>" min="0" step="0.01" /></div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Current Stock</label>
                  <input type="number" class="form-control" name="stock" value="<?= e($product['stock']) ?>" min="0" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Minimum Stock Alert</label>
                  <input type="number" class="form-control" name="minimum_stock" value="<?= e($product['minimum_stock']) ?>" min="0" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-image me-2"></i>Product Image</h2></div>
            <div class="card-body text-center">
              <?php
              $imgSrc = $product['image'] && file_exists(PRODUCT_UPLOAD_PATH . $product['image'])
                ? PRODUCT_UPLOAD_URL . $product['image']
                : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='120' height='120' fill='%23f1f5f9' rx='8'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' font-size='40'%3E📦%3C/text%3E%3C/svg%3E";
              ?>
              <img id="imagePreview" src="<?= $imgSrc ?>"
                   style="width:140px;height:140px;object-fit:cover;border-radius:12px;border:2px dashed var(--border);margin-bottom:12px;" />
              <div>
                <label for="imageInput" class="btn btn-outline-primary btn-sm w-100">
                  <i class="bi bi-upload me-1"></i>Change Image
                </label>
                <input type="file" id="imageInput" name="image" accept="image/*" class="d-none" />
              </div>
            </div>
          </div>
          <div class="card mb-3">
            <div class="card-header"><h2 class="card-title"><i class="bi bi-upc me-2"></i>Barcode</h2></div>
            <div class="card-body text-center">
              <svg id="barcodePreviewSvg" style="max-width:100%;"></svg>
            </div>
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill me-1"></i>Update Product</button>
            <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </div>
      </div>
    </form>
  </main>
</div>

<?php
$barcodeVal = e($product['barcode'] ?: $product['sku']);
$pageScripts = <<<JS
<script>
initImagePreview('imageInput', 'imagePreview');
function previewBarcode() {
  const code = document.getElementById('barcodeInput').value;
  if (!code) return;
  try {
    JsBarcode('#barcodePreviewSvg', code, { format:'CODE128', width:1.5, height:60, displayValue:true, fontSize:12, margin:6, background:'transparent', lineColor:'currentColor' });
  } catch(e) {}
}
// Auto preview on load
window.addEventListener('DOMContentLoaded', function() {
  const code = '$barcodeVal';
  if (code) {
    try {
      JsBarcode('#barcodePreviewSvg', code, { format:'CODE128', width:1.5, height:60, displayValue:true, fontSize:12, margin:6, background:'transparent', lineColor:'currentColor' });
    } catch(e) {}
  }
});
document.getElementById('barcodeInput').addEventListener('input', previewBarcode);
</script>
JS;
include '../includes/footer.php';
?>
