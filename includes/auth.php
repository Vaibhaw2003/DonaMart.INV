<?php
/**
 * Authentication Functions
 * Smart Inventory & Billing Management System
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login — redirect to login if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    // Session expiry check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        logout();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Require a specific role
 */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        die('<h2>Access Denied</h2><p>You do not have permission to view this page.</p>');
    }
}

/**
 * Check if the current user has the specified role(s)
 */
function hasRole(string ...$roles): bool {
    return in_array($_SESSION['user_role'] ?? '', $roles, true);
}

/**
 * Perform login
 */
function login(string $email, string $password): array {
    $user = db()->fetchOne(
        'SELECT * FROM users WHERE email = ? AND status = 1',
        [$email]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'];
    $_SESSION['last_activity'] = time();

    // Log activity
    logActivity('Logged in', 'auth');

    return ['success' => true, 'message' => 'Login successful.'];
}

/**
 * Perform logout
 */
function logout(): void {
    logActivity('Logged out', 'auth');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

/**
 * Get current logged-in user info
 */
function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id']    ?? 0,
        'name'   => $_SESSION['user_name']  ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role']  ?? '',
        'avatar' => $_SESSION['user_avatar'] ?? null,
    ];
}
