<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/order-tracking.php';
require_once __DIR__ . '/../includes/loyalty.php';
require_admin();

$pageTitle = 'Nota Manual';
$pdo = db();
$error = '';
$csrf = app_csrf_token();

if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_address', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD customer_address TEXT NULL AFTER customer_phone");
        }
        if (!in_array('subtotal_before_discount', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD subtotal_before_discount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER customer_note");
        }
        if (!in_array('discount_amount', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER subtotal_before_discount");
        }
        if (!in_array('payment_status', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
        }
        if (!in_array('payment_method', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
        }
        if (!in_array('paid_amount', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER payment_method");
        }
        if (!in_array('payment_note', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD payment_note TEXT NULL AFTER paid_amount");
        }
        if (!in_array('paid_at', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD paid_at DATETIME NULL AFTER payment_note");
        }
    } catch (Throwable $e) {
        // Abaikan jika server tidak mengizinkan ALTER TABLE otomatis
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo) {
    $orderCode = trim((string)($_POST['order_code'] ?? ''));
    if ($orderCode === '') {
        $orderCode = 'NOTA-' . date('Ymd') . '-' . random_int(1000, 9999);
    }
    $createdAtRaw = trim((string)($_POST['created_at'] ?? ''));
    $createdAt = $createdAtRaw !== '' ? date('Y-m-d H:i:s', strtotime($createdAtRaw)) : date('Y-m-d H:i:s');

    $customerName = trim((string)($_POST['customer_name'] ?? 'Pelanggan Walk-in'));
    $customerPhone = trim((string)($_POST['customer_phone'] ?? '-'));
    $customerAddress = trim((string)($_POST['customer_address'] ?? ''));
    $paymentMethod = trim((string)($_POST['payment_method'] ?? 'cash'));
    $paymentStatus = trim((string)($_POST['payment_status'] ?? 'paid'));
    if ($paymentStatus === 'partial') {
        $paymentStatus = 'waiting';
    }
    $status = trim((string)($_POST['status'] ?? 'completed'));
    $allowedStatuses = ['pending', 'confirmed', 'processing', 'printed', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'completed';
    }
    $customerNote = trim((string)($_POST['customer_note'] ?? ''));
    $discountAmount = max(0, (float)($_POST['discount_amount'] ?? 0));

    $names = isset($_POST['item_name']) && is_array($_POST['item_name']) ? $_POST['item_name'] : [];
    $specs = isset($_POST['item_spec']) && is_array($_POST['item_spec']) ? $_POST['item_spec'] : [];
    $qtys = isset($_POST['item_qty']) && is_array($_POST['item_qty']) ? $_POST['item_qty'] : [];
    $units = isset($_POST['item_unit']) && is_array($_POST['item_unit']) ? $_POST['item_unit'] : [];
    $prices = isset($_POST['item_price']) && is_array($_POST['item_price']) ? $_POST['item_price'] : [];

    $items = [];
    $subtotal = 0;

    foreach ($names as $idx => $rawName) {
        $pName = trim((string)$rawName);
        if ($pName === '') {
            continue;
        }
        $qty = max(1, (int)($qtys[$idx] ?? 1));
        $unit = trim((string)($units[$idx] ?? 'pcs')) ?: 'pcs';
        $price = max(0, (float)($prices[$idx] ?? 0));
        $spec = trim((string)($specs[$idx] ?? ''));
        $rowSub = $qty * $price;
        $subtotal += $rowSub;

        $items[] = [
            'product_name' => $pName,
            'spec'         => $spec,
            'quantity'     => $qty,
            'unit'         => $unit,
            'unit_price'   => $price,
            'subtotal'     => $rowSub,
        ];
    }

    if (empty($items)) {
        $error = 'Minimal harus ada 1 baris item produk atau jasa cetak pada nota.';
    } else {
        $totalEstimate = max(0, $subtotal - $discountAmount);
        $paidAmount = max(0, (float)($_POST['paid_amount'] ?? 0));
        if ($paymentStatus === 'paid' && ($paidAmount <= 0 || $paidAmount < $totalEstimate)) {
            $paidAmount = $totalEstimate;
        }
        $paidAt = $paymentStatus === 'paid' ? date('Y-m-d H:i:s') : null;

        try {
            $pdo->beginTransaction();

            // Pastikan order_code unik
            $chkStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_code = ?');
            $chkStmt->execute([$orderCode]);
            if ((int)$chkStmt->fetchColumn() > 0) {
                $orderCode .= '-' . random_int(10, 99);
            }

            $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            $hasAddressCol = in_array('customer_address', $cols, true);

            $finalNote = $customerNote;
            if (!$hasAddressCol && $customerAddress !== '') {
                $finalNote = trim("Alamat: {$customerAddress}\n" . $customerNote);
            }

            if ($hasAddressCol) {
                $insOrder = $pdo->prepare("INSERT INTO orders (
                    order_code, customer_name, customer_phone, customer_address, customer_note,
                    subtotal_before_discount, discount_amount, total_estimate,
                    status, payment_status, payment_method, paid_amount, paid_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insOrder->execute([
                    $orderCode,
                    $customerName,
                    $customerPhone,
                    $customerAddress !== '' ? $customerAddress : null,
                    $finalNote !== '' ? $finalNote : null,
                    $subtotal,
                    $discountAmount,
                    $totalEstimate,
                    $status,
                    $paymentStatus,
                    $paymentMethod,
                    $paidAmount,
                    $paidAt,
                    $createdAt,
                ]);
            } else {
                $insOrder = $pdo->prepare("INSERT INTO orders (
                    order_code, customer_name, customer_phone, customer_note,
                    subtotal_before_discount, discount_amount, total_estimate,
                    status, payment_status, payment_method, paid_amount, paid_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insOrder->execute([
                    $orderCode,
                    $customerName,
                    $customerPhone,
                    $finalNote !== '' ? $finalNote : null,
                    $subtotal,
                    $discountAmount,
                    $totalEstimate,
                    $status,
                    $paymentStatus,
                    $paymentMethod,
                    $paidAmount,
                    $paidAt,
                    $createdAt,
                ]);
            }

            $orderId = (int)$pdo->lastInsertId();

            $insItem = $pdo->prepare("INSERT INTO order_items (
                order_id, product_id, product_name, quantity, unit, variant_data, unit_price, subtotal
            ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)");

            foreach ($items as $it) {
                $variantJson = $it['spec'] !== '' ? json_encode(['ukuran' => $it['spec']], JSON_UNESCAPED_UNICODE) : null;
                $insItem->execute([
                    $orderId,
                    $it['product_name'],
                    $it['quantity'],
                    $it['unit'],
                    $variantJson,
                    $it['unit_price'],
                    $it['subtotal'],
                ]);
            }

            try { order_record_status($pdo, $orderId, $status); } catch (Throwable $e) {}
            try { order_record_payment($pdo, $orderId, $paymentStatus); } catch (Throwable $e) {}

            $pdo->commit();

            if (($_POST['print_now'] ?? '0') === '1') {
                header('Location: ' . page_url('invoice.php?code=' . urlencode($orderCode) . '&print=1'));
                exit;
            }

            admin_flash('success', 'Nota manual ' . $orderCode . ' berhasil diterbitkan dan disimpan ke database.');
            header('Location: ' . page_url('admin/nota-manual.php'));
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal menyimpan nota manual ke database: ' . $e->getMessage();
        }
    }
}

$catalogProducts = [];
$recentOrders = [];

if ($pdo) {
    try {
        $catalogProducts = $pdo->query("SELECT name, price, unit FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $catalogProducts = [];
    }

    try {
        $ordersRaw = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
        foreach ($ordersRaw as $or) {
            $itemStmt->execute([(int)$or['id']]);
            $or['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            $recentOrders[] = $or;
        }
    } catch (Throwable $e) {
        $recentOrders = [];
    }
}

require_once __DIR__ . '/_header.php';
?>

<div class="admin-title">
    <div>
        <h1>Buat Nota Manual (Kasir Percetakan)</h1>
        <p>Terbitkan nota transaksi langsung untuk pelanggan walk-in, pesanan offline, atau kustom tanpa melalui alur web.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo page_url('admin/pesanan.php'); ?>" class="admin-btn secondary">
            <i class="bi bi-cart-check"></i> Daftar Pesanan
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="admin-alert error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
<?php endif; ?>

<!-- Main Manual Nota Generator Form -->
<form action="<?php echo page_url('admin/nota-manual.php'); ?>" method="POST" id="notaManualForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="admin-card" style="margin-bottom: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid #f2dce7; padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; color: #18243a; font-size: 18px; font-weight: 800;">
                    <i class="bi bi-receipt" style="color: #e11d64;"></i> Informasi Pelanggan & Transaksi
                </h2>
                <small style="color: #7d747c;">Lengkapi data pemesan dan nomor referensi nota.</small>
            </div>
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #e11d64; background: #fff0f6; border: 1px solid #f2c9db; padding: 4px 10px; border-radius: 999px;">
                    NOTA KASIR RESMI
                </span>
            </div>
        </div>

        <div class="admin-grid-2">
            <div class="admin-field">
                <label>Nomor Nota Transaksi *</label>
                <input type="text" name="order_code" id="orderCode" required value="NOTA-<?php echo date('Ymd'); ?>-<?php echo random_int(1000, 9999); ?>" style="font-weight: 700; color: #18243a; font-family: monospace;">
            </div>
            <div class="admin-field">
                <label>Tanggal Transaksi *</label>
                <input type="datetime-local" name="created_at" id="createdAt" required value="<?php echo date('Y-m-d\TH:i'); ?>">
            </div>
        </div>

        <div class="admin-grid-2" style="margin-top: 14px;">
            <div class="admin-field">
                <label>Nama Pelanggan / Usaha / Instansi *</label>
                <input type="text" name="customer_name" required placeholder="Contoh: Bapak Hendra (Toko Berkah)">
            </div>
            <div class="admin-field">
                <label>No. WhatsApp / Telepon *</label>
                <input type="tel" name="customer_phone" required placeholder="08xxxxxxxxxx">
            </div>
        </div>

        <div class="admin-field" style="margin-top: 14px;">
            <label>Alamat / Lokasi Pengantaran (Opsional)</label>
            <input type="text" name="customer_address" placeholder="Contoh: Jl. Diponegoro No. 14, Pasuruan">
        </div>

        <div class="admin-grid-2" style="margin-top: 14px;">
            <div class="admin-field">
                <label>Metode Pembayaran</label>
                <select name="payment_method" id="paymentMethod">
                    <option value="cash">Tunai / Cash Langsung</option>
                    <option value="transfer">Transfer Bank (BCA / Mandiri / BRI)</option>
                    <option value="qris">QRIS Rosa Indah</option>
                </select>
            </div>
            <div class="admin-field">
                <label>Status Pembayaran</label>
                <select name="payment_status" id="paymentStatus" onchange="syncPaymentStatus(this.value)">
                    <option value="paid">LUNAS (Sudah Diterima Penuh)</option>
                    <option value="waiting">DP / Sebagian</option>
                    <option value="unpaid">Belum Bayar (Tempo / Tagihan)</option>
                </select>
            </div>
        </div>

        <div class="admin-grid-2" style="margin-top: 14px;">
            <div class="admin-field">
                <label>Nominal Pembayaran Diterima (Rp) *</label>
                <input type="number" name="paid_amount" id="paidAmount" min="0" value="0" placeholder="0" oninput="calculateTotal()">
            </div>
            <div class="admin-field">
                <label>Status Alur Pesanan</label>
                <select name="status">
                    <option value="completed">Selesai (Langsung Diambil / Serah Terima)</option>
                    <option value="processing">Sedang Diproses Cetak</option>
                    <option value="printed">Selesai Dicetak (Menunggu Finishing)</option>
                    <option value="confirmed">Dikonfirmasi</option>
                </select>
            </div>
        </div>

        <div class="admin-field" style="margin-top: 14px;">
            <label>Catatan Khusus / Spesifikasi Pengerjaan (Tampil di Nota)</label>
            <textarea name="customer_note" rows="2" placeholder="Contoh: Finishing laminasi doff, lubang mata ayam tiap sudut, diambil besok jam 15.00 WIB"></textarea>
        </div>
    </div>

    <!-- Line Items Section -->
    <div class="admin-card" style="padding: 0; overflow: hidden; margin-bottom: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 22px; background: #fffcfd; border-bottom: 1px solid #f2dce7; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="margin: 0; color: #18243a; font-size: 17px; font-weight: 800;">
                    Rincian Item Produk & Jasa Cetak
                </h2>
                <small style="color: #8c8289;">Tambahkan satu atau lebih item cetak untuk nota ini.</small>
            </div>
            <button type="button" class="admin-btn secondary" onclick="addItemRow()">
                <i class="bi bi-plus-circle"></i> Tambah Baris Item
            </button>
        </div>

        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; padding: 12px 16px;">
            <table class="admin-table" id="itemsTable" style="min-width: 780px;">
                <thead>
                    <tr>
                        <th style="width: 35%;">Nama Produk / Jasa Cetak</th>
                        <th style="width: 25%;">Spesifikasi / Ukuran</th>
                        <th style="width: 12%;">Qty</th>
                        <th style="width: 12%;">Satuan</th>
                        <th style="width: 18%; text-align: right;">Harga Satuan (Rp)</th>
                        <th style="width: 18%; text-align: right;">Subtotal (Rp)</th>
                        <th style="width: 40px; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <!-- Default initial row -->
                    <tr class="item-row">
                        <td>
                            <input type="text" name="item_name[]" class="admin-input-cell item-name" required placeholder="Contoh: Banner Spanduk Flexi" list="productList" onchange="autoFillProduct(this)">
                        </td>
                        <td>
                            <input type="text" name="item_spec[]" class="admin-input-cell" placeholder="Ukuran 3x1m, Mata Ayam 4 sudut">
                        </td>
                        <td>
                            <input type="number" name="item_qty[]" class="admin-input-cell item-qty" required min="1" value="1" oninput="calculateRow(this)">
                        </td>
                        <td>
                            <select name="item_unit[]" class="admin-input-cell item-unit">
                                <option value="pcs">pcs</option>
                                <option value="meter">meter</option>
                                <option value="lembar">lembar</option>
                                <option value="box">box</option>
                                <option value="buku">buku</option>
                                <option value="rim">rim</option>
                                <option value="paket">paket</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="item_price[]" class="admin-input-cell item-price" required min="0" value="25000" oninput="calculateRow(this)" style="text-align: right;">
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #18243a; font-variant-numeric: tabular-nums;">
                            <span class="row-subtotal">Rp25.000</span>
                            <input type="hidden" name="item_subtotal[]" class="item-subtotal-val" value="25000">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Hapus Baris">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Catalog autocomplete datalist -->
        <datalist id="productList">
            <?php foreach ($catalogProducts as $p): ?>
                <option value="<?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo (float)$p['price']; ?>" data-unit="<?php echo htmlspecialchars($p['unit'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?> - Rp<?php echo number_format((float)$p['price'], 0, ',', '.'); ?>/<?php echo htmlspecialchars($p['unit'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
            <option value="Jasa Desain Grafis" data-price="25000" data-unit="pcs">Jasa Desain Grafis</option>
            <option value="Biaya Tambahan Finishing" data-price="10000" data-unit="pcs">Biaya Tambahan Finishing</option>
            <option value="Ongkos Kirim Kurir" data-price="15000" data-unit="pcs">Ongkos Kirim Kurir</option>
        </datalist>

        <!-- Calculation Summary Box -->
        <div style="background: #fff8fb; border-top: 1px solid #f2dce7; padding: 20px 24px;">
            <div style="display: flex; justify-content: flex-end;">
                <div style="width: 360px; max-width: 100%;">
                    <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 8px;">
                        <span style="color: #6e646d;">Subtotal Item:</span>
                        <strong id="displaySubtotal" style="font-variant-numeric: tabular-nums;">Rp25.000</strong>
                        <input type="hidden" name="subtotal" id="inputSubtotal" value="25000">
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; margin-bottom: 8px;">
                        <span style="color: #6e646d;">Diskon / Potongan (Rp):</span>
                        <input type="number" name="discount_amount" id="inputDiscount" min="0" value="0" style="width: 120px; text-align: right; padding: 5px 8px; border: 1px solid #eadde3; border-radius: 8px; font-size: 13px;" oninput="calculateTotal()">
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #e11d64; padding-top: 10px; border-top: 2px dashed #f1cadc; margin-top: 8px;">
                        <span>Total Tagihan:</span>
                        <span id="displayTotal" style="font-size: 20px; font-variant-numeric: tabular-nums;">Rp25.000</span>
                        <input type="hidden" name="total_estimate" id="inputTotal" value="25000">
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-top: 8px; color: #287a47;">
                        <span>Uang Telah Dibayar:</span>
                        <strong id="displayPaid" style="font-variant-numeric: tabular-nums;">Rp25.000</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-top: 4px; font-weight: 700; color: #c0392b;">
                        <span>Sisa Tagihan / Kurang:</span>
                        <strong id="displayRemaining" style="font-variant-numeric: tabular-nums;">Rp0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Toolbar -->
    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px; flex-wrap: wrap;">
        <button type="submit" name="print_now" value="0" class="admin-btn secondary" style="padding: 12px 22px;">
            <i class="bi bi-save"></i> Simpan ke Riwayat Pesanan
        </button>
        <button type="submit" name="print_now" value="1" class="admin-btn" style="padding: 12px 26px; font-size: 14px;">
            <i class="bi bi-printer-fill"></i> Simpan & Langsung Cetak Nota
        </button>
    </div>
</form>

<!-- Riwayat Nota Transaksi Terbaru -->
<div class="admin-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 22px; border-bottom: 1px solid #f2dce7; background: #fffcfd; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #18243a; font-size: 17px; font-weight: 800;">
            Riwayat Nota Transaksi Terbaru
        </h2>
        <a href="<?php echo page_url('admin/pesanan.php'); ?>" style="color: #e11d64; font-size: 12.5px; font-weight: 700; text-decoration: none;">
            Lihat Semua Pesanan →
        </a>
    </div>
    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="admin-table" style="min-width: 820px;">
            <thead>
                <tr>
                    <th>No. Nota</th>
                    <th>Nama Pelanggan</th>
                    <th>Item Pesanan</th>
                    <th>Total</th>
                    <th>Status Bayar</th>
                    <th style="width: 140px; text-align: center;">Aksi Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #8c8289;">Belum ada riwayat pesanan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $o):
                        $pStatus = (string)($o['payment_status'] ?? 'unpaid');
                        $pillClass = $pStatus === 'paid' ? 'status-pill-completed' : ($pStatus === 'waiting' || $pStatus === 'partial' ? 'status-pill-pending' : 'status-pill-cancelled');
                        $pillLabel = $pStatus === 'paid' ? 'LUNAS' : ($pStatus === 'waiting' || $pStatus === 'partial' ? 'DP / VERIFIKASI' : 'BELUM BAYAR');
                    ?>
                        <tr>
                            <td>
                                <strong style="color: #18243a;"><?php echo htmlspecialchars($o['order_code'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small style="display: block; color: #8e838b; font-size: 11px;"><?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small style="display: block; color: #7f747d; font-size: 11.5px;"><?php echo htmlspecialchars($o['customer_phone'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td>
                                <?php foreach (($o['items'] ?? []) as $it): ?>
                                    <div style="font-size: 12px; color: #3e3740;">
                                        • <?php echo htmlspecialchars($it['product_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$it['quantity']; ?> <?php echo htmlspecialchars($it['unit'], ENT_QUOTES, 'UTF-8'); ?>)
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <strong style="color: #e11d64; font-variant-numeric: tabular-nums;">
                                    Rp<?php echo number_format((float)$o['total_estimate'], 0, ',', '.'); ?>
                                </strong>
                            </td>
                            <td>
                                <span class="status-pill <?php echo $pillClass; ?>">
                                    <?php echo $pillLabel; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?php echo page_url('invoice.php?code=' . urlencode($o['order_code']) . '&print=1'); ?>" target="_blank" rel="noopener" class="admin-btn" style="padding: 5px 11px; font-size: 11.5px; border-radius: 8px;">
                                    <i class="bi bi-printer"></i> Cetak Nota
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.admin-input-cell {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #eadde3;
    border-radius: 8px;
    padding: 8px 10px;
    font: inherit;
    font-size: 13px;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s ease;
}
.admin-input-cell:focus {
    border-color: #e11d64;
    box-shadow: 0 0 0 2px #ffe8f1;
}
.btn-remove-row {
    background: transparent;
    border: 0;
    color: #e05252;
    cursor: pointer;
    font-size: 16px;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.btn-remove-row:hover {
    background: #ffecec;
}
</style>

<script>
function formatRupiahJs(number) {
    return 'Rp' + Number(number || 0).toLocaleString('id-ID');
}

function autoFillProduct(inputElem) {
    var val = inputElem.value;
    var options = document.querySelectorAll('#productList option');
    var row = inputElem.closest('tr');
    if (!row) return;
    options.forEach(function(opt) {
        if (opt.value === val) {
            var price = opt.getAttribute('data-price');
            var unit = opt.getAttribute('data-unit');
            if (price !== null && price !== '') {
                var priceInput = row.querySelector('.item-price');
                if (priceInput) priceInput.value = price;
            }
            if (unit) {
                var unitSelect = row.querySelector('.item-unit');
                if (unitSelect) unitSelect.value = unit;
            }
            calculateRow(inputElem);
        }
    });
}

function calculateRow(inputElem) {
    var row = inputElem.closest('tr');
    if (!row) return;
    var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    var price = parseFloat(row.querySelector('.item-price').value) || 0;
    var subtotal = qty * price;
    row.querySelector('.item-subtotal-val').value = subtotal;
    row.querySelector('.row-subtotal').textContent = formatRupiahJs(subtotal);
    calculateTotal();
}

function calculateTotal() {
    var subtotals = document.querySelectorAll('.item-subtotal-val');
    var totalSubtotal = 0;
    subtotals.forEach(function(el) {
        totalSubtotal += parseFloat(el.value) || 0;
    });

    var discount = parseFloat(document.getElementById('inputDiscount').value) || 0;
    var totalEstimate = Math.max(0, totalSubtotal - discount);

    document.getElementById('displaySubtotal').textContent = formatRupiahJs(totalSubtotal);
    document.getElementById('inputSubtotal').value = totalSubtotal;

    document.getElementById('displayTotal').textContent = formatRupiahJs(totalEstimate);
    document.getElementById('inputTotal').value = totalEstimate;

    var paidInput = document.getElementById('paidAmount');
    var paid = parseFloat(paidInput.value) || 0;

    var paymentStatusSelect = document.getElementById('paymentStatus');
    if (paymentStatusSelect.value === 'paid' && (paid === 0 || paid < totalEstimate)) {
        paidInput.value = totalEstimate;
        paid = totalEstimate;
    }

    document.getElementById('displayPaid').textContent = formatRupiahJs(paid);
    var remaining = Math.max(0, totalEstimate - paid);
    document.getElementById('displayRemaining').textContent = formatRupiahJs(remaining);
}

function syncPaymentStatus(val) {
    var total = parseFloat(document.getElementById('inputTotal').value) || 0;
    var paidInput = document.getElementById('paidAmount');
    if (val === 'paid') {
        paidInput.value = total;
    } else if (val === 'unpaid') {
        paidInput.value = 0;
    }
    calculateTotal();
}

function addItemRow() {
    var tbody = document.getElementById('itemsBody');
    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="text" name="item_name[]" class="admin-input-cell item-name" required placeholder="Contoh: Kartu Nama 2 Sisi" list="productList" onchange="autoFillProduct(this)">
        </td>
        <td>
            <input type="text" name="item_spec[]" class="admin-input-cell" placeholder="Art Paper 260gr, Doff">
        </td>
        <td>
            <input type="number" name="item_qty[]" class="admin-input-cell item-qty" required min="1" value="1" oninput="calculateRow(this)">
        </td>
        <td>
            <select name="item_unit[]" class="admin-input-cell item-unit">
                <option value="pcs">pcs</option>
                <option value="box">box</option>
                <option value="meter">meter</option>
                <option value="lembar">lembar</option>
                <option value="buku">buku</option>
                <option value="rim">rim</option>
                <option value="paket">paket</option>
            </select>
        </td>
        <td>
            <input type="number" name="item_price[]" class="admin-input-cell item-price" required min="0" value="0" oninput="calculateRow(this)" style="text-align: right;">
        </td>
        <td style="text-align: right; font-weight: 700; color: #18243a; font-variant-numeric: tabular-nums;">
            <span class="row-subtotal">Rp0</span>
            <input type="hidden" name="item_subtotal[]" class="item-subtotal-val" value="0">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeRow(btn) {
    var rows = document.querySelectorAll('#itemsBody .item-row');
    if (rows.length <= 1) {
        return;
    }
    var tr = btn.closest('tr');
    if (tr) {
        tr.remove();
        calculateTotal();
    }
}

window.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
