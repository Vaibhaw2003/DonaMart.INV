<?php
/**
 * Sidebar Navigation
 * Smart Inventory & Billing Management System
 */
require_once __DIR__ . '/auth.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function isActive(array $pages): string {
    global $currentPage;
    return in_array($currentPage, $pages) ? 'active' : '';
}

// Low stock count for badge
$lowStockCount = db()->count('SELECT COUNT(*) c FROM products WHERE stock <= minimum_stock AND status = 1');
?>

<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside class="sidebar" id="appSidebar">
  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <i class="bi bi-box-seam-fill"></i>
    </div>
    <div class="sidebar-brand-text">Smart<span>INV</span></div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <!-- Main -->
    <div class="nav-section-label">Main</div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/dashboard.php" class="sidebar-link <?= isActive(['dashboard.php']) ?>">
        <i class="bi bi-grid-1x2-fill nav-icon"></i>
        <span>Dashboard</span>
      </a>
    </div>

    <!-- Inventory -->
    <div class="nav-section-label">Inventory</div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/products.php" class="sidebar-link <?= isActive(['products.php','add-product.php','edit-product.php']) ?>">
        <i class="bi bi-boxes nav-icon"></i>
        <span>Products</span>
        <?php if ($lowStockCount > 0): ?>
          <span class="badge bg-warning text-dark"><?= $lowStockCount ?></span>
        <?php endif; ?>
      </a>
    </div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/categories.php" class="sidebar-link <?= isActive(['categories.php']) ?>">
        <i class="bi bi-tag-fill nav-icon"></i>
        <span>Categories</span>
      </a>
    </div>

    <!-- Parties -->
    <div class="nav-section-label">Parties</div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/suppliers.php" class="sidebar-link <?= isActive(['suppliers.php']) ?>">
        <i class="bi bi-truck nav-icon"></i>
        <span>Suppliers</span>
      </a>
    </div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/customers.php" class="sidebar-link <?= isActive(['customers.php']) ?>">
        <i class="bi bi-people-fill nav-icon"></i>
        <span>Customers</span>
      </a>
    </div>

    <!-- Transactions -->
    <div class="nav-section-label">Transactions</div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/purchases.php" class="sidebar-link <?= isActive(['purchases.php']) ?>">
        <i class="bi bi-cart-plus-fill nav-icon"></i>
        <span>Purchases</span>
      </a>
    </div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/sales.php" class="sidebar-link <?= isActive(['sales.php']) ?>">
        <i class="bi bi-receipt nav-icon"></i>
        <span>Sales & Invoices</span>
      </a>
    </div>

    <!-- Analytics -->
    <div class="nav-section-label">Analytics</div>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/reports.php" class="sidebar-link <?= isActive(['reports.php']) ?>">
        <i class="bi bi-bar-chart-line-fill nav-icon"></i>
        <span>Reports</span>
      </a>
    </div>

    <!-- Admin -->
    <?php if (hasRole('admin', 'manager')): ?>
    <div class="nav-section-label">Administration</div>

    <?php if (hasRole('admin')): ?>
    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/register.php" class="sidebar-link <?= isActive(['register.php']) ?>">
        <i class="bi bi-person-plus-fill nav-icon"></i>
        <span>Manage Users</span>
      </a>
    </div>
    <?php endif; ?>

    <div class="sidebar-item">
      <a href="<?= APP_URL ?>/admin/settings.php" class="sidebar-link <?= isActive(['settings.php']) ?>">
        <i class="bi bi-gear-fill nav-icon"></i>
        <span>Settings</span>
      </a>
    </div>
    <?php endif; ?>

  </nav>

  <!-- Footer / User -->
  <div class="sidebar-footer">
    <a href="<?= APP_URL ?>/admin/profile.php" class="sidebar-user text-decoration-none">
      <?php if ($user['avatar']): ?>
        <img src="<?= e(USER_UPLOAD_URL . $user['avatar']) ?>" class="sidebar-user-avatar" alt="Avatar" />
      <?php else: ?>
        <div class="sidebar-user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($user['name']) ?></div>
        <div class="sidebar-user-role"><?= e($user['role']) ?></div>
      </div>
      <i class="bi bi-chevron-right" style="color:#475569;font-size:11px;"></i>
    </a>
  </div>
</aside>
