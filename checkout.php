<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/order-tracking.php';
require_once __DIR__ . '/config/customer-auth.php';
require_once __DIR__ . '/includes/promotions.php';
require_once __DIR__ . '/includes/loyalty.php';
if ($pdo) {
    try {
        loyalty_ensure_tables($pdo);
    } catch (Throwable $e) {
    }
}

cart_init();
$pdoInit = db();
$pdo = $pdoInit;
if ($pdoInit) {
    try {
        customer_auth_init($pdoInit);
        ensure_promotions_tables($pdoInit);
    } catch (Throwable $e) {
    }
}
$loggedCustomer = customer_logged_in() ? customer_user() : null;
$error = '';
$buyId = trim($_GET['buy'] ?? '');
$buyProduct = null;
if ($buyId !== '') {
    foreach ($products as $item) if ($item['id'] === $buyId) {
        $buyProduct = $item;
        break;
    }
}

$items = [];
if ($buyProduct) {
    $items[] = ['product' => $buyProduct, 'quantity' => 1];
} else {
    foreach (cart_items() as $productId => $row) {
        foreach ($products as $item) {
            if ($item['id'] !== $productId) continue;
            $items[] = ['product' => $item, 'quantity' => max(1, (int)$row['quantity'])];
            break;
        }
    }
}

function ensure_order_attachments_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_attachments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id BIGINT UNSIGNED NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_order_attachments_order (order_id)
    ) ENGINE=InnoDB");
}

function upload_order_files(array $files, string $orderCode, string $baseDir): array
{
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $maxBytes = 10 * 1024 * 1024;
    $maxFiles = 3;
    $uploaded = [];
    if (empty($files['name'])) return $uploaded;
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    if (count($names) > $maxFiles) throw new RuntimeException('Maksimal 3 file desain dapat diunggah.');
    $targetDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $orderCode;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) throw new RuntimeException('Folder upload desain tidak dapat dibuat.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($names as $i => $original) {
        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($errors[$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Ada file desain yang gagal diunggah.');
        $tmp = $tmpNames[$i] ?? '';
        $size = (int)($sizes[$i] ?? 0);
        $original = trim((string)$original);
        if ($original === '' || !is_uploaded_file($tmp)) throw new RuntimeException('File desain tidak valid.');
        if ($size <= 0 || $size > $maxBytes) throw new RuntimeException('Ukuran setiap file desain maksimal 10 MB.');
        $mime = $finfo->file($tmp) ?: '';
        if (!isset($allowed[$mime])) throw new RuntimeException('Format desain hanya JPG, PNG, WEBP, atau PDF.');
        $ext = $allowed[$mime];
        $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($original, PATHINFO_FILENAME));
        $safeBase = trim($safeBase, '-_') ?: 'desain';
        $stored = $safeBase . '-' . bin2hex(random_bytes(5)) . '.' . $ext;
        $destination = $targetDir . DIRECTORY_SEPARATOR . $stored;
        if (!move_uploaded_file($tmp, $destination)) throw new RuntimeException('File desain gagal disimpan.');
        $uploaded[] = [
            'original_name' => $original,
            'stored_name' => $stored,
            'file_path' => 'uploads/order-designs/' . $orderCode . '/' . $stored,
            'mime_type' => $mime,
            'file_size' => $size,
            'absolute_path' => $destination,
        ];
    }
    return $uploaded;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!cart_verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Sesi checkout tidak valid. Silakan muat ulang halaman.';
    } elseif (!$items) {
        $error = 'Keranjang masih kosong.';
    } else {
        $name = trim($_POST['customer_name'] ?? ($loggedCustomer['name'] ?? ''));
        $phone = trim($_POST['customer_phone'] ?? ($loggedCustomer['phone'] ?? ''));
        $address = trim($_POST['customer_address'] ?? ($loggedCustomer['address'] ?? ''));
        $size = trim($_POST['size'] ?? 'Standar / sesuai produk');
        $material = trim($_POST['material'] ?? 'Standar');
        $finishing = trim($_POST['finishing'] ?? 'Tanpa finishing khusus');
        $design = trim($_POST['design'] ?? 'Kirim desain sendiri');
        $notes = trim($_POST['notes'] ?? '');
        $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));
        $designFiles = $_FILES['design_files'] ?? [];

        if ($name === '' || $phone === '' || $address === '') {
            $error = 'Nama, nomor WhatsApp, dan alamat wajib diisi.';
        } else {
            $pdo = db();
            if (!$pdo) {
                $error = 'Database belum terhubung. Pesanan belum dapat disimpan.';
            } else {
                $subtotal = 0;
                foreach ($items as $row) $subtotal += product_unit_price($row['product'], $row['quantity']) * $row['quantity'];
                $isMember = customer_logged_in();
                $appliedRule = null;
                $voucherError = '';
                if ($voucherCode !== '') {
                    [$voucherOk, $voucherRule, $voucherMessage] = promo_apply_voucher($pdo, $voucherCode, $items, $isMember, $subtotal, $loggedCustomer ? (int)$loggedCustomer['id'] : null);
                    if (!$voucherOk) {
                        $error = $voucherMessage;
                    } else {
                        $appliedRule = $voucherRule;
                    }
                } else {
                    $appliedRule = promo_get_best($pdo, $items, $isMember, $subtotal);
                }
                if ($error !== '') {
                    // Jangan menyimpan pesanan jika voucher tidak valid.
                } else {
                    $discount = $appliedRule ? (float)$appliedRule['discount_amount'] : 0.0;
                    $total = max(0.0, $subtotal - $discount);
                    $orderCode = 'RIP-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
                    $promoNote = $appliedRule ? ($appliedRule['source'] === 'voucher' ? 'Voucher: ' . $appliedRule['code'] : 'Promo: ' . $appliedRule['name']) : 'Tidak ada promo/voucher';
                    $customerNote = "Alamat: {$address}\nUkuran: {$size}\nBahan: {$material}\nFinishing: {$finishing}\nDesain: {$design}\nCatatan: {$notes}\n{$promoNote}";
                    $uploadedFiles = [];
                    try {
                        ensure_order_attachments_table($pdo);
                        $uploadDir = __DIR__ . '/uploads/order-designs';
                        $uploadedFiles = upload_order_files($designFiles, $orderCode, $uploadDir);
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare('INSERT INTO orders (customer_id, order_code, customer_name, customer_phone, customer_note, subtotal_before_discount, discount_amount, voucher_code, promotion_id, total_estimate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$loggedCustomer['id'] ?? null, $orderCode, $name, $phone, $customerNote, $subtotal, $discount, $appliedRule && $appliedRule['source'] === 'voucher' ? $appliedRule['code'] : null, $appliedRule && $appliedRule['source'] === 'promotion' ? (int)$appliedRule['id'] : null, $total, 'pending']);
                        $orderId = (int)$pdo->lastInsertId();
                        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit, variant_data, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        foreach ($items as $row) {
                            $p = $row['product'];
                            $qty = $row['quantity'];
                            $price = product_unit_price($p, $qty);
                            $variantData = json_encode(['ukuran' => $size, 'bahan' => $material, 'finishing' => $finishing, 'desain' => $design], JSON_UNESCAPED_UNICODE);
                            $itemStmt->execute([$orderId, $p['id'], $p['name'], $qty, $p['unit'], $variantData, $price, $price * $qty]);
                        }
                        if ($uploadedFiles) {
                            $attachStmt = $pdo->prepare('INSERT INTO order_attachments (order_id, original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)');
                            foreach ($uploadedFiles as $file) {
                                $attachStmt->execute([$orderId, $file['original_name'], $file['stored_name'], $file['file_path'], $file['mime_type'], $file['file_size']]);
                            }
                        }
                        if ($appliedRule) {
                            if ($appliedRule['source'] === 'voucher') {
                                $u = $pdo->prepare('UPDATE vouchers SET usage_count=usage_count+1 WHERE id=? AND (quota IS NULL OR usage_count < quota)');
                                $u->execute([(int)$appliedRule['id']]);
                                if ($u->rowCount() !== 1) throw new RuntimeException('Kuota voucher baru saja habis. Silakan coba lagi.');
                                $r = $pdo->prepare('INSERT INTO voucher_redemptions (voucher_id, customer_id, order_id, code, discount_amount) VALUES (?,?,?,?,?)');
                                $r->execute([(int)$appliedRule['id'], $loggedCustomer['id'] ?? null, $orderId, $appliedRule['code'], $discount]);
                            } else {
                                $u = $pdo->prepare('UPDATE promotions SET usage_count=usage_count+1 WHERE id=? AND (quota IS NULL OR usage_count < quota)');
                                $u->execute([(int)$appliedRule['id']]);
                                if ($u->rowCount() !== 1) throw new RuntimeException('Kuota promo baru saja habis. Silakan coba lagi.');
                            }
                        }
                        $pdo->commit();
                        try {
                            order_record_status($pdo, $orderId, 'pending');
                        } catch (Throwable $e) {
                        }

                        $lines = [];
                        $lines[] = 'Halo Admin Rosa Indah Printing 👋';
                        $lines[] = '';
                        $lines[] = '*PESANAN BARU*';
                        $lines[] = 'No. Pesanan: ' . $orderCode;
                        $lines[] = '';
                        $lines[] = '*DETAIL PESANAN*';
                        foreach ($items as $row) {
                            $p = $row['product'];
                            $qty = $row['quantity'];
                            $linePrice = product_unit_price($p, $qty);
                            $lines[] = '• ' . $p['name'] . ' — ' . $qty . ' ' . $p['unit'];
                            $lines[] = '  Rp' . number_format($linePrice * $qty, 0, ',', '.');
                        }
                        $lines[] = '';
                        $lines[] = 'Ukuran: ' . $size;
                        $lines[] = 'Bahan: ' . $material;
                        $lines[] = 'Finishing: ' . $finishing;
                        $lines[] = 'Desain: ' . $design;
                        $lines[] = 'File desain: ' . (count($uploadedFiles) ? count($uploadedFiles) . ' file terlampir di sistem Admin' : 'Tidak ada');
                        $lines[] = 'Subtotal: Rp' . number_format($subtotal, 0, ',', '.');
                        if ($discount > 0) $lines[] = ($appliedRule['source'] === 'voucher' ? 'Voucher ' . $appliedRule['code'] : 'Promo ' . $appliedRule['name']) . ': -Rp' . number_format($discount, 0, ',', '.');
                        $lines[] = 'Estimasi Total: Rp' . number_format($total, 0, ',', '.');
                        $lines[] = '';
                        $lines[] = '*DATA PEMESAN*';
                        $lines[] = 'Nama: ' . $name;
                        $lines[] = 'No. WhatsApp: ' . $phone;
                        $lines[] = 'Alamat: ' . $address;
                        $lines[] = 'Catatan: ' . ($notes ?: '-');
                        $lines[] = '';
                        $lines[] = 'Lacak pesanan: ' . page_url('lacak-pesanan.php?code=' . urlencode($orderCode) . '&phone=' . urlencode($phone));
                        $lines[] = 'Konfirmasi pembayaran: ' . page_url('pembayaran.php?code=' . urlencode($orderCode));
                        $lines[] = '';
                        $lines[] = 'Mohon dibantu konfirmasi harga final dan proses pesanannya. Terima kasih 🙏';
                        cart_clear();
                        $waUrl = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . rawurlencode(implode("\n", $lines));
                        header('Location: ' . $waUrl);
                        exit;
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        foreach ($uploadedFiles as $file) {
                            if (!empty($file['absolute_path']) && is_file($file['absolute_path'])) @unlink($file['absolute_path']);
                        }
                        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Pesanan gagal disimpan. Silakan coba lagi.';
                    }
                }
            }
        }
    }
}

$total = 0;
foreach ($items as $row) $total += product_unit_price($row['product'], $row['quantity']) * $row['quantity'];
include __DIR__ . '/includes/header.php';
?>
<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>
<main class="checkout-page">
    <div class="container">
        <div class="catalog-breadcrumb detail-breadcrumb"><a href="<?php echo page_url('index.php'); ?>">Home</a><i class="bi bi-chevron-right"></i><a href="<?php echo page_url('cart.php'); ?>">Keranjang</a><i class="bi bi-chevron-right"></i><span>Checkout</span></div>
        <?php if (!$items): ?>
            <section class="checkout-empty"><i class="bi bi-bag-x"></i>
                <h1>Belum ada produk</h1>
                <p>Tambahkan produk ke keranjang sebelum checkout.</p><a class="catalog-primary-btn" href="<?php echo page_url('produk.php'); ?>">Lihat Produk</a>
            </section>
        <?php else: ?>
            <section class="checkout-heading"><span class="small-title">CHECKOUT PESANAN</span>
                <h1>Lengkapi Detail <span>Pesanan Anda</span></h1>
                <p>Pesanan akan disimpan ke sistem dan setelah berhasil Anda akan diarahkan ke WhatsApp Admin Rosa Indah Printing.</p>
            </section>
            <?php if ($error): ?><div class="cart-alert error"><i class="bi bi-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($loggedCustomer): ?><div class="account-checkout-note"><i class="bi bi-person-check"></i> Anda login sebagai <strong><?php echo htmlspecialchars($loggedCustomer['name'], ENT_QUOTES, 'UTF-8'); ?></strong>. Data profil sudah diisi otomatis.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" class="checkout-grid" autocomplete="on">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                <div class="checkout-main-card">
                    <div class="checkout-section-title"><span>01</span>
                        <div>
                            <h2>Produk yang Dipilih</h2>
                            <p>Periksa produk sebelum melanjutkan.</p>
                        </div>
                    </div>
                    <div class="checkout-items-preview">
                        <?php foreach ($items as $row): $p = $row['product']; ?>
                            <div class="checkout-product"><img src="<?php echo product_image_url($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                <div><span class="detail-category"><?php echo htmlspecialchars($p['category']); ?></span>
                                    <h3><?php echo htmlspecialchars($p['name']); ?></h3><small><?php echo $row['quantity']; ?> <?php echo htmlspecialchars($p['unit']); ?> × Rp<?php echo number_format(product_unit_price($p, $row['quantity']), 0, ',', '.'); ?></small>
                                </div><strong>Rp<?php echo number_format(product_unit_price($p, $row['quantity']) * $row['quantity'], 0, ',', '.'); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="checkout-section-title"><span>02</span>
                        <div>
                            <h2>Spesifikasi Pesanan</h2>
                            <p>Sesuaikan detail cetakan yang Anda inginkan.</p>
                        </div>
                    </div>
                    <div class="checkout-form-grid">
                        <label>Ukuran<select name="size">
                                <option>Standar / sesuai produk</option>
                                <option>A4</option>
                                <option>A5</option>
                                <option>F4</option>
                                <option>Custom</option>
                            </select></label>
                        <label>Bahan<select name="material">
                                <option>Standar / sesuai produk</option>
                                <option>Art Paper</option>
                                <option>Art Carton</option>
                                <option>Ivory</option>
                                <option>HVS</option>
                                <option>Vinyl</option>
                                <option>Flexi</option>
                                <option>Custom / konsultasi</option>
                            </select></label>
                        <label>Finishing<select name="finishing">
                                <option>Tanpa finishing khusus</option>
                                <option>Laminasi Glossy</option>
                                <option>Laminasi Doff</option>
                                <option>Potong / Cutting</option>
                                <option>Lipat</option>
                                <option>Custom / konsultasi</option>
                            </select></label>
                        <label>Jenis Desain<select name="design">
                                <option>Kirim desain sendiri</option>
                                <option>Belum punya desain, minta dibantu</option>
                                <option>Revisi desain yang sudah ada</option>
                            </select></label>
                        <label class="full">Catatan Pesanan<textarea name="notes" rows="4" placeholder="Nama yang dicetak, warna, deadline, atau permintaan khusus..."></textarea></label>
                    </div>
                    <div class="checkout-section-title"><span>03</span>
                        <div>
                            <h2>File / Desain</h2>
                            <p>Opsional — kirim desain yang ingin dicetak.</p>
                        </div>
                    </div>
                    <div class="checkout-upload-box">
                        <label class="checkout-upload-label"><i class="bi bi-cloud-arrow-up"></i><span><strong>Pilih file desain</strong><small>JPG, PNG, WEBP atau PDF • maksimal 10 MB per file • maksimal 3 file</small></span><input id="designFiles" type="file" name="design_files[]" accept="image/jpeg,image/png,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf" multiple></label>
                        <div id="designPreview" class="checkout-design-preview" aria-live="polite"></div>
                        <div class="checkout-upload-help"><i class="bi bi-info-circle"></i> Jika belum punya desain, kosongkan bagian ini dan pilih bantuan desain pada spesifikasi.</div>
                    </div>
                    <div class="checkout-section-title"><span>04</span>
                        <div>
                            <h2>Promo & Voucher Member</h2>
                            <p>Gunakan voucher atau dapatkan promo otomatis jika memenuhi syarat.</p>
                        </div>
                    </div>
                    <?php $checkoutPreviewSubtotal = 0;
                    foreach ($items as $r) $checkoutPreviewSubtotal += product_unit_price($r['product'], $r['quantity']) * $r['quantity']; ?>
                    <div class="checkout-promo-box">
                        <div class="checkout-promo-info"><i class="bi bi-ticket-perforated-fill"></i>
                            <div><strong>🎁 Promo khusus Member</strong><small>Voucher yang ditandai khusus Member hanya dapat digunakan setelah Login Member.</small></div>
                        </div>
                        <div class="checkout-voucher-row"><input type="text" name="voucher_code" maxlength="60" autocomplete="off" placeholder="Masukkan kode voucher (opsional)" value="<?php echo htmlspecialchars($_POST['voucher_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><span class="checkout-voucher-note">Jika kode kosong, sistem otomatis memilih promo aktif terbaik.</span></div>
                        <?php if ($pdo): $checkoutPromo = promo_get_best($pdo, $items, (bool)$loggedCustomer, $checkoutPreviewSubtotal);
                            if ($checkoutPromo): ?><div class="checkout-active-promo"><i class="bi bi-stars"></i><span><strong><?php echo htmlspecialchars($checkoutPromo['name']); ?></strong> — <?php echo htmlspecialchars(promo_label($checkoutPromo)); ?>. Promo akan diterapkan otomatis jika Anda tidak memakai voucher.</span></div><?php endif;
                                                                                                                                                                                                                                                                                                                                    endif; ?>
                    </div>
                    <div class="checkout-section-title customer-title"><span>05</span>
                        <div>
                            <h2>Data Pemesan</h2>
                            <p>Data ini digunakan untuk menghubungi Anda.</p>
                        </div>
                    </div>
                    <div class="checkout-form-grid">
                        <label>Nama Lengkap<input name="customer_name" type="text" required placeholder="Masukkan nama lengkap" value="<?php echo htmlspecialchars($loggedCustomer['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></label>
                        <label>No. WhatsApp<input name="customer_phone" type="tel" required placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($loggedCustomer['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></label>
                        <label class="full">Alamat / Lokasi Pengiriman<textarea name="customer_address" rows="3" required placeholder="Masukkan alamat atau lokasi pengiriman/pengambilan"><?php echo htmlspecialchars($loggedCustomer['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
                    </div>
                </div>
                <?php $previewSubtotal = 0;
                foreach ($items as $r) $previewSubtotal += product_unit_price($r['product'], $r['quantity']) * $r['quantity'];
                $previewRule = null;
                $previewDiscount = 0;
                $previewVoucher = strtoupper(trim($_POST['voucher_code'] ?? ''));
                if ($pdo) {
                    if ($previewVoucher !== '') {
                        [$pvOk, $pvRule, $pvMsg] = promo_apply_voucher($pdo, $previewVoucher, $items, (bool)$loggedCustomer, $previewSubtotal, $loggedCustomer ? (int)$loggedCustomer['id'] : null);
                        if ($pvOk) $previewRule = $pvRule;
                    } else {
                        $previewRule = promo_get_best($pdo, $items, (bool)$loggedCustomer, $previewSubtotal);
                    }
                    if ($previewRule) $previewDiscount = (float)$previewRule['discount_amount'];
                }
                $previewTotal = max(0, $previewSubtotal - $previewDiscount); ?>
                <aside class="checkout-summary-card">
                    <div class="summary-top"><span>RINGKASAN PESANAN</span><i class="bi bi-receipt"></i></div>
                    <div class="summary-lines">
                        <div><span>Jumlah item</span><strong><?php echo array_sum(array_column($items, 'quantity')); ?></strong></div>
                        <div><span>Subtotal</span><strong>Rp<?php echo number_format($previewSubtotal, 0, ',', '.'); ?></strong></div><?php if ($previewDiscount > 0): ?><div class="summary-discount"><span><?php echo $previewRule['source'] === 'voucher' ? 'Voucher ' . $previewRule['code'] : 'Promo ' . htmlspecialchars($previewRule['name']); ?></span><strong>-Rp<?php echo number_format($previewDiscount, 0, ',', '.'); ?></strong></div><?php endif; ?><div class="summary-total"><span>Estimasi total</span><strong>Rp<?php echo number_format($previewTotal, 0, ',', '.'); ?></strong></div>
                    </div>
                    <div class="summary-note"><i class="bi bi-info-circle"></i>
                        <p>Harga merupakan estimasi. Admin akan mengonfirmasi harga final sesuai spesifikasi produksi. Promo/voucher yang berhasil diterapkan dicatat pada pesanan.</p>
                    </div><button type="submit" class="checkout-wa-btn"><i class="bi bi-whatsapp"></i> Simpan Pesanan & Lanjut WhatsApp</button><a class="summary-back" href="<?php echo page_url('cart.php'); ?>"><i class="bi bi-arrow-left"></i> Kembali ke keranjang</a>
                </aside>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
    (function() {
        const input = document.getElementById('designFiles');
        const preview = document.getElementById('designPreview');
        if (!input || !preview) return;

        const maxFiles = 3;
        const maxBytes = 10 * 1024 * 1024;
        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        input.addEventListener('change', function() {
            preview.innerHTML = '';
            const files = Array.from(input.files || []);
            if (!files.length) return;

            if (files.length > maxFiles) {
                alert('Maksimal 3 file desain.');
                input.value = '';
                return;
            }

            const valid = [];
            files.forEach(function(file) {
                if (!allowed.includes(file.type)) {
                    alert('Format tidak didukung: ' + file.name + '. Gunakan JPG, PNG, WEBP, atau PDF.');
                    return;
                }
                if (file.size <= 0 || file.size > maxBytes) {
                    alert('File ' + file.name + ' melebihi batas 10 MB.');
                    return;
                }
                valid.push(file);
            });

            if (valid.length !== files.length) {
                input.value = '';
                preview.innerHTML = '';
                return;
            }

            valid.forEach(function(file) {
                const card = document.createElement('div');
                card.className = 'checkout-design-preview-item';

                const visual = document.createElement('div');
                visual.className = 'checkout-design-preview-visual';

                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.alt = file.name;
                    img.loading = 'lazy';
                    const url = URL.createObjectURL(file);
                    img.src = url;
                    img.onload = function() {
                        URL.revokeObjectURL(url);
                    };
                    visual.appendChild(img);
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-file-earmark-pdf-fill';
                    visual.appendChild(icon);
                }

                const info = document.createElement('div');
                info.className = 'checkout-design-preview-info';
                const name = document.createElement('strong');
                name.textContent = file.name;
                const size = document.createElement('small');
                size.textContent = (file.size / 1048576).toFixed(2) + ' MB • ' + (file.type === 'application/pdf' ? 'PDF' : 'Gambar');
                info.appendChild(name);
                info.appendChild(size);

                const ok = document.createElement('i');
                ok.className = 'bi bi-check-circle-fill checkout-design-ok';
                ok.title = 'File siap diunggah';

                card.appendChild(visual);
                card.appendChild(info);
                card.appendChild(ok);
                preview.appendChild(card);
            });
        });
    })();
</script>

<style>
    .checkout-promo-box {
        border: 1px solid #f0d5e2;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(135deg, #fff7fb, #fff);
        display: grid;
        gap: 12px;
        margin-bottom: 20px
    }

    .checkout-promo-info {
        display: flex;
        gap: 12px;
        align-items: center
    }

    .checkout-promo-info>i {
        font-size: 24px;
        color: #d64a8a
    }

    .checkout-promo-info strong,
    .checkout-promo-info small {
        display: block
    }

    .checkout-promo-info strong {
        color: #18243a
    }

    .checkout-promo-info small {
        color: #7b7279;
        margin-top: 3px;
        line-height: 1.45
    }

    .checkout-voucher-row {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap
    }

    .checkout-voucher-row input {
        flex: 1;
        min-width: 220px;
        border: 1px solid #eadde3;
        border-radius: 11px;
        padding: 11px 13px;
        text-transform: uppercase;
        box-sizing: border-box
    }

    .checkout-voucher-note {
        font-size: 12px;
        color: #8a8088
    }

    .checkout-active-promo {
        display: flex;
        gap: 9px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #fff0f7;
        color: #725d68;
        font-size: 12px;
        line-height: 1.45
    }

    .checkout-active-promo i {
        color: #d64a8a
    }

    .summary-discount {
        color: #2f8a58
    }

    .summary-discount strong {
        color: #2f8a58
    }

    @media(max-width:600px) {
        .checkout-voucher-row {
            display: block
        }

        .checkout-voucher-row input {
            width: 100%;
            min-width: 0
        }

        .checkout-voucher-note {
            display: block;
            margin-top: 6px
        }
    }
</style>
<?php include __DIR__ . '/includes/footer.php'; ?>