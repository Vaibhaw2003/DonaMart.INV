<?php
/**
 * Utility / Helper Functions
 * Smart Inventory & Billing Management System
 */

require_once __DIR__ . '/database.php';

// -------------------------------------------------------
// App Settings helper
// -------------------------------------------------------
function getSettings(): array {
    static $settings = null;
    if ($settings === null) {
        $settings = db()->fetchOne('SELECT * FROM settings LIMIT 1') ?: [];
    }
    return $settings;
}

// -------------------------------------------------------
// Formatting
// -------------------------------------------------------
function formatCurrency(float $amount): string {
    $s = getSettings();
    return ($s['currency_symbol'] ?? '₹') . number_format($amount, 2);
}

function formatDate(string $date): string {
    return date('d M Y', strtotime($date));
}

function formatDateTime(string $dt): string {
    return date('d M Y, h:i A', strtotime($dt));
}

// -------------------------------------------------------
// SKU / Invoice / Purchase Number generation
// -------------------------------------------------------
function generateSKU(string $categoryPrefix = 'PRD'): string {
    return strtoupper($categoryPrefix) . '-' . strtoupper(substr(uniqid(), -6));
}

function generateInvoiceNumber(): string {
    $s = getSettings();
    $prefix = $s['invoice_prefix'] ?? 'INV';
    $year = date('Y');
    $count = db()->count('SELECT COUNT(*) as c FROM sales WHERE YEAR(sale_date) = ?', [$year]);
    return $prefix . '-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function generatePurchaseNumber(): string {
    $year = date('Y');
    $count = db()->count('SELECT COUNT(*) as c FROM purchases WHERE YEAR(purchase_date) = ?', [$year]);
    return 'PO-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// -------------------------------------------------------
// Image Upload
// -------------------------------------------------------
function uploadImage(array $file, string $destDir, int $maxSizeKB = 2048): array {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)];
    }
    if ($file['size'] > $maxSizeKB * 1024) {
        return ['success' => false, 'message' => "File too large. Max {$maxSizeKB}KB allowed."];
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filename = uniqid('img_', true) . '.' . $ext;
    $dest = rtrim($destDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }

    return ['success' => true, 'filename' => $filename];
}

// -------------------------------------------------------
// Activity Logger
// -------------------------------------------------------
function logActivity(string $action, string $module = 'general'): void {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        db()->insert(
            'INSERT INTO activity_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)',
            [$userId, $action, $module, $ip]
        );
    } catch (Exception $e) {
        // Silently fail — logging should not break the app
    }
}

// -------------------------------------------------------
// Stock Status
// -------------------------------------------------------
function stockStatus(int $stock, int $minStock): array {
    if ($stock <= 0) {
        return ['label' => 'Out of Stock', 'class' => 'danger', 'icon' => 'bi-x-circle-fill'];
    }
    if ($stock <= $minStock) {
        return ['label' => 'Low Stock', 'class' => 'warning', 'icon' => 'bi-exclamation-triangle-fill'];
    }
    return ['label' => 'In Stock', 'class' => 'success', 'icon' => 'bi-check-circle-fill'];
}

// -------------------------------------------------------
// Dashboard Stats
// -------------------------------------------------------
function getDashboardStats(): array {
    $today = date('Y-m-d');
    $month = date('Y-m');

    return [
        'total_products'   => db()->count('SELECT COUNT(*) c FROM products WHERE status = 1'),
        'total_categories' => db()->count('SELECT COUNT(*) c FROM categories'),
        'total_customers'  => db()->count('SELECT COUNT(*) c FROM customers'),
        'total_suppliers'  => db()->count('SELECT COUNT(*) c FROM suppliers'),
        'todays_sales'     => db()->fetchOne('SELECT COALESCE(SUM(grand_total),0) AS total FROM sales WHERE sale_date = ?', [$today])['total'] ?? 0,
        'monthly_sales'    => db()->fetchOne('SELECT COALESCE(SUM(grand_total),0) AS total FROM sales WHERE DATE_FORMAT(sale_date,"%Y-%m") = ?', [$month])['total'] ?? 0,
        'total_revenue'    => db()->fetchOne('SELECT COALESCE(SUM(grand_total),0) AS total FROM sales')['total'] ?? 0,
        'low_stock'        => db()->count('SELECT COUNT(*) c FROM products WHERE stock <= minimum_stock AND status = 1'),
    ];
}

// -------------------------------------------------------
// Sanitize output
// -------------------------------------------------------
function e(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// -------------------------------------------------------
// JSON response helper
// -------------------------------------------------------
function jsonResponse(bool $success, string $message, array $data = []): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// -------------------------------------------------------
// Redirect helper
// -------------------------------------------------------
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// -------------------------------------------------------
// CSRF Token
// -------------------------------------------------------
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(false, 'Invalid CSRF token.');
    }
}

// -------------------------------------------------------
// Pagination helper
// -------------------------------------------------------
function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): array {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'base_url'     => $baseUrl,
    ];
}
