<?php
/**
 * Tahap 22 — Promo, Voucher & Diskon Member
 *
 * Promo = diskon otomatis yang memenuhi syarat.
 * Voucher = kode yang dimasukkan customer saat checkout.
 * Keduanya dapat dibatasi hanya untuk Member, tanggal, minimum belanja,
 * kategori/produk, kuota, serta jenis diskon persen atau nominal.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/products-data.php';

function ensure_promotions_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promotions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        description TEXT NULL,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
        min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
        max_discount DECIMAL(14,2) NULL,
        scope_type ENUM('all','category','product') NOT NULL DEFAULT 'all',
        scope_value VARCHAR(180) NULL,
        member_only TINYINT(1) NOT NULL DEFAULT 1,
        quota INT UNSIGNED NULL,
        usage_count INT UNSIGNED NOT NULL DEFAULT 0,
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_promotions_active (status, starts_at, ends_at),
        INDEX idx_promotions_scope (scope_type, scope_value)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS vouchers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(60) NOT NULL UNIQUE,
        name VARCHAR(160) NOT NULL,
        description TEXT NULL,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
        min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
        max_discount DECIMAL(14,2) NULL,
        scope_type ENUM('all','category','product') NOT NULL DEFAULT 'all',
        scope_value VARCHAR(180) NULL,
        member_only TINYINT(1) NOT NULL DEFAULT 1,
        quota INT UNSIGNED NULL,
        usage_count INT UNSIGNED NOT NULL DEFAULT 0,
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_vouchers_active (status, starts_at, ends_at),
        INDEX idx_vouchers_code (code)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS voucher_redemptions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        voucher_id BIGINT UNSIGNED NOT NULL,
        customer_id BIGINT UNSIGNED NULL,
        order_id BIGINT UNSIGNED NOT NULL,
        code VARCHAR(60) NOT NULL,
        discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_voucher_order (voucher_id, order_id),
        INDEX idx_voucher_customer (voucher_id, customer_id),
        FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $cols = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    $migrations = [
        'subtotal_before_discount' => "ALTER TABLE orders ADD subtotal_before_discount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER customer_note",
        'discount_amount' => "ALTER TABLE orders ADD discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER subtotal_before_discount",
        'voucher_code' => "ALTER TABLE orders ADD voucher_code VARCHAR(60) NULL AFTER discount_amount",
        'promotion_id' => "ALTER TABLE orders ADD promotion_id BIGINT UNSIGNED NULL AFTER voucher_code",
    ];
    foreach ($migrations as $col => $sql) {
        if (!in_array($col, $cols, true)) {
            try { $pdo->exec($sql); } catch (Throwable $e) {}
        }
    }
}

function promo_now(): string { return date('Y-m-d H:i:s'); }

function promo_scope_subtotal(array $items, string $scopeType, ?string $scopeValue): float {
    $total = 0.0;
    foreach ($items as $row) {
        $p = $row['product'];
        $match = $scopeType === 'all';
        if ($scopeType === 'category') {
            $match = strcasecmp((string)$p['category'], (string)$scopeValue) === 0;
            // Kompatibilitas promo lama yang masih menyimpan nama kategori generik.
            if (!$match) {
                $legacyCategoryProducts = [
                    'Undangan' => ['undangan-nikah','undangan-lipat-3'],
                    'Percetakan' => ['print-scan-copy','nota','buku','kalender'],
                    'Promosi' => ['banner','spanduk'],
                    'Alat Promosi' => ['kartu-nama','sticker'],
                    'Custom' => ['mug-custom','lanyard-id-card','sablon-dtf'],
                    'Foto & Souvenir' => ['cetak-foto','pigora','gantungan-kunci'],
                ];
                $match = isset($legacyCategoryProducts[(string)$scopeValue]) && in_array((string)$p['id'], $legacyCategoryProducts[(string)$scopeValue], true);
            }
        }
        if ($scopeType === 'product') $match = (string)$p['id'] === (string)$scopeValue;
        if ($match) {
            $qty = max(1, (int)$row['quantity']);
            $total += product_unit_price($p, $qty) * $qty;
        }
    }
    return $total;
}

function promo_calculate_discount(array $rule, float $eligibleSubtotal): float {
    if ($eligibleSubtotal <= 0 || $eligibleSubtotal < (float)$rule['min_order']) return 0.0;
    if (($rule['quota'] ?? null) !== null && (int)$rule['usage_count'] >= (int)$rule['quota']) return 0.0;
    if ($rule['discount_type'] === 'fixed') {
        $discount = min($eligibleSubtotal, (float)$rule['discount_value']);
    } else {
        $discount = $eligibleSubtotal * ((float)$rule['discount_value'] / 100);
        if ($rule['max_discount'] !== null && $rule['max_discount'] !== '') $discount = min($discount, (float)$rule['max_discount']);
    }
    return max(0.0, min($eligibleSubtotal, round($discount, 2)));
}

function promo_is_eligible(array $rule, bool $isMember, array $items, float $subtotal): bool {
    if (($rule['status'] ?? '') !== 'active') return false;
    $now = time();
    if (strtotime((string)$rule['starts_at']) > $now || strtotime((string)$rule['ends_at']) < $now) return false;
    if ((int)($rule['member_only'] ?? 0) === 1 && !$isMember) return false;
    if ((float)($rule['min_order'] ?? 0) > $subtotal) return false;
    $eligible = promo_scope_subtotal($items, (string)$rule['scope_type'], $rule['scope_value'] ?? null);
    return promo_calculate_discount($rule, $eligible) > 0;
}

function promo_get_best(PDO $pdo, array $items, bool $isMember, float $subtotal): ?array {
    ensure_promotions_tables($pdo);
    $stmt = $pdo->query("SELECT * FROM promotions WHERE status='active' AND starts_at <= NOW() AND ends_at >= NOW() ORDER BY discount_value DESC, id DESC");
    $best = null; $bestDiscount = 0.0;
    foreach ($stmt->fetchAll() as $rule) {
        if (!promo_is_eligible($rule, $isMember, $items, $subtotal)) continue;
        $eligible = promo_scope_subtotal($items, (string)$rule['scope_type'], $rule['scope_value'] ?? null);
        $discount = promo_calculate_discount($rule, $eligible);
        if ($discount > $bestDiscount) {
            $bestDiscount = $discount;
            $rule['discount_amount'] = $discount;
            $rule['eligible_subtotal'] = $eligible;
            $rule['source'] = 'promotion';
            $best = $rule;
        }
    }
    return $best;
}

function promo_find_voucher(PDO $pdo, string $code): ?array {
    ensure_promotions_tables($pdo);
    $code = strtoupper(trim($code));
    if ($code === '') return null;
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code=? LIMIT 1");
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function promo_apply_voucher(PDO $pdo, string $code, array $items, bool $isMember, float $subtotal, ?int $customerId = null): array {
    $code = strtoupper(trim($code));
    if ($code === '') return [true, null, ''];
    $rule = promo_find_voucher($pdo, $code);
    if (!$rule) return [false, null, 'Kode voucher tidak ditemukan.'];
    if (($rule['status'] ?? '') !== 'active' || strtotime((string)$rule['starts_at']) > time() || strtotime((string)$rule['ends_at']) < time()) {
        return [false, null, 'Voucher sudah tidak aktif atau di luar masa berlaku.'];
    }
    if ((int)$rule['member_only'] === 1 && !$isMember) return [false, null, 'Voucher ini khusus Member. Silakan Login Member terlebih dahulu.'];
    if (isset($rule['customer_id']) && $rule['customer_id'] !== null && (int)$rule['customer_id'] > 0 && (int)$rule['customer_id'] !== (int)$customerId) return [false, null, 'Voucher reward ini hanya dapat digunakan oleh Member yang menukarkannya.'];
    if ((int)($rule['quota'] ?? 0) > 0 && (int)$rule['usage_count'] >= (int)$rule['quota']) return [false, null, 'Kuota voucher sudah habis.'];
    if ((float)$rule['min_order'] > $subtotal) return [false, null, 'Minimum belanja untuk voucher ini adalah Rp' . number_format((float)$rule['min_order'],0,',','.').'.'];
    $eligible = promo_scope_subtotal($items, (string)$rule['scope_type'], $rule['scope_value'] ?? null);
    $discount = promo_calculate_discount($rule, $eligible);
    if ($discount <= 0) return [false, null, 'Voucher tidak berlaku untuk produk yang dipilih.'];
    $rule['discount_amount'] = $discount;
    $rule['eligible_subtotal'] = $eligible;
    $rule['source'] = 'voucher';
    return [true, $rule, 'Voucher berhasil diterapkan.'];
}

function promo_label(array $rule): string {
    if ($rule['discount_type'] === 'fixed') return 'Diskon Rp' . number_format((float)$rule['discount_value'],0,',','.');
    $label = 'Diskon ' . rtrim(rtrim(number_format((float)$rule['discount_value'],2,',','.'),'0'),',') . '%';
    if ($rule['max_discount'] !== null && $rule['max_discount'] !== '') $label .= ' maks. Rp' . number_format((float)$rule['max_discount'],0,',','.');
    return $label;
}

function promo_scope_label(array $rule): string {
    if (($rule['scope_type'] ?? 'all') === 'category') return 'Kategori: ' . ($rule['scope_value'] ?? '-');
    if (($rule['scope_type'] ?? 'all') === 'product') return 'Produk: ' . ($rule['scope_value'] ?? '-');
    return 'Semua produk';
}

function promo_format_date(string $date): string { return date('d/m/Y H:i', strtotime($date)); }
