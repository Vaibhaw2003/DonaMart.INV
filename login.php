<?php
/**
 * Login Page
 * Smart Inventory & Billing Management System
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            header('Location: ' . APP_URL . '/admin/dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Read saved theme from cookie (so login page respects user preference)
$theme = $_COOKIE['smartinv_theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>" data-bs-theme="<?= e($theme) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Login to SmartINV — Smart Inventory & Billing Management System" />
  <title>Login | SmartINV — Smart Inventory & Billing</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
  <style>
    /* Extra login-only page styles */
    .auth-grid {
      display: grid;
      grid-template-columns: 1fr;
      min-height: 100vh;
    }
    @media (min-width: 1024px) {
      .auth-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    .auth-left-panel {
      background: linear-gradient(135deg, #0E1712 0%, #142119 55%, #0E1712 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px;
      position: relative;
      overflow: hidden;
    }

    .auth-right-panel {
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px;
      position: relative;
    }

    /* Feature list on left panel */
    .auth-feature {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.6);
      font-size: 13.5px;
    }
    .auth-feature:last-child { border-bottom: none; }
    .auth-feature-icon {
      width: 36px; height: 36px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 17px;
      flex-shrink: 0;
    }

    /* Stats counters on left */
    .auth-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 32px;
    }
    .auth-stat-box {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 12px;
      padding: 14px 16px;
      text-align: center;
    }
    .auth-stat-box .num {
      font-family: 'IBM Plex Mono', monospace;
      font-size: 24px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -0.5px;
      display: block;
    }
    .auth-stat-box .lbl {
      font-size: 11px;
      color: rgba(255,255,255,0.4);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.6px;
    }

    /* Right panel card */
    .auth-form-wrapper {
      width: 100%;
      max-width: 420px;
    }

    /* Light mode right panel card */
    [data-theme="light"] .auth-right-panel {
      background: var(--bg);
    }
    [data-theme="light"] .auth-form-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 42px 38px;
      box-shadow: 0 8px 40px rgba(20,25,18,0.1);
    }
    [data-theme="light"] .auth-form-card .input-icon-wrapper .form-control {
      background: var(--surface-2) !important;
      border: 1.5px solid var(--border) !important;
      color: var(--text) !important;
    }
    [data-theme="light"] .auth-form-card .input-icon-wrapper .form-control::placeholder {
      color: var(--text-light) !important;
    }
    [data-theme="light"] .auth-form-card .input-icon-wrapper .input-icon {
      color: var(--text-light);
    }
    [data-theme="light"] .auth-form-card .input-action-btn {
      color: var(--text-light);
    }
    [data-theme="light"] .auth-form-card .input-action-btn:hover {
      color: var(--text);
    }
    [data-theme="light"] .auth-form-card .form-label-auth {
      color: var(--text-secondary);
    }
    [data-theme="light"] .auth-form-card .auth-title {
      color: var(--text);
    }
    [data-theme="light"] .auth-form-card .auth-subtitle {
      color: var(--text-muted);
    }
    [data-theme="light"] .auth-form-card .quick-logins {
      border-top-color: var(--border);
    }
    [data-theme="light"] .auth-form-card .quick-login-label {
      color: var(--text-light);
    }
    [data-theme="light"] .auth-form-card .btn-quick-login {
      background: var(--surface-2);
      border-color: var(--border);
      color: var(--text-secondary);
    }
    [data-theme="light"] .auth-form-card .btn-quick-login:hover {
      background: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary-dark);
    }

    /* Dark mode card */
    [data-theme="dark"] .auth-form-card {
      background: rgba(20, 33, 25, 0.55);
      backdrop-filter: blur(32px);
      -webkit-backdrop-filter: blur(32px);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 24px;
      padding: 42px 38px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.45);
    }
  </style>
</head>
<body>

<div class="auth-grid">

  <!-- LEFT PANEL (shown on desktop) -->
  <div class="auth-left-panel d-none d-lg-flex">
    <!-- Glow orbs -->
    <div class="glow-orb glow-orb-1"></div>
    <div class="glow-orb glow-orb-2"></div>
    <div class="glow-orb glow-orb-3"></div>

    <!-- Floating particles -->
    <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:0;">
      <?php for($i=0;$i<18;$i++): ?>
      <div style="
        position:absolute;
        width:<?= rand(2,7) ?>px; height:<?= rand(2,7) ?>px;
        background:rgba(201,122,26,<?= rand(1,4)/10 ?>);
        border-radius:50%;
        left:<?= rand(3,97) ?>%; top:<?= rand(3,97) ?>%;
        animation: fadeInUp <?= rand(4,8) ?>s ease infinite alternate;
        animation-delay: <?= rand(0,5) ?>s;
      "></div>
      <?php endfor; ?>
    </div>

    <div style="position:relative;z-index:1;width:100%;max-width:400px;">
      <!-- Brand -->
      <div class="d-flex align-items-center gap-3 mb-8" style="margin-bottom:40px;">
        <div style="width:52px;height:52px;background:#C97A1A;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;box-shadow:0 8px 24px rgba(201,122,26,0.4);">
          <i class="bi bi-box-seam-fill" style="color:#15231D;"></i>
        </div>
        <div>
          <div style="font-family:'Fraunces',serif;font-size:22px;font-weight:800;color:#fff;letter-spacing:-0.5px;">Smart<span style="color:#C97A1A;">INV</span></div>
          <div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:rgba(255,255,255,0.35);font-weight:500;letter-spacing:0.5px;text-transform:uppercase;">Business Suite</div>
        </div>
      </div>

      <!-- Tagline -->
      <h1 style="font-family:'Fraunces',serif;font-size:32px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1.15;margin-bottom:12px;">
        Your Complete<br><span style="color:#DD8F2E;">Business Dashboard</span>
      </h1>
      <p style="font-size:14px;color:rgba(255,255,255,0.45);margin-bottom:36px;line-height:1.6;">
        Manage inventory, sales, purchases and generate professional invoices — all from one place.
      </p>

      <!-- Features -->
      <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:8px 20px;margin-bottom:28px;">
        <div class="auth-feature">
          <div class="auth-feature-icon" style="background:rgba(43,92,115,0.22);color:#6FA8C2;"><i class="bi bi-boxes"></i></div>
          <span>Smart Inventory Management</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon" style="background:rgba(60,110,71,0.22);color:#6FAE7C;"><i class="bi bi-receipt-cutoff"></i></div>
          <span>Professional GST Invoicing</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon" style="background:rgba(91,75,138,0.22);color:#9C8ED1;"><i class="bi bi-bar-chart-line-fill"></i></div>
          <span>Real-time Reports & Analytics</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon" style="background:rgba(201,122,26,0.22);color:#E0A050;"><i class="bi bi-file-pdf-fill"></i></div>
          <span>PDF & Email Invoice Delivery</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="auth-stats">
        <div class="auth-stat-box">
          <span class="num">₹∞</span>
          <span class="lbl">Revenue Tracked</span>
        </div>
        <div class="auth-stat-box">
          <span class="num">3</span>
          <span class="lbl">User Roles</span>
        </div>
        <div class="auth-stat-box">
          <span class="num">100%</span>
          <span class="lbl">GST Ready</span>
        </div>
        <div class="auth-stat-box">
          <span class="num">24/7</span>
          <span class="lbl">Available</span>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL: Login Form -->
  <div class="auth-right-panel">
    <!-- Dark mode toggle -->
    <button id="themeSwitcher" onclick="toggleTheme()" style="
      position:absolute;top:20px;right:20px;
      width:38px;height:38px;border-radius:10px;
      background:var(--surface);border:1.5px solid var(--border);
      display:flex;align-items:center;justify-content:center;
      cursor:pointer;color:var(--text-muted);font-size:16px;
      transition:all .2s ease;
    ">
      <i class="bi bi-<?= $theme === 'dark' ? 'sun-fill' : 'moon-stars-fill' ?>" id="themeIcon"></i>
    </button>

    <div class="auth-form-wrapper">
      <div class="auth-form-card">

        <!-- Logo (mobile) -->
        <div class="d-lg-none mb-4 text-center">
          <div style="width:54px;height:54px;background:#C97A1A;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 10px;">
            <i class="bi bi-box-seam-fill" style="color:#15231D;"></i>
          </div>
          <div style="font-family:'Fraunces',serif;font-size:20px;font-weight:800;letter-spacing:-0.5px;">Smart<span style="color:var(--primary);">INV</span></div>
        </div>

        <h1 class="auth-title" style="font-size:24px;font-weight:800;margin-bottom:5px;">Welcome Back 👋</h1>
        <p class="auth-subtitle" style="margin-bottom:28px;">Sign in to your SmartINV account</p>

        <?php if ($error): ?>
        <div style="background:rgba(166,64,47,0.12);border:1px solid rgba(166,64,47,0.3);border-radius:10px;padding:10px 14px;margin-bottom:18px;display:flex;align-items:center;gap:8px;font-size:13px;color:#E2A89A;">
          <i class="bi bi-exclamation-triangle-fill" style="color:#C2604A;flex-shrink:0;"></i>
          <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" novalidate>
          <div class="mb-3">
            <label class="form-label-auth" for="loginEmail">Email Address</label>
            <div class="input-icon-wrapper">
              <input
                type="email"
                class="form-control"
                id="loginEmail"
                name="email"
                placeholder="admin@smartinv.com"
                value="<?= e($_POST['email'] ?? '') ?>"
                required
                autocomplete="email"
              />
              <span class="input-icon"><i class="bi bi-envelope-fill"></i></span>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label-auth mb-0" for="loginPassword">Password</label>
            </div>
            <div class="input-icon-wrapper">
              <input
                type="password"
                class="form-control"
                id="loginPassword"
                name="password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
              />
              <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
              <button type="button" id="togglePwd" class="input-action-btn" tabindex="-1">
                <i class="bi bi-eye-fill" id="togglePwdIcon"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-auth" id="loginBtn">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
          </button>
        </form>

        <!-- Quick login links -->
        <div class="quick-logins">
          <div class="quick-login-label">⚡ Quick Demo Access</div>
          <div class="d-flex justify-content-between gap-2 mt-3">
            <button type="button" class="btn-quick-login" data-email="admin@smartinv.com" data-role="Admin" id="quickAdmin">
              <span class="role-badge role-admin"><i class="bi bi-shield-fill" style="font-size:8px;"></i></span>
              <span>Admin</span>
            </button>
            <button type="button" class="btn-quick-login" data-email="manager@smartinv.com" data-role="Manager" id="quickManager">
              <span class="role-badge role-manager"><i class="bi bi-person-fill" style="font-size:8px;"></i></span>
              <span>Manager</span>
            </button>
            <button type="button" class="btn-quick-login" data-email="staff@smartinv.com" data-role="Staff" id="quickStaff">
              <span class="role-badge role-staff"><i class="bi bi-headset" style="font-size:8px;"></i></span>
              <span>Staff</span>
            </button>
          </div>
          <div style="font-size:11px;color:rgba(255,255,255,0.22);text-align:center;margin-top:10px;" id="pwdHint"></div>
        </div>

      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── Theme toggle ──────────────────────────────────────────────
  function toggleTheme() {
    const html  = document.documentElement;
    const curr  = html.getAttribute('data-theme') || 'dark';
    const next  = curr === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    html.setAttribute('data-bs-theme', next);
    document.getElementById('themeIcon').className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    localStorage.setItem('smartinv_theme', next);
    document.cookie = 'smartinv_theme=' + next + ';path=/;max-age=31536000;SameSite=Strict';
  }

  // ── Toggle password visibility ────────────────────────────────
  document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd  = document.getElementById('loginPassword');
    const icon = document.getElementById('togglePwdIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.className = 'bi bi-eye-slash-fill';
    } else {
      pwd.type = 'password';
      icon.className = 'bi bi-eye-fill';
    }
  });

  // ── Quick fill credentials ────────────────────────────────────
  document.querySelectorAll('.btn-quick-login').forEach(btn => {
    btn.addEventListener('click', function() {
      const email = this.dataset.email;
      const emailInput = document.getElementById('loginEmail');
      const pwdInput   = document.getElementById('loginPassword');
      emailInput.value = email;
      pwdInput.value   = 'Admin@123';

      document.getElementById('pwdHint').textContent = '✓ Credentials filled — click Sign In';

      [emailInput, pwdInput].forEach(input => {
        input.classList.remove('pulse-glow');
        void input.offsetWidth;
        input.classList.add('pulse-glow');
      });
    });
  });

  // ── Loading state on submit ───────────────────────────────────
  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:16px;height:16px;border-width:2px;"></span>Signing in…';
    btn.disabled = true;
    btn.style.opacity = '0.8';
  });

  // ── Sync theme from localStorage on load ─────────────────────
  (function() {
    const saved = localStorage.getItem('smartinv_theme');
    if (saved && saved !== document.documentElement.getAttribute('data-theme')) {
      document.documentElement.setAttribute('data-theme', saved);
      document.documentElement.setAttribute('data-bs-theme', saved);
      const icon = document.getElementById('themeIcon');
      if (icon) icon.className = saved === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
  })();
</script>
</body>
</html>