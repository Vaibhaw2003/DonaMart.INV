<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/login.php');
}
exit;
