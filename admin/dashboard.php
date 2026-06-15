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
        <input type="text" class="form-control" placeholder="Search products, invoices..." />
      </div>
    </div>
    <div class="header-actions">
      <button class="header-btn" id="darkModeToggle" data-bs-toggle="tooltip" title="Toggle Dark Mode">
        <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
      </button>
      <button class="header-btn position-relative" data-bs-toggle="tooltip" title="Notifications">
        <i class="bi bi-bell-fill"></i>
        <?php if ($stats['low_stock'] > 0): ?>
        <span class="notif-badge"></span>
        <?php endif; ?>
      </button>
      <div class="dropdown">
        <div class="header-user dropdown-toggle" data-bs-toggle="dropdown">
          <div class="header-avatar"><?= strtoupper(substr(currentUser()['name'], 0, 1)) ?></div>
          <span class="header-user-name d-none d-md-inline"><?= e(currentUser()['name']) ?></span>
        </div>
        <ul class="dropdown-menu dropdown-menu-end" style="border:1px solid var(--border);background:var(--surface);">
          <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/profile.php"><i class="bi bi-person-fill me-2"></i>My Profile</a></li>
          <li><a class="dropdown-item" href="<?= APP_URL ?>/admin/settings.php"><i class="bi bi-gear-fill me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider" style="border-color:var(--border);"></li>
          <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <main class="page-content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= e(currentUser()['name']) ?>! Here's your business overview.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/admin/sales.php?action=new" class="btn btn-primary">
          <i class="bi bi-plus-lg"></i> New Invoice
        </a>
        <a href="<?= APP_URL ?>/admin/purchases.php?action=new" class="btn btn-outline-secondary">
          <i class="bi bi-cart-plus"></i> New Purchase
        </a>
      </div>
    </div>

    <!-- Low stock alert -->
    <?php if ($stats['low_stock'] > 0): ?>
    <div class="alert-low-stock mb-4 fade-in-up">
      <i class="bi bi-exclamation-triangle-fill fs-22"></i>
      <div>
        <strong><?= $stats['low_stock'] ?> product(s) are running low on stock!</strong>
        <div style="font-size:13px;margin-top:2px;">Review your inventory and place purchase orders.</div>
      </div>
      <a href="<?= APP_URL ?>/admin/products.php?filter=low_stock" class="btn btn-sm btn-warning ms-auto">View Products</a>
    </div>
    <?php endif; ?>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card blue h-100">
          <div class="stat-icon"><i class="bi bi-boxes"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> Active Items</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card purple h-100">
          <div class="stat-icon"><i class="bi bi-tag-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Categories</div>
            <div class="stat-value"><?= number_format($stats['total_categories']) ?></div>
            <div class="stat-change"><i class="bi bi-grid"></i> Product Groups</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card cyan h-100">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Customers</div>
            <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> Registered</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card orange h-100">
          <div class="stat-icon"><i class="bi bi-truck"></i></div>
          <div class="stat-body">
            <div class="stat-label">Suppliers</div>
            <div class="stat-value"><?= number_format($stats['total_suppliers']) ?></div>
            <div class="stat-change"><i class="bi bi-building"></i> Vendors</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card green h-100">
          <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value" style="font-size:18px;"><?= formatCurrency($stats['todays_sales']) ?></div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> Today</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card blue h-100">
          <div class="stat-icon"><i class="bi bi-calendar-month-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Monthly Sales</div>
            <div class="stat-value" style="font-size:18px;"><?= formatCurrency($stats['monthly_sales']) ?></div>
            <div class="stat-change"><i class="bi bi-calendar"></i> This Month</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card green h-100">
          <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="font-size:18px;"><?= formatCurrency($stats['total_revenue']) ?></div>
            <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> All Time</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card red h-100">
          <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value"><?= number_format($stats['low_stock']) ?></div>
            <div class="stat-change <?= $stats['low_stock'] > 0 ? 'down' : '' ?>">
              <i class="bi bi-<?= $stats['low_stock'] > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
              <?= $stats['low_stock'] > 0 ? 'Needs Attention' : 'All Good' ?>
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
          </div>
          <div class="card-body">
            <div class="chart-container" style="height:230px;">
              <canvas id="topProductsChart"></canvas>
            </div>
            <div id="topProductsLegend" class="mt-2"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase vs Sales Bar + Recent Sales + Low Stock -->
    <div class="row g-3 mb-4">
      <!-- Purchase vs Sales -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-bar-chart-fill text-primary-custom"></i> Purchase vs Sales</h2>
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
            <div class="p-4 text-center text-muted">
              <i class="bi bi-check-circle-fill text-success fs-22 d-block mb-2"></i>
              All products have sufficient stock!
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
                    <td><?= e($lp['category_name'] ?? 'N/A') ?></td>
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
      <!-- Recent Sales -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-receipt-cutoff text-primary-custom"></i> Recent Invoices</h2>
            <a href="<?= APP_URL ?>/admin/sales.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentSales as $sale): ?>
                  <tr>
                    <td><span class="badge badge-primary"><?= e($sale['invoice_number']) ?></span></td>
                    <td><?= e($sale['customer_name'] ?? 'Walk-in Customer') ?></td>
                    <td><?= formatDate($sale['sale_date']) ?></td>
                    <td class="fw-700"><?= formatCurrency($sale['grand_total']) ?></td>
                    <td>
                      <?php $pmColors = ['cash'=>'success','card'=>'primary','upi'=>'info','bank'=>'secondary','credit'=>'warning']; ?>
                      <span class="badge badge-<?= $pmColors[$sale['payment_method']] ?? 'secondary' ?>">
                        <?= ucfirst(e($sale['payment_method'])) ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?= APP_URL ?>/admin/invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" data-bs-toggle="tooltip" title="View Invoice">
                        <i class="bi bi-eye-fill"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Log -->
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <h2 class="card-title"><i class="bi bi-activity text-primary-custom"></i> Activity Log</h2>
          </div>
          <div class="card-body" style="max-height:320px;overflow-y:auto;">
            <?php foreach ($activityLogs as $log): ?>
            <div class="d-flex align-items-start gap-2 mb-3">
              <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);margin-top:6px;flex-shrink:0;"></div>
              <div>
                <div class="fw-600 fs-13"><?= e($log['action']) ?></div>
                <div class="fs-12 text-muted"><?= e($log['user_name'] ?? 'System') ?> · <?= formatDateTime($log['created_at']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($activityLogs)): ?>
            <div class="text-center text-muted py-3"><i class="bi bi-clock-history d-block fs-22 mb-1"></i>No activity yet</div>
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

  // ---- Sales Line Chart ----
  const salesCtx = document.getElementById('salesChart').getContext('2d');
  const salesGrad = salesCtx.createLinearGradient(0, 0, 0, 280);
  salesGrad.addColorStop(0, 'rgba(37,99,235,0.35)');
  salesGrad.addColorStop(1, 'rgba(37,99,235,0)');

  new Chart(salesCtx, {
    type: 'line',
    data: {
      labels: $salesLabels,
      datasets: [{
        label: 'Sales (₹)',
        data: $salesData,
        borderColor: '#2563eb',
        backgroundColor: salesGrad,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#2563eb',
        pointRadius: 5,
        pointHoverRadius: 7,
        borderWidth: 2.5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' },
          ticks: { callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(1)+'k' : v) }
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
          label: 'Sales', data: $salesData, backgroundColor: 'rgba(37,99,235,0.8)',
          borderRadius: 6, borderSkipped: false,
        },
        {
          label: 'Purchases', data: $purchaseData, backgroundColor: 'rgba(34,197,94,0.7)',
          borderRadius: 6, borderSkipped: false,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } }
      }
    }
  });

  // ---- Top Products Doughnut ----
  const topLabels = $topLabels;
  const topData   = $topData;
  const topColors = ['#2563eb','#22c55e','#f59e0b','#ef4444','#8b5cf6'];
  if (topLabels.length > 0) {
    const tpCtx = document.getElementById('topProductsChart').getContext('2d');
    new Chart(tpCtx, {
      type: 'doughnut',
      data: {
        labels: topLabels,
        datasets: [{
          data: topData,
          backgroundColor: topColors,
          borderWidth: 0,
          hoverOffset: 6,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' units' } }
        }
      }
    });
    // Custom legend
    const legend = document.getElementById('topProductsLegend');
    topLabels.forEach((label, i) => {
      legend.innerHTML += \`<div class="d-flex align-items-center gap-2 mb-1" style="font-size:12px;">
        <div style="width:10px;height:10px;border-radius:50%;background:\${topColors[i]};flex-shrink:0;"></div>
        <span class="text-truncate" title="\${label}">\${label}</span>
        <span class="ms-auto fw-600">\${topData[i]}</span>
      </div>\`;
    });
  } else {
    document.getElementById('topProductsChart').closest('.chart-container').innerHTML =
      '<div class="text-center text-muted py-5"><i class="bi bi-bar-chart-fill d-block fs-22 mb-2"></i>No sales data yet</div>';
  }
});
</script>
JS;

include '../includes/footer.php';
?>
