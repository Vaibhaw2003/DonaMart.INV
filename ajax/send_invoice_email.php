<?php
/**
 * AJAX: Send Invoice Email
 * Smart Inventory & Billing Management System
 */
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$saleId = (int)($_POST['sale_id'] ?? 0);
$email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$saleId || !$email) {
    jsonResponse(false, 'Invalid sale ID or email address.');
}

$sale = db()->fetchOne("
    SELECT s.*, c.name AS customer_name
    FROM sales s LEFT JOIN customers c ON c.id=s.customer_id
    WHERE s.id=?
", [$saleId]);

if (!$sale) jsonResponse(false, 'Invoice not found.');

$settings = getSettings();

// Build plain email content (PHPMailer integration point)
$subject  = "Invoice {$sale['invoice_number']} from {$settings['company_name']}";
$body     = "Dear {$sale['customer_name']},\n\nPlease find your invoice details below:\n\n"
           . "Invoice No: {$sale['invoice_number']}\n"
           . "Date: {$sale['sale_date']}\n"
           . "Grand Total: " . formatCurrency($sale['grand_total']) . "\n\n"
           . "Thank you for your business!\n\n"
           . "{$settings['company_name']}\n{$settings['phone']}\n{$settings['email']}";

// Try PHPMailer if available, otherwise use mail()
$sent = false;

if (file_exists('../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require '../vendor/phpmailer/phpmailer/src/SMTP.php';
    require '../vendor/phpmailer/phpmailer/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['email'] ?? '';
        $mail->Password   = 'YOUR_SMTP_PASSWORD'; // Set in settings
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom($settings['email'] ?? 'noreply@smartinv.com', $settings['company_name']);
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        $sent = true;
    } catch (Exception $e) {
        // Fall through to mail()
    }
}

if (!$sent) {
    $headers = "From: {$settings['company_name']} <{$settings['email']}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $sent    = mail($email, $subject, $body, $headers);
}

logActivity("Sent invoice {$sale['invoice_number']} to $email", 'sales');
jsonResponse($sent, $sent ? "Invoice sent to $email successfully!" : "Mail function unavailable. Configure SMTP in settings.");
