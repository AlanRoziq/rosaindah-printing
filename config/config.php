<?php
// Konfigurasi utama Rosa Indah Printing
if (!defined('BASE_URL')) {
    define('BASE_URL', '/rosaindahprinting'); // Hosting domain root: biasanya ''.
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
