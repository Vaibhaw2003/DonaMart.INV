<?php
/**
 * AJAX: Get all products (for dropdowns in invoice/purchase builders)
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$products = db()->fetchAll("
    SELECT id, name, sku, selling_price, purchase_price, stock, unit
    FROM products
    WHERE status = 1 AND stock >= 0
    ORDER BY name ASC
");

echo json_encode(['success' => true, 'products' => $products]);
