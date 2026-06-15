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

$error = '';
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
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Login to SmartINV — Smart Inventory & Billing Management System" />
  <title>Login | SmartINV</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
</head>
<body>

<div class="auth-page">
  <div class="auth-bg-shapes"></div>

  <!-- Floating particles -->
  <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
    <?php for($i=0;$i<12;$i++): ?>
    <div style="
      position:absolute;
      width:<?= rand(4,10) ?>px; height:<?= rand(4,10) ?>px;
      background:rgba(37,99,235,<?= rand(1,4)/10 ?>);
      border-radius:50%;
      left:<?= rand(5,95) ?>%;
      top:<?= rand(5,95) ?>%;
      animation: fadeInUp <?= rand(2,5) ?>s ease infinite alternate;
      animation-delay: <?= rand(0,3) ?>s;
    "></div>
    <?php endfor; ?>
  </div>

  <div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
      <i class="bi bi-box-seam-fill"></i>
    </div>

    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to your SmartINV account</p>

    <?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5;border-radius:10px;">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm" novalidate>
      <div class="mb-3">
        <label class="form-label" for="email">Email Address</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.4);">
            <i class="bi bi-envelope-fill"></i>
          </span>
          <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            placeholder="admin@smartinv.com"
            value="<?= e($_POST['email'] ?? '') ?>"
            style="padding-left:38px;"
            required
            autocomplete="email"
          />
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <div class="position-relative">
          <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.4);">
            <i class="bi bi-lock-fill"></i>
          </span>
          <input
            type="password"
            class="form-control"
            id="password"
            name="password"
            placeholder="Enter your password"
            style="padding-left:38px;padding-right:44px;"
            required
            autocomplete="current-password"
          />
          <button type="button" id="togglePwd" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;padding:0;">
            <i class="bi bi-eye-fill" id="togglePwdIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-auth" id="loginBtn">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <!-- Demo credentials -->
    <div class="demo-creds">
      <div class="mb-1"><strong>🔑 Demo Credentials:</strong></div>
      <div>Admin: <strong>admin@smartinv.com</strong> / <strong>Admin@123</strong></div>
      <div>Manager: <strong>manager@smartinv.com</strong> / <strong>Admin@123</strong></div>
      <div>Staff: <strong>staff@smartinv.com</strong> / <strong>Admin@123</strong></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  document.getElementById('togglePwd').addEventListener('click', function() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('togglePwdIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.className = 'bi bi-eye-slash-fill';
    } else {
      pwd.type = 'password';
      icon.className = 'bi bi-eye-fill';
    }
  });

  // Loading state on submit
  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
    btn.disabled = true;
  });
</script>
</body>
</html>
