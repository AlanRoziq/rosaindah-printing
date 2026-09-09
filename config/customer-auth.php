<?php
/**
 * Stage 21 — Sistem Member Rosa Indah Printing
 *
 * - Semua pengunjung tetap boleh checkout tanpa login.
 * - Akun yang terdaftar + terverifikasi = Member.
 * - Nomor WhatsApp adalah identitas unik login.
 * - OTP 4 digit disimpan dalam bentuk hash dan kedaluwarsa.
 */
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

function customer_auth_init(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        phone VARCHAR(30) NOT NULL UNIQUE,
        email VARCHAR(160) NULL,
        password VARCHAR(255) NOT NULL,
        address TEXT NULL,
        is_verified TINYINT(1) NOT NULL DEFAULT 1,
        verification_code_hash VARCHAR(255) NULL,
        verification_expires_at DATETIME NULL,
        verification_sent_at DATETIME NULL,
        verification_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_customers_phone (phone),
        INDEX idx_customers_verified (is_verified)
    ) ENGINE=InnoDB");

    $cols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    $migrations = [
        'is_verified' => "ALTER TABLE customers ADD is_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER address",
        'verification_code_hash' => "ALTER TABLE customers ADD verification_code_hash VARCHAR(255) NULL AFTER is_verified",
        'verification_expires_at' => "ALTER TABLE customers ADD verification_expires_at DATETIME NULL AFTER verification_code_hash",
        'verification_sent_at' => "ALTER TABLE customers ADD verification_sent_at DATETIME NULL AFTER verification_expires_at",
        'verification_attempts' => "ALTER TABLE customers ADD verification_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER verification_sent_at",
    ];
    foreach ($migrations as $col => $sql) {
        if (!in_array($col, $cols, true)) {
            try { $pdo->exec($sql); } catch (Throwable $e) {}
        }
    }

    // Email bersifat opsional dan TIDAK menjadi identitas unik Member.
    try {
        $indexes = $pdo->query("SHOW INDEX FROM customers")->fetchAll();
        foreach ($indexes as $idx) {
            if (($idx['Column_name'] ?? '') === 'email' && (int)($idx['Non_unique'] ?? 1) === 0 && ($idx['Key_name'] ?? '') !== 'PRIMARY') {
                $key = str_replace('`', '', (string)$idx['Key_name']);
                if ($key !== '') $pdo->exec("ALTER TABLE customers DROP INDEX `{$key}`");
            }
        }
    } catch (Throwable $e) {}

    $orderCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('customer_id', $orderCols, true)) {
        try { $pdo->exec("ALTER TABLE orders ADD customer_id BIGINT UNSIGNED NULL AFTER id"); } catch (Throwable $e) {}
        try { $pdo->exec("ALTER TABLE orders ADD INDEX idx_orders_customer (customer_id)"); } catch (Throwable $e) {}
        try { $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL"); } catch (Throwable $e) {}
    }
}

function customer_logged_in(): bool { return !empty($_SESSION['customer_id']); }
function customer_id(): ?int { return customer_logged_in() ? (int)$_SESSION['customer_id'] : null; }
function customer_user(): array {
    return [
        'id' => $_SESSION['customer_id'] ?? null,
        'name' => $_SESSION['customer_name'] ?? 'Member',
        'phone' => $_SESSION['customer_phone'] ?? '',
        'email' => $_SESSION['customer_email'] ?? '',
        'address' => $_SESSION['customer_address'] ?? '',
        'is_verified' => (int)($_SESSION['customer_verified'] ?? 0),
    ];
}
function customer_login(array $customer): void {
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customer['id'];
    $_SESSION['customer_name'] = (string)$customer['name'];
    $_SESSION['customer_phone'] = (string)$customer['phone'];
    $_SESSION['customer_email'] = (string)($customer['email'] ?? '');
    $_SESSION['customer_address'] = (string)($customer['address'] ?? '');
    $_SESSION['customer_verified'] = (int)($customer['is_verified'] ?? 1);
}
function customer_logout(): void {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_phone'], $_SESSION['customer_email'], $_SESSION['customer_address'], $_SESSION['customer_verified']);
}
function customer_csrf(): string {
    if (empty($_SESSION['customer_csrf'])) $_SESSION['customer_csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['customer_csrf'];
}
function customer_verify_csrf(string $token): bool {
    return $token !== '' && !empty($_SESSION['customer_csrf']) && hash_equals($_SESSION['customer_csrf'], $token);
}
function customer_normalize_phone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
    if (str_starts_with($digits, '8')) return '62' . $digits;
    return $digits;
}
function customer_e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function customer_flash_set(string $type, string $message): void { $_SESSION['customer_flash'] = [$type, $message]; }
function customer_flash_get(): ?array { $v = $_SESSION['customer_flash'] ?? null; unset($_SESSION['customer_flash']); return $v; }

function customer_pending_set(int $id, string $phone): void {
    $_SESSION['member_pending_id'] = $id;
    $_SESSION['member_pending_phone'] = $phone;
}
function customer_pending_id(): ?int {
    return !empty($_SESSION['member_pending_id']) ? (int)$_SESSION['member_pending_id'] : null;
}
function customer_pending_phone(): string { return (string)($_SESSION['member_pending_phone'] ?? ''); }
function customer_pending_clear(): void { unset($_SESSION['member_pending_id'], $_SESSION['member_pending_phone']); }

function customer_otp_remaining(PDO $pdo, int $customerId): int {
    $stmt = $pdo->prepare('SELECT verification_sent_at FROM customers WHERE id=? LIMIT 1');
    $stmt->execute([$customerId]);
    $sent = $stmt->fetchColumn();
    if (!$sent) return 0;
    $remain = WHATSAPP_OTP_COOLDOWN - (time() - strtotime((string)$sent));
    return max(0, $remain);
}

function customer_send_whatsapp_otp(PDO $pdo, array $customer, bool $force = false): array {
    $id = (int)$customer['id'];
    $remaining = customer_otp_remaining($pdo, $id);
    if (!$force && $remaining > 0) return [false, 'Tunggu ' . $remaining . ' detik sebelum meminta kode baru.'];

    $phone = customer_normalize_phone((string)$customer['phone']);
    if ($phone === '') return [false, 'Nomor WhatsApp tidak valid.'];
    $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $message = "Halo " . (string)$customer['name'] . ",\n\nKode verifikasi Akun Member Rosa Indah Printing Anda adalah: *{$code}*\n\nKode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.";

    if (WHATSAPP_OTP_DRIVER !== 'fonnte' || trim((string)WHATSAPP_OTP_TOKEN) === '') {
        return [false, 'Pengiriman kode WhatsApp belum dikonfigurasi. Isi WHATSAPP_OTP_TOKEN di config/config.php terlebih dahulu.'];
    }

    if (!function_exists('curl_init')) return [false, 'Ekstensi cURL PHP belum aktif di server.'];
    $ch = curl_init(WHATSAPP_OTP_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: ' . WHATSAPP_OTP_TOKEN],
        CURLOPT_POSTFIELDS => ['target' => $phone, 'message' => $message],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $curlErr !== '') return [false, 'Kode WhatsApp gagal dikirim. Coba lagi beberapa saat.'];
    $data = json_decode((string)$raw, true);
    $ok = ($http >= 200 && $http < 300 && is_array($data) && (($data['status'] ?? false) === true || ($data['status'] ?? '') === 'success'));
    if (!$ok) return [false, 'Kode WhatsApp belum berhasil dikirim. Periksa token dan koneksi WhatsApp provider.'];

    $hash = password_hash($code, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', time() + WHATSAPP_OTP_EXPIRE);
    $stmt = $pdo->prepare('UPDATE customers SET verification_code_hash=?, verification_expires_at=?, verification_sent_at=NOW(), verification_attempts=0 WHERE id=?');
    $stmt->execute([$hash, $expires, $id]);
    return [true, 'Kode verifikasi 4 digit telah dikirim ke WhatsApp Anda.'];
}

function customer_verify_otp(PDO $pdo, int $customerId, string $code): array {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id=? LIMIT 1');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) return [false, null, 'Data Member tidak ditemukan.'];
    if ((int)$customer['is_verified'] === 1) return [true, $customer, 'Akun Member sudah terverifikasi.'];
    if (!preg_match('/^\d{4}$/', $code)) return [false, null, 'Kode verifikasi harus terdiri dari 4 angka.'];
    if ((int)($customer['verification_attempts'] ?? 0) >= 5) return [false, null, 'Terlalu banyak percobaan. Silakan kirim ulang kode verifikasi.'];
    if (empty($customer['verification_code_hash']) || empty($customer['verification_expires_at']) || strtotime($customer['verification_expires_at']) < time()) {
        return [false, null, 'Kode verifikasi sudah kedaluwarsa. Silakan kirim ulang kode.'];
    }
    if (!password_verify($code, $customer['verification_code_hash'])) {
        $pdo->prepare('UPDATE customers SET verification_attempts=verification_attempts+1 WHERE id=?')->execute([$customerId]);
        return [false, null, 'Kode verifikasi salah.'];
    }
    $pdo->prepare('UPDATE customers SET is_verified=1, verification_code_hash=NULL, verification_expires_at=NULL, verification_sent_at=NULL, verification_attempts=0 WHERE id=?')->execute([$customerId]);
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id=? LIMIT 1');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    return [true, $customer, 'Akun Member Anda sudah terdaftar dan terverifikasi.'];
}
