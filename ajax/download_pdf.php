<?php
/**
 * PDF Invoice Download (simple HTML-to-print version without DomPDF dependency)
 * If DomPDF is installed via Composer, this uses it.
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../admin/sales.php'); exit; }

$sale = db()->fetchOne("
    SELECT s.*, c.name AS customer_name, c.phone AS customer_phone,
           c.email AS customer_email, c.address AS customer_address
    FROM sales s LEFT JOIN customers c ON c.id=s.customer_id
    WHERE s.id=?
", [$id]);
if (!$sale) { header('Location: ../admin/sales.php'); exit; }

$saleItems = db()->fetchAll("
    SELECT si.*, p.name AS product_name, p.sku, p.unit
    FROM sale_items si JOIN products p ON p.id=si.product_id
    WHERE si.sale_id=?
", [$id]);

$settings = getSettings();

// If DomPDF vendor autoload exists, use it
$dompdfAvailable = file_exists('../vendor/autoload.php');

if ($dompdfAvailable) {
    require_once '../vendor/autoload.php';

    $html = buildInvoiceHTML($sale, $saleItems, $settings);
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $filename = 'Invoice-' . $sale['invoice_number'] . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
} else {
    // Fallback: output printable HTML page
    header('Content-Type: text/html; charset=utf-8');
    echo buildInvoiceHTML($sale, $saleItems, $settings, true);
    exit;
}

function buildInvoiceHTML(array $sale, array $items, array $settings, bool $printable = false): string {
    $companyName = htmlspecialchars($settings['company_name'] ?? 'Company');
    $gst         = htmlspecialchars($settings['gst_number'] ?? '');
    $address     = htmlspecialchars($settings['address'] ?? '');
    $phone       = htmlspecialchars($settings['phone'] ?? '');
    $email       = htmlspecialchars($settings['email'] ?? '');
    $invNo       = htmlspecialchars($sale['invoice_number']);
    $date        = date('d M Y', strtotime($sale['sale_date']));
    $custName    = htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer');
    $custPhone   = htmlspecialchars($sale['customer_phone'] ?? '');
    $custEmail   = htmlspecialchars($sale['customer_email'] ?? '');
    $custAddr    = htmlspecialchars($sale['customer_address'] ?? '');
    $payMethod   = ucfirst(htmlspecialchars($sale['payment_method']));
    $sym         = htmlspecialchars($settings['currency_symbol'] ?? '₹');

    $itemRows = '';
    foreach ($items as $i => $item) {
        $itemRows .= '<tr>
            <td style="padding:8px;border:1px solid #e2e8f0;">' . ($i+1) . '</td>
            <td style="padding:8px;border:1px solid #e2e8f0;">' . htmlspecialchars($item['product_name']) . '<br><small style="color:#64748b;">' . htmlspecialchars($item['sku']) . '</small></td>
            <td style="padding:8px;border:1px solid #e2e8f0;text-align:center;">' . $item['quantity'] . ' ' . htmlspecialchars($item['unit']) . '</td>
            <td style="padding:8px;border:1px solid #e2e8f0;text-align:right;">' . $sym . number_format($item['price'],2) . '</td>
            <td style="padding:8px;border:1px solid #e2e8f0;text-align:right;">' . ($item['discount']>0 ? '-'.$sym.number_format($item['discount'],2) : '—') . '</td>
            <td style="padding:8px;border:1px solid #e2e8f0;text-align:right;font-weight:700;">' . $sym . number_format($item['total'],2) . '</td>
        </tr>';
    }

    $printBtn = $printable ? '<div style="text-align:center;margin:20px 0;"><button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:14px;cursor:pointer;">🖨 Print Invoice</button></div>' : '';

    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Invoice '.$invNo.'</title>
<style>body{font-family:"DejaVu Sans",sans-serif;font-size:13px;color:#1e293b;margin:0;padding:20px;}
@media print{button{display:none!important;}}
table{width:100%;border-collapse:collapse;}
</style></head><body>
'.$printBtn.'
<div style="background:#0f172a;color:#fff;padding:24px;border-radius:8px 8px 0 0;">
  <table><tr>
    <td><div style="font-size:20px;font-weight:800;">'.$companyName.'</div>
    <div style="font-size:11px;opacity:.6;">GST: '.$gst.'</div>
    <div style="font-size:11px;opacity:.6;">'.$address.'</div>
    <div style="font-size:11px;opacity:.6;">'.$phone.' &bull; '.$email.'</div></td>
    <td style="text-align:right;">
    <div style="font-size:11px;opacity:.5;text-transform:uppercase;letter-spacing:1px;">Invoice</div>
    <div style="font-size:26px;font-weight:900;">'.$invNo.'</div>
    <div style="font-size:11px;opacity:.6;">Date: '.$date.'</div>
    </td>
  </tr></table>
</div>
<div style="padding:20px;border:1px solid #e2e8f0;border-top:none;">
  <table style="margin-bottom:20px;"><tr>
    <td style="width:50%;vertical-align:top;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:6px;">Bill To</div>
      <div style="font-weight:700;">'.$custName.'</div>
      <div style="color:#64748b;">'.$custPhone.'</div>
      <div style="color:#64748b;">'.$custEmail.'</div>
      <div style="color:#64748b;">'.$custAddr.'</div>
    </td>
    <td style="text-align:right;vertical-align:top;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:6px;">Payment</div>
      <div style="font-weight:600;">'.$payMethod.'</div>
      <div style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;display:inline-block;font-size:11px;font-weight:700;">PAID</div>
    </td>
  </tr></table>

  <table style="margin-bottom:20px;">
    <thead><tr style="background:#f8fafc;">
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:left;">#</th>
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:left;">Product</th>
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:center;">Qty</th>
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:right;">Unit Price</th>
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:right;">Discount</th>
      <th style="padding:8px;border:1px solid #e2e8f0;text-align:right;">Total</th>
    </tr></thead>
    <tbody>'.$itemRows.'</tbody>
  </table>

  <div style="text-align:right;margin-bottom:20px;">
    <table style="width:280px;margin-left:auto;">
      <tr><td style="padding:6px 0;color:#64748b;">Subtotal</td><td style="padding:6px 0;text-align:right;font-weight:600;">'.$sym.number_format($sale['subtotal'],2).'</td></tr>
      <tr><td style="padding:6px 0;color:#ef4444;">Discount</td><td style="padding:6px 0;text-align:right;font-weight:600;color:#ef4444;">- '.$sym.number_format($sale['discount'],2).'</td></tr>
      <tr><td style="padding:6px 0;color:#22c55e;">GST</td><td style="padding:6px 0;text-align:right;font-weight:600;color:#22c55e;">+ '.$sym.number_format($sale['gst'],2).'</td></tr>
      <tr style="background:#2563eb;color:#fff;">
        <td style="padding:10px;font-weight:700;border-radius:4px 0 0 4px;">Grand Total</td>
        <td style="padding:10px;text-align:right;font-weight:900;font-size:18px;border-radius:0 4px 4px 0;">'.$sym.number_format($sale['grand_total'],2).'</td>
      </tr>
    </table>
  </div>

  <div style="text-align:center;padding-top:16px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px;">
    <strong>Thank you for your business! 🙏</strong><br>
    This is a computer-generated invoice. No signature required.<br>
    For queries: '.$email.' &bull; '.$phone.'
  </div>
</div>
'.$printBtn.'
</body></html>';
}
