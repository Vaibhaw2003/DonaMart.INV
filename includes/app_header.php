<?php
/**
 * Reusable App Top Header bar
 * Include after include sidebar.php in every admin page.
 * Usage: include '../includes/app_header.php';
 *
 * Optional: Set $pageTitle (already used), $breadcrumbs array before including.
 */
$_hUser = currentUser();
?>
<div class="main-content">
  <header class="app-header">
    <button class="header-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <div class="header-search">
      <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="form-control" id="globalSearch" placeholder="Quick search…" autocomplete="off" />
      </div>
    </div>
    <div class="header-actions">
      <button class="header-btn" id="darkModeToggle" data-bs-toggle="tooltip" title="Toggle Dark/Light Mode">
        <i class="bi bi-moon-stars-fill" id="darkModeIcon"></i>
      </button>
      <button class="header-btn position-relative" data-bs-toggle="tooltip" title="Notifications">
        <i class="bi bi-bell-fill"></i>
      </button>
      <div class="header-divider"></div>
      <div class="dropdown">
        <div class="header-user dropdown-toggle" data-bs-toggle="dropdown" id="headerUserDropdown" aria-expanded="false">
          <?php if ($_hUser['avatar']): ?>
            <img src="<?= e(USER_UPLOAD_URL . $_hUser['avatar']) ?>" class="header-avatar" alt="Avatar" />
          <?php else: ?>
            <div class="header-avatar"><?= strtoupper(substr($_hUser['name'], 0, 1)) ?></div>
          <?php endif; ?>
          <span class="header-user-name d-none d-md-inline"><?= e($_hUser['name']) ?></span>
          <i class="bi bi-chevron-down" style="font-size:10px;color:var(--text-muted);"></i>
        </div>
        <ul class="dropdown-menu dropdown-menu-end mt-1" aria-labelledby="headerUserDropdown">
          <li>
            <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
              <div style="font-weight:700;font-size:13px;color:var(--text);"><?= e($_hUser['name']) ?></div>
              <div style="font-size:11.5px;color:var(--text-muted);"><?= e(ucfirst($_hUser['role'])) ?></div>
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
