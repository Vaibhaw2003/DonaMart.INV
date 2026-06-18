<?php
/**
 * HTML Header / Head section
 * Smart Inventory & Billing Management System
 */
require_once __DIR__ . '/functions.php';
$settings = getSettings();
$pageTitle = $pageTitle ?? APP_NAME;
$theme = $_COOKIE['smartinv_theme'] ?? ($settings['theme'] ?? 'light');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>" data-bs-theme="<?= e($theme) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Smart Inventory & Billing Management System — Manage products, sales, purchases and invoices efficiently." />
  <title><?= e($pageTitle) ?> | <?= e($settings['company_name'] ?? APP_NAME) ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" />

  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css" />
</head>
<body>
