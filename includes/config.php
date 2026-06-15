<?php
/**
 * Configuration File
 * Smart Inventory & Billing Management System
 */

// -------------------------------------------------------
// Database Configuration
// -------------------------------------------------------
define('DB_HOST',     'localhost');
define('DB_NAME',     'smart_inventory');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// -------------------------------------------------------
// Application Configuration
// -------------------------------------------------------
define('APP_NAME',    'SmartINV');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/Billing%20Managment');
define('BASE_PATH',   dirname(__DIR__));

// -------------------------------------------------------
// Upload Paths
// -------------------------------------------------------
define('UPLOAD_PATH',         BASE_PATH . '/uploads/');
define('PRODUCT_UPLOAD_PATH', BASE_PATH . '/uploads/products/');
define('USER_UPLOAD_PATH',    BASE_PATH . '/uploads/users/');

// -------------------------------------------------------
// Upload URL Paths
// -------------------------------------------------------
define('UPLOAD_URL',         APP_URL . '/uploads/');
define('PRODUCT_UPLOAD_URL', APP_URL . '/uploads/products/');
define('USER_UPLOAD_URL',    APP_URL . '/uploads/users/');

// -------------------------------------------------------
// Session Configuration
// -------------------------------------------------------
define('SESSION_LIFETIME', 3600); // 1 hour

// -------------------------------------------------------
// Timezone
// -------------------------------------------------------
date_default_timezone_set('Asia/Kolkata');

// -------------------------------------------------------
// Error Reporting (set to 0 in production)
// -------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------------------------------------------------------
// Start Session
// -------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
