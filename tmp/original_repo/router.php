<?php
// router.php - Router for PHP built-in web server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Strip /rosaindahprinting prefix if present
if (strpos($uri, '/rosaindahprinting') === 0) {
    $uri = substr($uri, strlen('/rosaindahprinting'));
    if ($uri === '' || $uri === '/') {
        require __DIR__ . '/index.php';
        return true;
    }
}

$path = __DIR__ . $uri;

// If static file exists, serve directly
if ($uri !== '/' && file_exists($path) && !is_dir($path)) {
    return false;
}

// If file.php exists, load it
if (file_exists($path . '.php')) {
    require $path . '.php';
    return true;
}

// If directory with index.php exists
if (is_dir($path) && file_exists(rtrim($path, '/') . '/index.php')) {
    require rtrim($path, '/') . '/index.php';
    return true;
}

// Default to index.php
require __DIR__ . '/index.php';
