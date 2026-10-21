<?php
// router.php for PHP built-in web server

// Aktifkan output buffering agar header dan session cookie selalu valid
if (ob_get_level() === 0) {
    ob_start();
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__ . $uri;

// Static file handling (css, js, images, fonts, icons, etc.)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'php') {
        return false;
    }
    require $file;
    return true;
}

// Directory handling (e.g. /admin/)
if (is_dir($file)) {
    $indexFile = rtrim($file, '/') . '/index.php';
    if (file_exists($indexFile)) {
        require $indexFile;
        return true;
    }
}

// Clean URL handling (e.g. /produk -> /produk.php)
if (file_exists($file . '.php')) {
    require $file . '.php';
    return true;
}

// Default fallback to index.php
if (file_exists(__DIR__ . '/index.php')) {
    require __DIR__ . '/index.php';
    return true;
}

return false;
