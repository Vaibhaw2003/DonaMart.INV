<?php
/**
 * Dashboard
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle = 'Dashboard';
$stats = getDashboardStats();
$settings = getSettings();

// Monthly sales chart data (last 6 months)
$monthlySales = db()->fetchAll("
    SELECT DATE_FORMAT(sale_date, '%b %Y') AS month,
           DATE_FORMAT(sale_date, '%Y-%m') AS month_key,
           SUM(grand_total) AS total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY month_key, month
    ORDER BY month_key ASC
");

// Monthly purchases (last 6 months)
$monthlyPurchases = db()->fetchAll("
    SELECT DATE_FORMAT(purchase_date, '%b %Y') AS month,
           DATE_FORMAT(purchase_date, '%Y-%m') AS month_key,
           SUM(total_amount) AS total
    FROM purchases
    WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY month_key, month
    ORDER BY month_key ASC
");

// Top 5 selling products
$topProducts = db()->fetchAll("
    SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.total) AS revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    GROUP BY si.product_id, p.name
    ORDER BY qty_sold DESC
    LIMIT 5
");

// Recent sales
$recentSales = db()->fetchAll("
    SELECT s.*, c.name AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 8
");

// Low stock products
$lowStockProducts = db()->fetchAll("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= p.minimum_stock AND p.status = 1
    ORDER BY p.stock ASC
    LIMIT 5
");

// Activity logs
$activityLogs = db()->fetchAll("
    SELECT al.*, u.name AS user_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">

  <!-- Top Header -->
  <header class="app-header">
    <button class="header-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="header-search">
      <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control" id="globalSearch" placeholder="Search products, invoices, customers…" />
      </div>
    </div>
    <div class="header-actions">
      <button class="header-btn" id="darkModeToggle" data-bs-toggle="tooltip" title="Toggle Theme">
        <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
      </button>
      <button class="header-btn position-relative" data-bs-toggle="tooltip" title="Notifications">
        <i class="bi bi-bell-fill"></i>
        <?php if ($stats['low_stock'] > 0): ?>
        <span class="notif-badge"></span>
        <?php endif; ?>
      </button>
      <div class="header-divider"></div>
      <div class="dropdown">
        <div class="header-user dropdown-toggle" data-bs-toggle="dropdown" id="userMenuToggle">
          <div class="header-avatar"><?= strtoupper(substr(currentUser()['name'], 0, 1)) ?></div>
          <span class="header-user-name d-none d-md-inline"><?= e(currentUser()['name']) ?></span>
          <i class="bi bi-chevron-down" style="font-size:10px;color:var(--text-muted);"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end mt-1">
          <li>
            <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
              <div style="font-weight:700;font-size:13px;color:var(--text);"><?= e(currentUser()['name']) ?></div>
              <div style="font-size:11.5px;color:var(--text-muted);"><?= e(ucfirst(currentUser()['role'])) ?></div>
            </div>
          </li>
          <li><a class="dropdown-item mt-1" href="<?= APP_URL ?>/admin/profile.php"><i class="bi bi-person-fill me-2 text-primary-custom"></i>My Profile</a></li>
          <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/settings.php"><i class="bi bi-gear-fill me-2 text-primary-custom"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header fade-in-up">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
          <i class="bi bi-calendar3 me-1"></i>
          <?= date('l, d F Y') ?> &nbsp;·&nbsp; Welcome back, <strong><?= e(currentUser()['name']) ?></strong>!
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="<?= APP_URL ?>/admin/purchases.php?action=new" class="btn btn-outline-secondary">
          <i class="bi bi-cart-plus"></i> New Purchase
        </a>
        <a href="<?= APP_URL ?>/admin/sales.php?action=new" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> New Invoice
        </a>
      </div>
    </div>

    <!-- Low stock alert -->
    <?php if ($stats['low_stock'] > 0): ?>
    <div class="alert-low-stock mb-4 fade-in-up" style="animation-delay:.05s;">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div class="flex-grow-1">
        <strong><?= $stats['low_stock'] ?> product(s) are running low on stock!</strong>
        <div style="font-size:12.5px;margin-top:2px;color:var(--text-muted);">Review your inventory and place purchase orders before you run out.</div>
      </div>
      <a href="<?= APP_URL ?>/admin/products.php?filter=low_stock" class="btn btn-sm btn-warning flex-shrink-0">
        <i class="bi bi-arrow-right"></i> View Products
      </a>
    </div>
    <?php endif; ?>

    <!-- Stat Cards Row 1 -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card blue h-100" style="animation-delay:.04s;">
          <div class="stat-icon"><i class="bi bi-boxes"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
            <div class="stat-change up"><i class="bi bi-check-circle-fill"></i> Active Items</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card purple h-100" style="animation-delay:.08s;">
          <div class="stat-icon"><i class="bi bi-tag-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Categories</div>
            <div class="stat-value"><?= number_format($stats['total_categories']) ?></div>
            <div class="stat-change"><i class="bi bi-grid-3x3-gap"></i> Product Groups</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card cyan h-100" style="animation-delay:.12s;">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Customers</div>
            <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> Registered</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card orange h-100" style="animation-delay:.16s;">
          <div class="stat-icon"><i class="bi bi-truck"></i></div>
          <div class="stat-body">
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?= number_format($stats['total_suppliers']) ?></div>
            <div class="stat-change"><i class="bi bi-building"></i> Vendors</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stat Cards Row 2 — Financial -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card green h-100" style="animation-delay:.20s;">
          <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value" style="font-size:20px;"><?= formatCurrency($stats['todays_sales']) ?></div>
            <div class="stat-change up"><i class="bi bi-sun-fill"></i> Today</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card blue h-100" style="animation-delay:.24s;">
          <div class="stat-icon"><i class="bi bi-calendar-month-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Monthly Sales</div>
            <div class="stat-value" style="font-size:20px;"><?= formatCurrency($stats['monthly_sales']) ?></div>
            <div class="stat-change"><i class="bi bi-calendar3"></i> This Month</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card green h-100" style="animation-delay:.28s;">
          <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="font-size:20px;"><?= formatCurrency($stats['total_revenue']) ?></div>
            <div class="stat-change up"><i class="bi bi-trophy-fill"></i> All Time</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-sm-6 col-lg-3">
        <div class="stat-card red h-100" style="animation-delay:.32s;">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value"><?= number_format($stats['low_stock']) ?></div>
            <div class="stat-change <?= $stats['low_stock'] > 0 ? 'down' : 'up' ?>">
              <i class="bi bi-<?= $stats['low_stock'] > 0 ? 'exclamation-triangle' : 'check-circle-fill' ?>"></i>
              <?= $stats['low_stock'] > 0 ? 'Needs Attention' : 'All Good' ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="card" style="animation-delay:.1s;">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-lightning-charge-fill text-primary-custom"></i> Quick Actions</h2>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/sales.php?action=new" class="quick-action-btn">
                  <i class="bi bi-receipt-cutoff"></i>
                  <span>New Invoice</span>
                </a>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/purchases.php?action=new" class="quick-action-btn">
                  <i class="bi bi-cart-plus-fill"></i>
                  <span>New Purchase</span>
                </a>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/add-product.php" class="quick-action-btn">
                  <i class="bi bi-plus-square-fill"></i>
                  <span>Add Product</span>
                </a>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/customers.php" class="quick-action-btn">
                  <i class="bi bi-person-plus-fill"></i>
                  <span>Add Customer</span>
                </a>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/reports.php" class="quick-action-btn">
                  <i class="bi bi-bar-chart-line-fill"></i>
                  <span>View Reports</span>
                </a>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= APP_URL ?>/admin/settings.php" class="quick-action-btn">
                  <i class="bi bi-gear-wide-connected"></i>
                  <span>Settings</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
      <!-- Monthly Sales Chart -->
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-graph-up-arrow text-primary-custom"></i> Sales Overview</h2>
            <span class="badge badge-primary">Last 6 Months</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:280px;">
              <canvas id="salesChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Products Doughnut -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-pie-chart-fill text-primary-custom"></i> Top Products</h2>
            <span class="badge badge-secondary">By Units Sold</span>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="chart-container" style="height:200px;">
              <canvas id="topProductsChart"></canvas>
            </div>
            <div id="topProductsLegend" class="mt-3"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase vs Sales + Low Stock -->
    <div class="row g-3 mb-4">
      <!-- Purchase vs Sales -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-bar-chart-fill text-primary-custom"></i> Purchase vs Sales</h2>
            <span class="badge badge-primary">Comparison</span>
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:240px;">
              <canvas id="pvsBChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Low Stock Alert</h2>
            <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($lowStockProducts)): ?>
            <div class="empty-state py-5">
              <i class="bi bi-check-circle-fill empty-state-icon" style="color:var(--success);"></i>
              <div class="empty-state-title">Stock Levels Are Good!</div>
              <div class="empty-state-text">All products have sufficient stock.</div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table mb-0">
                <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($lowStockProducts as $lp):
                    $status = stockStatus($lp['stock'], $lp['minimum_stock']);
                  ?>
                  <tr>
                    <td class="fw-600"><?= e($lp['name']) ?></td>
                    <td><span class="badge badge-secondary"><?= e($lp['category_name'] ?? 'N/A') ?></span></td>
                    <td>
                      <span class="fw-700 text-<?= $status['class'] ?>"><?= $lp['stock'] ?></span>
                      <div class="stock-bar" style="width:80px;">
                        <?php $pct = $lp['minimum_stock'] > 0 ? min(100, ($lp['stock']/$lp['minimum_stock'])*100) : 0; ?>
                        <div class="stock-bar-fill bg-<?= $status['class'] ?>" style="width:<?= $pct ?>%;"></div>
                      </div>
                    </td>
                    <td><span class="badge badge-<?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Sales + Activity -->
    <div class="row g-3">
      <!-- Recent Invoices -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-receipt-cutoff text-primary-custom"></i> Recent Invoices</h2>
            <a href="<?= APP_URL ?>/admin/sales.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <?php if (empty($recentSales)): ?>
            <div class="empty-state py-4">
              <i class="bi bi-receipt empty-state-icon"></i>
              <div class="empty-state-title">No Invoices Yet</div>
              <div class="empty-state-text">Create your first sale to see it here.</div>
              <a href="<?= APP_URL ?>/admin/sales.php?action=new" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Invoice
              </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th style="width:60px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentSales as $sale): ?>
                  <tr>
                    <td><span class="badge badge-primary"><?= e($sale['invoice_number']) ?></span></td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                          <?= strtoupper(substr($sale['customer_name'] ?? 'W', 0, 1)) ?>
                        </div>
                        <span class="fw-500"><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></span>
                      </div>
                    </td>
                    <td class="text-muted-custom"><?= formatDate($sale['sale_date']) ?></td>
                    <td class="fw-700" style="color:var(--success);"><?= formatCurrency($sale['grand_total']) ?></td>
                    <td>
                      <?php $pmColors = ['cash'=>'success','card'=>'primary','upi'=>'info','bank'=>'secondary','credit'=>'warning']; ?>
                      <span class="badge badge-<?= $pmColors[$sale['payment_method']] ?? 'secondary' ?>">
                        <i class="bi bi-<?= $sale['payment_method'] === 'cash' ? 'cash' : ($sale['payment_method'] === 'card' ? 'credit-card' : 'phone') ?>"></i>
                        <?= ucfirst(e($sale['payment_method'])) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= APP_URL ?>/admin/invoice.php?id=<?= $sale['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="View Invoice">
                        <i class="bi bi-eye-fill"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Activity Log -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-activity text-primary-custom"></i> Activity Log</h2>
          </div>
          <div class="card-body" style="max-height:340px;overflow-y:auto;padding:16px 20px;">
            <?php if (empty($activityLogs)): ?>
            <div class="empty-state py-3">
              <i class="bi bi-clock-history empty-state-icon"></i>
              <div class="empty-state-text">No activity yet</div>
            </div>
            <?php else: ?>
            <?php foreach ($activityLogs as $i => $log): ?>
            <div class="d-flex align-items-start gap-3 mb-3 pb-3 <?= $i < count($activityLogs)-1 ? '' : '' ?>" style="<?= $i < count($activityLogs)-1 ? 'border-bottom:1px solid var(--border-light);' : '' ?>">
              <div class="activity-dot mt-1"></div>
              <div class="flex-grow-1">
                <div class="fw-600 fs-13"><?= e($log['action']) ?></div>
                <div class="fs-12 text-muted-custom mt-1">
                  <i class="bi bi-person-circle me-1"></i><?= e($log['user_name'] ?? 'System') ?>
                  <span class="mx-1">·</span>
                  <i class="bi bi-clock me-1"></i><?= formatDateTime($log['created_at']) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<?php
// Build chart data JSON
$salesLabels    = json_encode(array_column($monthlySales, 'month'));
$salesData      = json_encode(array_column($monthlySales, 'total'));
$purchaseData   = json_encode(array_column($monthlyPurchases, 'total'));
$purchaseLabels = json_encode(array_column($monthlyPurchases, 'month'));
$topLabels      = json_encode(array_column($topProducts, 'name'));
$topData        = json_encode(array_column($topProducts, 'qty_sold'));

$pageScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
  const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)';
  const textColor = () => isDark() ? '#8896ae' : '#6b7280';

  // ---- Sales Area Chart ----
  const salesCtx = document.getElementById('salesChart').getContext('2d');
  const salesGrad = salesCtx.createLinearGradient(0, 0, 0, 280);
  salesGrad.addColorStop(0, 'rgba(79,70,229,0.3)');
  salesGrad.addColorStop(1, 'rgba(79,70,229,0.01)');

  new Chart(salesCtx, {
    type: 'line',
    data: {
      labels: $salesLabels,
      datasets: [{
        label: 'Sales (₹)',
        data: $salesData,
        borderColor: '#4f46e5',
        backgroundColor: salesGrad,
        tension: 0.45,
        fill: true,
        pointBackgroundColor: '#4f46e5',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 8,
        borderWidth: 2.5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          mode: 'index', intersect: false,
          backgroundColor: isDark() ? '#1a2035' : '#fff',
          borderColor: isDark() ? '#1e2d45' : '#e2e8f0',
          borderWidth: 1,
          titleColor: isDark() ? '#f0f4ff' : '#111827',
          bodyColor: isDark() ? '#8896ae' : '#6b7280',
          padding: 12,
          callbacks: { label: ctx => ' ₹ ' + parseFloat(ctx.raw).toLocaleString('en-IN', {minimumFractionDigits:2}) }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor(), font: { size: 11 } } },
        y: {
          beginAtZero: true,
          grid: { color: gridColor() },
          ticks: {
            color: textColor(), font: { size: 11 },
            callback: v => '₹' + (v >= 100000 ? (v/100000).toFixed(1)+'L' : v >= 1000 ? (v/1000).toFixed(1)+'K' : v)
          }
        }
      }
    }
  });

  // ---- Purchase vs Sales Bar Chart ----
  const pvsCtx = document.getElementById('pvsBChart').getContext('2d');
  new Chart(pvsCtx, {
    type: 'bar',
    data: {
      labels: $salesLabels,
      datasets: [
        {
          label: 'Sales (₹)', data: $salesData,
          backgroundColor: 'rgba(79,70,229,0.85)',
          borderRadius: 8, borderSkipped: false,
        },
        {
          label: 'Purchases (₹)', data: $purchaseData,
          backgroundColor: 'rgba(16,185,129,0.75)',
          borderRadius: 8, borderSkipped: false,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { color: textColor(), font: { size: 12 }, boxWidth: 12, padding: 16 } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor(), font: { size: 11 } } },
        y: {
          beginAtZero: true,
          grid: { color: gridColor() },
          ticks: { color: textColor(), font: { size: 11 }, callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v) }
        }
      }
    }
  });

  // ---- Top Products Doughnut ----
  const topLabels = $topLabels;
  const topData   = $topData;
  const topColors = ['#4f46e5','#10b981','#f59e0b','#ef4444','#8b5cf6'];
  if (topLabels.length > 0) {
    const tpCtx = document.getElementById('topProductsChart').getContext('2d');
    new Chart(tpCtx, {
      type: 'doughnut',
      data: {
        labels: topLabels,
        datasets: [{ data: topData, backgroundColor: topColors, borderWidth: 0, hoverOffset: 8 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '74%',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' units' } }
        }
      }
    });
    const legend = document.getElementById('topProductsLegend');
    topLabels.forEach((label, i) => {
      legend.innerHTML += `<div class="d-flex align-items-center gap-2 mb-2" style="font-size:12.5px;">
        <div style="width:10px;height:10px;border-radius:50%;background:\${topColors[i]};flex-shrink:0;"></div>
        <span class="flex-grow-1 text-truncate" style="color:var(--text);" title="\${label}">\${label}</span>
        <span class="fw-700" style="color:var(--text);">\${topData[i]}<span style="color:var(--text-muted);font-weight:400;"> units</span></span>
      </div>`;
    });
  } else {
    document.getElementById('topProductsChart').closest('.chart-container').innerHTML =
      '<div class="empty-state py-4"><i class="bi bi-bar-chart-fill empty-state-icon"></i><div class="empty-state-text">No sales data yet</div></div>';
  }
});
</script>
JS;

include '../includes/footer.php';
?>
