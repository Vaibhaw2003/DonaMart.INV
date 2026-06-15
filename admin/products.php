<?php
/**
 * Products Management
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Products';

// Delete product
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $prod  = db()->fetchOne('SELECT name, image FROM products WHERE id=?', [$delId]);
    if ($prod && $prod['image'] && file_exists(PRODUCT_UPLOAD_PATH . $prod['image'])) {
        unlink(PRODUCT_UPLOAD_PATH . $prod['image']);
    }
    db()->execute('DELETE FROM products WHERE id=?', [$delId]);
    logActivity("Deleted product: " . ($prod['name'] ?? ''), 'products');
    header('Location: ' . APP_URL . '/admin/products.php?deleted=1');
    exit;
}

// Filters
$search    = trim($_GET['search'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);
$filter    = $_GET['filter'] ?? '';

$where  = ['p.status = 1'];
$params = [];

if ($search) {
    $where[]  = '(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($catFilter) {
    $where[]  = 'p.category_id = ?';
    $params[] = $catFilter;
}
if ($filter === 'low_stock') {
    $where[] = 'p.stock <= p.minimum_stock';
} elseif ($filter === 'out_of_stock') {
    $where[] = 'p.stock = 0';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$products = db()->fetchAll("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSQL
    ORDER BY p.created_at DESC
", $params);

$categories = db()->fetchAll('SELECT * FROM categories ORDER BY name');
$settings   = getSettings();

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
        <h1 class="page-title"><i class="bi bi-boxes me-2 text-primary-custom"></i>Products</h1>
        <p class="page-subtitle">Manage your product inventory</p>
      </div>
      <a href="<?= APP_URL ?>/admin/add-product.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Product
      </a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-check-circle-fill me-2"></i>Product deleted successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-dismiss">
      <i class="bi bi-check-circle-fill me-2"></i>Product saved successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3">
      <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
          <div class="flex-grow-1" style="min-width:200px;">
            <input type="text" class="form-control form-control-sm" name="search" placeholder="Search name, SKU, barcode..." value="<?= e($search) ?>" />
          </div>
          <select class="form-select form-select-sm" name="category" style="width:180px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="form-select form-select-sm" name="filter" style="width:160px;">
            <option value="">All Stock</option>
            <option value="low_stock" <?= $filter==='low_stock'?'selected':'' ?>>Low Stock</option>
            <option value="out_of_stock" <?= $filter==='out_of_stock'?'selected':'' ?>>Out of Stock</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
          <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
        </form>
      </div>
    </div>

    <!-- Products Table -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title"><i class="bi bi-table me-2"></i>Product List <span class="badge badge-primary ms-1"><?= count($products) ?></span></h2>
        <div class="d-flex gap-2">
          <a href="<?= APP_URL ?>/admin/add-product.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg me-1"></i>Add
          </a>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table" id="productsTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Purchase ₹</th>
                <th>Selling ₹</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $i => $p):
                $status = stockStatus($p['stock'], $p['minimum_stock']);
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <?php if ($p['image'] && file_exists(PRODUCT_UPLOAD_PATH . $p['image'])): ?>
                  <img src="<?= PRODUCT_UPLOAD_URL . e($p['image']) ?>" style="width:42px;height:42px;object-fit:cover;border-radius:8px;" />
                  <?php else: ?>
                  <div style="width:42px;height:42px;border-radius:8px;background:linear-gradient(135deg,#dbeafe,#ede9fe);display:flex;align-items:center;justify-content:center;font-size:18px;">📦</div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-700"><?= e($p['name']) ?></div>
                  <div class="fs-12 text-muted"><?= e($p['unit']) ?></div>
                </td>
                <td><code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:12px;"><?= e($p['sku']) ?></code></td>
                <td><?= e($p['category_name'] ?? '—') ?></td>
                <td><?= formatCurrency($p['purchase_price']) ?></td>
                <td class="fw-700 text-primary-custom"><?= formatCurrency($p['selling_price']) ?></td>
                <td>
                  <span class="fw-700 text-<?= $status['class'] ?>"><?= $p['stock'] ?></span>
                  <span class="text-muted fs-12"> <?= e($p['unit']) ?></span>
                  <div class="stock-bar" style="width:80px;">
                    <?php $pct = $p['minimum_stock'] > 0 ? min(100, round(($p['stock'] / max($p['minimum_stock'], 1)) * 100)) : 100; ?>
                    <div class="stock-bar-fill bg-<?= $status['class'] ?>" style="width:<?= $pct ?>%;"></div>
                  </div>
                </td>
                <td>
                  <span class="badge badge-<?= $status['class'] ?>">
                    <i class="bi <?= $status['icon'] ?> me-1"></i><?= $status['label'] ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="<?= APP_URL ?>/admin/edit-product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="Edit">
                      <i class="bi bi-pencil-fill"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-info btn-icon" onclick="showBarcode('<?= e($p['barcode'] ?: $p['sku']) ?>','<?= e($p['name']) ?>')" data-bs-toggle="tooltip" title="Barcode">
                      <i class="bi bi-upc-scan"></i>
                    </button>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon"
                       onclick="return confirm('Delete product: <?= e($p['name']) ?>?')" data-bs-toggle="tooltip" title="Delete">
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

<!-- Barcode Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i>Product Barcode</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="barcodeProductName" class="fw-700 mb-3"></div>
        <svg id="barcodeDisplay"></svg>
        <div id="barcodeValue" class="fs-12 text-muted mt-2"></div>
      </div>
      <div class="modal-footer justify-content-center">
        <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<JS
<script>
$(document).ready(function() {
  initDataTable('#productsTable', {
    order: [[0, 'desc']],
    columnDefs: [{ orderable: false, targets: [1, 9] }]
  });
});

function showBarcode(code, name) {
  try {
    JsBarcode('#barcodeDisplay', code, {
      format: 'CODE128',
      width: 2, height: 80,
      displayValue: true,
      fontSize: 14,
      margin: 10,
      background: '#ffffff',
      lineColor: '#000000'
    });
    document.getElementById('barcodeProductName').textContent = name;
    document.getElementById('barcodeValue').textContent = 'Code: ' + code;
    new bootstrap.Modal(document.getElementById('barcodeModal')).show();
  } catch(e) {
    SmartINV.toast('Invalid barcode value', 'error');
  }
}
</script>
JS;
include '../includes/footer.php';
?>
