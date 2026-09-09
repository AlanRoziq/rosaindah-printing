<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}


function app_csrf_token(): string {
    if (empty($_SESSION['admin_login_csrf'])) {
        $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_login_csrf'];
}

function app_verify_csrf(string $token): bool {
    return $token !== '' && !empty($_SESSION['admin_login_csrf'])
        && hash_equals($_SESSION['admin_login_csrf'], $token);
}

function admin_login_is_throttled(): bool {
    $state = $_SESSION['admin_login_throttle'] ?? [];
    $started = (int)($state['started_at'] ?? 0);
    $attempts = (int)($state['attempts'] ?? 0);
    if ($started === 0 || (time() - $started) >= 600) {
        unset($_SESSION['admin_login_throttle']);
        return false;
    }
    return $attempts >= 5;
}

function admin_login_record_failure(): void {
    $state = $_SESSION['admin_login_throttle'] ?? [];
    $started = (int)($state['started_at'] ?? 0);
    if ($started === 0 || (time() - $started) >= 600) {
        $state = ['started_at' => time(), 'attempts' => 0];
    }
    $state['attempts'] = (int)$state['attempts'] + 1;
    $_SESSION['admin_login_throttle'] = $state;
}

function admin_login_clear_throttle(): void {
    unset($_SESSION['admin_login_throttle']);
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: ' . page_url('admin/login.php'));
        exit;
    }
}

function admin_user(): array {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'name' => $_SESSION['admin_name'] ?? 'Admin',
        'username' => $_SESSION['admin_username'] ?? '',
    ];
}

function admin_flash(string $type, string $message): void {
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function get_admin_flash(): ?array {
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $flash;
}
