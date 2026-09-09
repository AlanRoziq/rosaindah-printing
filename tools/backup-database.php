<?php
/**
 * Backup database Rosa Indah Printing.
 * Jalankan via CLI, BUKAN melalui browser:
 *   php tools/backup-database.php
 *
 * Atur DB_* di environment hosting atau config/database.php terlebih dahulu.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Backup hanya boleh dijalankan melalui CLI.\n");
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$mysqldump = getenv('MYSQLDUMP_BIN') ?: 'mysqldump';
$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$outDir = __DIR__ . '/../backups';
if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Gagal membuat folder backup.\n"); exit(1);
}
$file = $outDir . '/rosaindahprinting-' . date('Y-m-d_H-i-s') . '.sql';
$cmd = escapeshellcmd($mysqldump)
    . ' --host=' . escapeshellarg($host)
    . ' --user=' . escapeshellarg($user)
    . ($pass !== '' ? ' --password=' . escapeshellarg($pass) : '')
    . ' --single-transaction --routines --triggers --events '
    . escapeshellarg($db) . ' > ' . escapeshellarg($file);
exec($cmd, $output, $code);
if ($code !== 0 || !is_file($file) || filesize($file) === 0) {
    @unlink($file);
    fwrite(STDERR, "Backup gagal. Pastikan mysqldump tersedia dan kredensial database benar.\n"); exit(1);
}
chmod($file, 0600);
echo "Backup berhasil: {$file}\n";
