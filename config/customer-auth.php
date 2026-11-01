<?php
/**
 * Sistem Autentikasi & Member Rosa Indah Printing
 *
 * - Semua pengunjung tetap boleh belanja & checkout tanpa login.
 * - Pelanggan yang mendaftar & login mendapatkan akses halaman Profil Member (akun.php),
 *   riwayat pesanan otomatis, poin loyalitas, simpan alamat, dan klaim voucher.
 * - Nomor WhatsApp adalah identitas unik login (mendukung awalan 08 maupun 628).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        @session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    @session_start();
}

function customer_auth_init(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        username VARCHAR(80) NULL,
        phone VARCHAR(30) NOT NULL UNIQUE,
        email VARCHAR(160) NULL,
        password VARCHAR(255) NOT NULL,
        gender VARCHAR(10) NULL DEFAULT 'L',
        birth_date DATE NULL,
        city VARCHAR(100) NULL DEFAULT 'Pasuruan',
        address TEXT NULL,
        address_note VARCHAR(255) NULL,
        avatar VARCHAR(255) NULL,
        points INT NOT NULL DEFAULT 100,
        member_tier VARCHAR(60) NOT NULL DEFAULT 'Member Prioritas',
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
        'username' => "ALTER TABLE customers ADD username VARCHAR(80) NULL AFTER name",
        'gender' => "ALTER TABLE customers ADD gender VARCHAR(10) NULL DEFAULT 'L' AFTER password",
        'birth_date' => "ALTER TABLE customers ADD birth_date DATE NULL AFTER gender",
        'city' => "ALTER TABLE customers ADD city VARCHAR(100) NULL DEFAULT 'Pasuruan' AFTER birth_date",
        'address_note' => "ALTER TABLE customers ADD address_note VARCHAR(255) NULL AFTER address",
        'avatar' => "ALTER TABLE customers ADD avatar VARCHAR(255) NULL AFTER address_note",
        'points' => "ALTER TABLE customers ADD points INT NOT NULL DEFAULT 100 AFTER avatar",
        'member_tier' => "ALTER TABLE customers ADD member_tier VARCHAR(60) NOT NULL DEFAULT 'Member Prioritas' AFTER points",
        'is_verified' => "ALTER TABLE customers ADD is_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER member_tier",
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

    try {
        $orderCols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_id', $orderCols, true)) {
            try { $pdo->exec("ALTER TABLE orders ADD customer_id BIGINT UNSIGNED NULL AFTER id"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE orders ADD INDEX idx_orders_customer (customer_id)"); } catch (Throwable $e) {}
            try { $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL"); } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {}
}

function customer_logged_in(): bool {
    return !empty($_SESSION['customer_id']) || !empty($_SESSION['customer']['id']);
}

function customer_id(): ?int {
    if (!empty($_SESSION['customer_id'])) {
        return (int)$_SESSION['customer_id'];
    }
    if (!empty($_SESSION['customer']['id'])) {
        return (int)$_SESSION['customer']['id'];
    }
    return null;
}

function customer_user(): array {
    $sessionCustomer = (!empty($_SESSION['customer']) && is_array($_SESSION['customer'])) ? $_SESSION['customer'] : [];
    $id = $_SESSION['customer_id'] ?? ($sessionCustomer['id'] ?? null);
    $name = $_SESSION['customer_name'] ?? ($sessionCustomer['name'] ?? 'Member');
    $phone = $_SESSION['customer_phone'] ?? ($sessionCustomer['phone'] ?? '');
    $email = $_SESSION['customer_email'] ?? ($sessionCustomer['email'] ?? '');
    $address = $_SESSION['customer_address'] ?? ($sessionCustomer['address'] ?? '');
    $isVerified = (int)($_SESSION['customer_verified'] ?? ($sessionCustomer['is_verified'] ?? 1));

    return [
        'id'           => $id,
        'name'         => $name,
        'username'     => $sessionCustomer['username'] ?? ($_SESSION['customer_username'] ?? ''),
        'phone'        => $phone,
        'email'        => $email,
        'gender'       => $sessionCustomer['gender'] ?? ($_SESSION['customer_gender'] ?? 'L'),
        'birth_date'   => $sessionCustomer['birth_date'] ?? ($_SESSION['customer_birth_date'] ?? ''),
        'city'         => $sessionCustomer['city'] ?? ($_SESSION['customer_city'] ?? 'Pasuruan'),
        'address'      => $address,
        'address_note' => $sessionCustomer['address_note'] ?? ($_SESSION['customer_address_note'] ?? ''),
        'avatar'       => $sessionCustomer['avatar'] ?? ($_SESSION['customer_avatar'] ?? ''),
        'points'       => (int)($sessionCustomer['points'] ?? ($_SESSION['customer_points'] ?? 100)),
        'member_tier'  => $sessionCustomer['member_tier'] ?? ($_SESSION['customer_member_tier'] ?? 'Member Prioritas'),
        'is_verified'  => $isVerified,
    ];
}

function customer_login(array $customer): void {
    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        @session_regenerate_id(true);
    }
    $id = (int)($customer['id'] ?? 1);
    $name = (string)($customer['name'] ?? 'Member');
    $username = (string)($customer['username'] ?? '');
    if ($username === '') {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', trim($name))[0] ?? 'member')) ?: 'member';
    }
    $phone = (string)($customer['phone'] ?? '');
    $email = (string)($customer['email'] ?? '');
    $gender = (string)($customer['gender'] ?? 'L');
    $birthDate = (string)($customer['birth_date'] ?? '');
    $city = (string)($customer['city'] ?? 'Pasuruan');
    $address = (string)($customer['address'] ?? '');
    $addressNote = (string)($customer['address_note'] ?? '');
    $avatar = (string)($customer['avatar'] ?? '');
    $points = isset($customer['points']) ? (int)$customer['points'] : 100;
    $memberTier = (string)($customer['member_tier'] ?? 'Member Prioritas');
    $isVerified = (int)($customer['is_verified'] ?? 1);

    $_SESSION['customer_id'] = $id;
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_username'] = $username;
    $_SESSION['customer_phone'] = $phone;
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_gender'] = $gender;
    $_SESSION['customer_birth_date'] = $birthDate;
    $_SESSION['customer_city'] = $city;
    $_SESSION['customer_address'] = $address;
    $_SESSION['customer_address_note'] = $addressNote;
    $_SESSION['customer_avatar'] = $avatar;
    $_SESSION['customer_points'] = $points;
    $_SESSION['customer_member_tier'] = $memberTier;
    $_SESSION['customer_verified'] = $isVerified;

    $_SESSION['customer'] = [
        'id'           => $id,
        'name'         => $name,
        'username'     => $username,
        'phone'        => $phone,
        'email'        => $email,
        'gender'       => $gender,
        'birth_date'   => $birthDate,
        'city'         => $city,
        'address'      => $address,
        'address_note' => $addressNote,
        'avatar'       => $avatar,
        'points'       => $points,
        'member_tier'  => $memberTier,
        'is_verified'  => $isVerified,
    ];
}

function customer_logout(): void {
    unset(
        $_SESSION['customer'],
        $_SESSION['customer_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_username'],
        $_SESSION['customer_phone'],
        $_SESSION['customer_email'],
        $_SESSION['customer_gender'],
        $_SESSION['customer_birth_date'],
        $_SESSION['customer_city'],
        $_SESSION['customer_address'],
        $_SESSION['customer_address_note'],
        $_SESSION['customer_avatar'],
        $_SESSION['customer_points'],
        $_SESSION['customer_member_tier'],
        $_SESSION['customer_verified']
    );
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

/**
 * Menghasilkan kandidat format nomor telepon (628... dan 08...)
 * agar pencarian login / duplikasi nomor tetap cocok meskipun di database tertulis 08... atau 628...
 */
function customer_phone_candidates(string $value): array {
    $norm = customer_normalize_phone($value);
    if ($norm === '') return ['', ''];
    $local = str_starts_with($norm, '62') ? ('0' . substr($norm, 2)) : $norm;
    return [$norm, $local];
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
