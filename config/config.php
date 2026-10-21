<?php
// Konfigurasi utama Rosa Indah Printing

// Buffer output agar header HTTP & cookie session selalu aman dikirim
if (ob_get_level() === 0) {
    ob_start();
}

// Inisialisasi session aman jika belum aktif
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    @session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
        $detectedBase = '';
    } elseif (strpos($scriptDir, '/rosaindahprinting') !== false) {
        $detectedBase = '/rosaindahprinting';
    } else {
        $detectedBase = rtrim($scriptDir, '/');
    }
    define('BASE_URL', getenv('BASE_URL') !== false ? getenv('BASE_URL') : $detectedBase);
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'local'); // Sebelum online ubah menjadi 'production'.
}

// Security headers. Tidak mengubah desain/CSS/JS situs.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if (APP_ENV === 'production') {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
    }
}

if (!defined('WHATSAPP_NUMBER')) {
    define('WHATSAPP_NUMBER', '6282331035371');
}

// Konfigurasi OTP Member via WhatsApp.
// Produksi: isi token provider WhatsApp yang digunakan. Jangan simpan token di repository publik.
if (!defined('WHATSAPP_OTP_DRIVER')) define('WHATSAPP_OTP_DRIVER', 'fonnte');
if (!defined('WHATSAPP_OTP_TOKEN')) define('WHATSAPP_OTP_TOKEN', '');
if (!defined('WHATSAPP_OTP_ENDPOINT')) define('WHATSAPP_OTP_ENDPOINT', 'https://api.fonnte.com/send');
if (!defined('WHATSAPP_OTP_DEVICE')) define('WHATSAPP_OTP_DEVICE', '');
if (!defined('WHATSAPP_OTP_COOLDOWN')) define('WHATSAPP_OTP_COOLDOWN', 60);
if (!defined('WHATSAPP_OTP_EXPIRE')) define('WHATSAPP_OTP_EXPIRE', 600);

function asset_url($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function page_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}
