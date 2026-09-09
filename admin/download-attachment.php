<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if (!$pdo || $id < 1) { http_response_code(404); exit('File tidak ditemukan.'); }

$stmt = $pdo->prepare('SELECT original_name, file_path, mime_type, file_size FROM order_attachments WHERE id=? LIMIT 1');
try { $stmt->execute([$id]); $file = $stmt->fetch(); } catch (Throwable $e) { $file = false; }
if (!$file) { http_response_code(404); exit('File tidak ditemukan.'); }

$base = realpath(__DIR__ . '/../uploads/order-designs');
$path = realpath(__DIR__ . '/../' . ltrim($file['file_path'], '/\\'));
if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404); exit('File tidak tersedia.');
}

$downloadName = preg_replace('/[^A-Za-z0-9._ -]/u', '_', basename($file['original_name'])) ?: 'desain';
header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
