<?php
/**
 * AJAX: Get Purchase Detail (for modal)
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$purchase = db()->fetchOne("
    SELECT p.*, s.company_name AS supplier_name
    FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id
    WHERE p.id=?
", [$id]);
if (!$purchase) { echo '<div class="p-3 text-danger">Not found.</div>'; exit; }

$items = db()->fetchAll("
    SELECT pi.*, pr.name AS product_name, pr.sku, pr.unit
    FROM purchase_items pi JOIN products pr ON pr.id=pi.product_id
    WHERE pi.purchase_id=?
", [$id]);
?>
<div class="p-3">
  <div class="d-flex justify-content-between mb-3">
    <div>
      <strong>PO:</strong> <span class="badge badge-secondary"><?= e($purchase['purchase_number']) ?></span><br>
      <strong>Supplier:</strong> <?= e($purchase['supplier_name'] ?? 'N/A') ?><br>
      <strong>Date:</strong> <?= formatDate($purchase['purchase_date']) ?>
    </div>
    <div class="text-end">
      <div class="text-muted fs-12">Total Amount</div>
      <div class="fw-800 text-primary-custom" style="font-size:22px;"><?= formatCurrency($purchase['total_amount']) ?></div>
    </div>
  </div>
  <table class="table">
    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= e($item['product_name']) ?></td>
        <td><code style="background:var(--bg);padding:1px 5px;border-radius:3px;font-size:11px;"><?= e($item['sku']) ?></code></td>
        <td><?= $item['quantity'] ?> <?= e($item['unit']) ?></td>
        <td><?= formatCurrency($item['price']) ?></td>
        <td class="fw-700"><?= formatCurrency($item['quantity'] * $item['price']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($purchase['notes']): ?>
  <div class="text-muted fs-12"><strong>Notes:</strong> <?= e($purchase['notes']) ?></div>
  <?php endif; ?>
</div>
