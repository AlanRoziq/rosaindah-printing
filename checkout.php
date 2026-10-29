<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/order-tracking.php';
require_once __DIR__ . '/config/customer-auth.php';
require_once __DIR__ . '/includes/promotions.php';
require_once __DIR__ . '/includes/loyalty.php';

$pdo = db();
if ($pdo) {
    try {
        loyalty_ensure_tables($pdo);
        customer_auth_init($pdo);
        ensure_promotions_tables($pdo);
    } catch (Throwable $e) {
    }
}

cart_init();

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$loggedCustomer = customer_logged_in() ? customer_user() : null;
$error = '';
$buyId = trim($_GET['buy'] ?? ($_POST['buy'] ?? ''));
$buyQty = max(1, (int)($_GET['qty'] ?? ($_POST['qty'] ?? 1)));
$buyProduct = null;
if ($buyId !== '') {
    foreach ($products as $item) {
        if ($item['id'] === $buyId) {
            $buyProduct = $item;
            break;
        }
    }
}

$items = [];
if ($buyProduct) {
    $unitPrice = product_unit_price($buyProduct, $buyQty);
    $items[] = [
        'product'   => $buyProduct,
        'quantity'  => $buyQty,
        'unitPrice' => $unitPrice,
        'subtotal'  => $unitPrice * $buyQty,
    ];
} else {
    foreach (cart_items() as $productId => $row) {
        foreach ($products as $item) {
            if ($item['id'] !== $productId) continue;
            $qty = max(1, (int)$row['quantity']);
            $unitPrice = product_unit_price($item, $qty);
            $items[] = [
                'product'   => $item,
                'quantity'  => $qty,
                'unitPrice' => $unitPrice,
                'subtotal'  => $unitPrice * $qty,
            ];
            break;
        }
    }
}

if (empty($items) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . page_url('cart.php'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrfToken = $_POST['csrf'] ?? '';
    if ($csrfToken !== '' && !cart_verify_csrf($csrfToken)) {
        $error = 'Sesi checkout tidak valid. Silakan muat ulang halaman.';
    } elseif (empty($items)) {
        $error = 'Keranjang belanja masih kosong.';
    } else {
        $name = trim($_POST['customer_name'] ?? ($loggedCustomer['name'] ?? ''));
        $phone = trim($_POST['customer_phone'] ?? ($loggedCustomer['phone'] ?? ''));
        $address = trim($_POST['customer_address'] ?? ($loggedCustomer['address'] ?? ''));
        $customerNoteInput = trim($_POST['customer_note'] ?? ($_POST['notes'] ?? ''));
        $paymentMethod = trim($_POST['payment_method'] ?? 'transfer');
        if (!in_array($paymentMethod, ['transfer', 'qris', 'cash'], true)) {
            $paymentMethod = 'transfer';
        }
        $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));

        if ($name === '' || $phone === '') {
            $error = 'Nama lengkap dan nomor WhatsApp wajib diisi.';
        } else {
            $subtotal = 0;
            foreach ($items as $row) {
                $subtotal += $row['subtotal'];
            }

            $isMember = customer_logged_in();
            $appliedRule = null;
            $discount = 0.0;

            if ($pdo) {
                if ($voucherCode !== '') {
                    [$voucherOk, $voucherRule, $voucherMessage] = promo_apply_voucher(
                        $pdo,
                        $voucherCode,
                        $items,
                        $isMember,
                        $subtotal,
                        $loggedCustomer ? (int)$loggedCustomer['id'] : null
                    );
                    if ($voucherOk && $voucherRule) {
                        $appliedRule = $voucherRule;
                        $discount = (float)$appliedRule['discount_amount'];
                    }
                } else {
                    $appliedRule = promo_get_best($pdo, $items, $isMember, $subtotal);
                    if ($appliedRule) {
                        $discount = (float)$appliedRule['discount_amount'];
                    }
                }
            }

            if ($discount <= 0 && $voucherCode !== '') {
                if ($voucherCode === 'ROSAHEMAT10' && $subtotal >= 100000) {
                    $discount = min(round($subtotal * 0.10), 50000);
                } elseif ($voucherCode === 'MEMBERBARU' && $subtotal >= 75000) {
                    $discount = 15000;
                }
            }

            $finalTotal = max(0.0, $subtotal - $discount);
            $orderCode = 'RIP-' . date('Ymd') . '-' . rand(1000, 9999);
            $combinedNote = '';
            if ($address !== '') {
                $combinedNote .= "Alamat: {$address}\n";
            } else {
                $combinedNote .= "Pengambilan: Ambil sendiri di Workshop Rosa Indah Printing\n";
            }
            if ($customerNoteInput !== '') {
                $combinedNote .= "Catatan: {$customerNoteInput}\n";
            }
            if ($voucherCode !== '' && $discount > 0) {
                $combinedNote .= "Voucher: {$voucherCode} (-" . format_rupiah($discount) . ")";
            }
            $combinedNote = trim($combinedNote);

            if ($pdo) {
                try {
                    // Pastikan kolom payment_method tersedia jika tabel lama
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                        if (!in_array('payment_status', $cols, true)) {
                            $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
                        }
                        if (!in_array('payment_method', $cols, true)) {
                            $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
                        }
                    } catch (Throwable $e) {}

                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('INSERT INTO orders (customer_id, order_code, customer_name, customer_phone, customer_note, subtotal_before_discount, discount_amount, voucher_code, promotion_id, total_estimate, status, payment_status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $loggedCustomer['id'] ?? null,
                        $orderCode,
                        $name,
                        $phone,
                        $combinedNote,
                        $subtotal,
                        $discount,
                        $voucherCode !== '' ? $voucherCode : null,
                        $appliedRule && ($appliedRule['source'] ?? '') === 'promotion' ? (int)$appliedRule['id'] : null,
                        $finalTotal,
                        'pending',
                        'unpaid',
                        $paymentMethod,
                    ]);
                    $orderId = (int)$pdo->lastInsertId();

                    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit, variant_data, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    foreach ($items as $row) {
                        $p = $row['product'];
                        $qty = (int)$row['quantity'];
                        $price = (float)$row['unitPrice'];
                        $variantData = json_encode(['catatan' => $customerNoteInput], JSON_UNESCAPED_UNICODE);
                        $itemStmt->execute([$orderId, $p['id'], $p['name'], $qty, $p['unit'], $variantData, $price, $price * $qty]);
                    }

                    $pdo->commit();
                    try {
                        order_record_status($pdo, $orderId, 'pending');
                    } catch (Throwable $e) {}
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                }
            }

            // Simpan ke sesi sebagai fallback agar langsung dapat dilacak
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['session_orders'][$orderCode] = [
                'id'               => time(),
                'order_code'       => $orderCode,
                'customer_name'    => $name,
                'customer_phone'   => $phone,
                'customer_address' => $address,
                'customer_note'    => $customerNoteInput,
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount,
                'voucher_code'     => $voucherCode,
                'total_estimate'   => $finalTotal,
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'payment_method'   => $paymentMethod,
                'paid_amount'      => 0,
                'payment_proof'    => null,
                'created_at'       => date('c'),
                'items'            => array_map(function ($it) {
                    return [
                        'product_id'   => $it['product']['id'],
                        'product_name' => $it['product']['name'],
                        'quantity'     => $it['quantity'],
                        'unit'         => $it['product']['unit'],
                        'unit_price'   => $it['unitPrice'],
                        'subtotal'     => $it['subtotal'],
                    ];
                }, $items),
                'status_history'   => [
                    [
                        'status'     => 'pending',
                        'note'       => 'Pesanan berhasil dibuat oleh pelanggan.',
                        'created_at' => date('c'),
                    ],
                ],
            ];

            if (!$buyProduct) {
                cart_clear();
            }

            header('Location: ' . page_url('lacak-pesanan.php?code=' . urlencode($orderCode) . '&phone=' . urlencode($phone) . '&new=1'));
            exit;
        }
    }
}

$total = 0;
foreach ($items as $row) {
    $total += $row['subtotal'];
}

$pageTitle = 'Checkout Pesanan';
include __DIR__ . '/includes/header.php';
?>

<style>
/* Sinkronisasi Desain Checkout Pesanan */
.checkout-page {
    background: #fbf8fa !important;
    min-height: 75vh;
}
.checkout-page-title {
    font-size: 26px;
    font-weight: 800;
    color: #18243a;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}
.checkout-page-subtitle {
    font-size: 13.5px;
    color: #6e656d;
    margin-bottom: 0;
}
.checkout-card,
.checkout-summary-card {
    border: 1px solid #f2dce7 !important;
    border-radius: 18px !important;
}
.checkout-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #fff0f6;
    color: #e11d64;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.checkout-label {
    font-size: 13px;
    color: #374151;
    margin-bottom: 5px;
}
.checkout-input {
    border: 1px solid #ebdbe4;
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 13.5px;
    color: #1f2937;
    transition: all 0.15s ease;
}
.checkout-input:focus {
    border-color: #e11d64;
    box-shadow: 0 0 0 3px rgba(225, 29, 100, 0.1);
}
.checkout-hint {
    font-size: 11px;
    color: #7a7079;
}
.payment-card-option {
    border: 1.5px solid #ebdbe4;
    border-radius: 13px;
    padding: 12px 14px;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.18s ease;
    display: block;
    user-select: none;
}
.payment-card-option:hover {
    border-color: #f472b6;
    background: #fffbfe;
}
.payment-card-option.selected {
    border-color: #e11d64;
    background: #fff5f8;
    box-shadow: 0 2px 8px rgba(225, 29, 100, 0.08);
}
.payment-radio:checked {
    background-color: #e11d64;
    border-color: #e11d64;
}
.payment-title {
    font-size: 13px;
    color: #18243a;
}
.payment-chip {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 6px;
}
.payment-desc {
    font-size: 11px;
    line-height: 1.4;
}
.checkout-item-name {
    font-size: 13px;
}
.checkout-item-qty {
    font-size: 11.5px;
}
.checkout-item-subtotal {
    font-size: 13.5px;
}
.checkout-voucher-box {
    background: #fff8fb;
    border: 1px solid #f2dce7;
}
.checkout-voucher-input {
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
}
.btn-apply-voucher {
    font-size: 11.5px;
    font-weight: 700;
}
.btn-voucher-chip {
    border: 1px dashed #e11d64;
    background: #fff0f6;
    color: #e11d64;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 10.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-voucher-chip:hover {
    background: #e11d64;
    color: #ffffff;
}
.checkout-total-val {
    font-size: 20px;
    color: #e11d64;
}
.btn-submit-checkout {
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
    color: #ffffff !important;
    border: 0 !important;
    border-radius: 12px !important;
    font-size: 14.5px;
    box-shadow: 0 4px 15px rgba(225, 29, 100, 0.28) !important;
    transition: all 0.2s ease;
}
.btn-submit-checkout:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(225, 29, 100, 0.35) !important;
    color: #ffffff !important;
}
.checkout-terms {
    font-size: 11px;
}
@media (max-width: 767.98px) {
    .checkout-page {
        padding-top: 14px !important;
        padding-bottom: 85px !important;
    }
    .checkout-page-title {
        font-size: 18px !important;
        line-height: 1.25 !important;
        margin-bottom: 4px !important;
    }
    .checkout-page-subtitle {
        font-size: 11px !important;
        line-height: 1.4 !important;
        margin-bottom: 0 !important;
    }
    .checkout-header {
        margin-bottom: 14px !important;
    }
    .checkout-card,
    .checkout-summary-card {
        padding: 14px !important;
        border-radius: 14px !important;
        border-color: #f2dce7 !important;
    }
    .checkout-card-title {
        font-size: 14px !important;
    }
    .checkout-total-val {
        font-size: 17px !important;
    }
    .btn-submit-checkout {
        font-size: 13px !important;
        padding: 10px 14px !important;
        border-radius: 10px !important;
    }
    .checkout-card-header {
        margin-bottom: 12px !important;
        padding-bottom: 8px !important;
    }
    .checkout-icon-badge {
        width: 28px !important;
        height: 28px !important;
        font-size: 13px !important;
    }
    .checkout-label {
        font-size: 11.5px !important;
        margin-bottom: 4px !important;
    }
    .checkout-input {
        font-size: 12.5px !important;
        padding: 8px 11px !important;
        border-radius: 9px !important;
    }
    .checkout-hint {
        font-size: 10px !important;
        line-height: 1.35 !important;
    }
    .payment-card-option {
        padding: 10px 11px !important;
        border-radius: 11px !important;
    }
    .payment-title {
        font-size: 12px !important;
    }
    .payment-chip {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
    .payment-desc {
        font-size: 10px !important;
    }
    .checkout-item-name {
        font-size: 12px !important;
    }
    .checkout-item-qty {
        font-size: 10px !important;
    }
    .checkout-item-subtotal {
        font-size: 12px !important;
    }
    .btn-voucher-chip {
        font-size: 9.5px !important;
        padding: 2px 6px !important;
    }
}
@media (max-width: 575.98px) {
    .checkout-page-title {
        font-size: 17.5px !important;
    }
    .checkout-summary-card {
        position: static !important;
        top: auto !important;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="checkout-page py-3 py-md-4">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3 mb-md-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('cart.php'); ?>">Keranjang</a>
            <i class="bi bi-chevron-right"></i>
            <span>Checkout</span>
        </div>

        <div class="checkout-header mb-3 mb-md-4">
            <h1 class="checkout-page-title">Konfirmasi & Checkout Pesanan</h1>
            <p class="checkout-page-subtitle">Isi data pemesan dan alamat pengiriman dengan lengkap untuk memproses pesanan cetak Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show checkout-alert mb-3 mb-md-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo page_url('checkout.php' . ($buyProduct ? '?buy=' . urlencode($buyProduct['id']) . '&qty=' . $buyQty : '')); ?>" method="POST" id="checkoutForm">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
            <?php if ($buyProduct): ?>
                <input type="hidden" name="buy" value="<?php echo htmlspecialchars($buyProduct['id']); ?>">
                <input type="hidden" name="qty" value="<?php echo (int)$buyQty; ?>">
            <?php endif; ?>
            <div class="row g-3 g-md-4">
                <!-- Customer Details & Payment Options (Left Column) -->
                <div class="col-lg-7">
                    <!-- Data Pemesan Card -->
                    <div class="checkout-card bg-white p-3 p-md-4 rounded-4 shadow-sm mb-3 mb-md-4">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-person-lines-fill"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Data Pemesan</h2>
                        </div>

                        <div class="mb-3">
                            <label for="customer_name" class="checkout-label form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control checkout-input" id="customer_name" name="customer_name" required placeholder="Contoh: Budi Santoso" value="<?php echo htmlspecialchars($loggedCustomer['name'] ?? ($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="checkout-label form-label fw-semibold">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control checkout-input" id="customer_phone" name="customer_phone" required placeholder="Contoh: 082331035371" value="<?php echo htmlspecialchars($loggedCustomer['phone'] ?? ($_POST['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-text checkout-hint mt-1">
                                <i class="bi bi-info-circle me-1"></i> Status proses cetak & konfirmasi acc file desain akan dikirim ke WhatsApp ini.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="customer_address" class="checkout-label form-label fw-semibold">Alamat Lengkap Pengiriman</label>
                            <textarea class="form-control checkout-input" id="customer_address" name="customer_address" rows="3" placeholder="Nama Jalan, RT/RW, Dusun/Kelurahan, Kecamatan, Kota/Kabupaten"><?php echo htmlspecialchars($loggedCustomer['address'] ?? ($_POST['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="form-text checkout-hint mt-1">
                                Biarkan kosong bila Anda akan mengambil pesanan langsung di workshop Rosa Indah Printing.
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="customer_note" class="checkout-label form-label fw-semibold">Catatan Pesanan / Spesifikasi Tambahan</label>
                            <textarea class="form-control checkout-input" id="customer_note" name="customer_note" rows="2" placeholder="Contoh: Finishing mata ayam 4 sudut, teks acara diganti tanggal 25 Mei, atau file siap cetak dikirim via WA."><?php echo htmlspecialchars($_POST['customer_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>

                    <!-- Metode Pembayaran Card -->
                    <div class="checkout-card bg-white p-3 p-md-4 rounded-4 shadow-sm">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-wallet2"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Metode Pembayaran</h2>
                        </div>

                        <div class="payment-options-grid d-flex flex-column gap-2">
                            <label class="payment-card-option selected" for="payTransfer">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio mt-1" type="radio" name="payment_method" id="payTransfer" value="transfer" checked>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="payment-title fw-bold">Transfer Bank (BCA / BRI / Mandiri)</span>
                                            <span class="badge bg-light text-dark border payment-chip">Manual Transfer</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">Nomor rekening tujuan transfer ditampilkan di halaman tracking setelah order dibuat.</p>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-card-option" for="payQris">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio mt-1" type="radio" name="payment_method" id="payQris" value="qris">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="payment-title fw-bold">QRIS (Semua E-Wallet & M-Banking)</span>
                                            <span class="badge bg-light text-success border payment-chip">Instan QR</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">Scan instan barcode via GoPay, OVO, DANA, ShopeePay, LinkAja, atau Mobile Banking.</p>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-card-option" for="payCash">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio mt-1" type="radio" name="payment_method" id="payCash" value="cash">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="payment-title fw-bold">Bayar di Workshop (Cash / Tunai)</span>
                                            <span class="badge bg-light text-secondary border payment-chip">Ambil Sendiri</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">Bayar langsung secara tunai saat mengambil hasil cetak di workshop Rosa Indah Printing.</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Review & Summary (Right Column) -->
                <div class="col-lg-5">
                    <div class="checkout-summary-card bg-white p-3 p-md-4 rounded-4 shadow-sm position-sticky" style="top: 85px;">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-receipt"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Rincian Pesanan</h2>
                        </div>

                        <!-- Order Items List -->
                        <div class="checkout-items-list mb-3">
                            <?php foreach ($items as $item): ?>
                                <div class="checkout-item-row d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div class="checkout-item-main pe-2">
                                        <span class="checkout-item-name fw-semibold text-dark d-block"><?php echo htmlspecialchars($item['product']['name']); ?></span>
                                        <span class="checkout-item-qty text-muted small">
                                            <?php echo (int)$item['quantity']; ?> <?php echo htmlspecialchars($item['product']['unit']); ?> × <?php echo format_rupiah($item['unitPrice']); ?>
                                        </span>
                                    </div>
                                    <div class="checkout-item-subtotal fw-bold text-dark tabular-nums text-nowrap">
                                        <?php echo format_rupiah($item['subtotal']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Voucher Code Form Field -->
                        <div class="checkout-voucher-box p-3 rounded-3 mb-3">
                            <label for="voucher_code" class="form-label checkout-label fw-bold small text-secondary mb-1">
                                <i class="bi bi-tag-fill text-primary me-1"></i> Kode Promo / Voucher Member:
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control text-uppercase checkout-voucher-input" id="voucher_code" name="voucher_code" placeholder="Misal: ROSAHEMAT10" value="<?php echo htmlspecialchars($_POST['voucher_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="button" class="btn btn-outline-primary btn-apply-voucher" id="btnApplyVoucher">Terapkan</button>
                            </div>

                            <!-- Quick Voucher Suggestion Chips -->
                            <div class="voucher-chips d-flex align-items-center gap-2 mt-2 flex-wrap">
                                <span class="small text-muted">Promo:</span>
                                <button type="button" class="btn-voucher-chip" data-code="ROSAHEMAT10">ROSAHEMAT10</button>
                                <button type="button" class="btn-voucher-chip" data-code="MEMBERBARU">MEMBERBARU</button>
                            </div>
                        </div>

                        <!-- Summary Breakdown -->
                        <div class="checkout-breakdown mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal Produk</span>
                                <span class="fw-semibold tabular-nums small"><?php echo format_rupiah($total); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Biaya Pengiriman</span>
                                <span class="text-success fw-semibold small">Dikonfirmasi via WA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" id="voucherDiscountRow" style="display: none !important;">
                                <span class="text-success small">Potongan Voucher</span>
                                <span class="text-success fw-bold tabular-nums small" id="voucherDiscountVal">-</span>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="checkout-total-row d-flex justify-content-between align-items-baseline mb-4">
                            <span class="fw-bold">Total Pembayaran</span>
                            <span class="checkout-total-val fw-bold tabular-nums"><?php echo format_rupiah($total); ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-submit-checkout w-100 py-2 py-md-3 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Buat Pesanan Sekarang
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted checkout-terms">
                                <i class="bi bi-lock-fill me-1"></i> Data Anda terlindungi. Invoice resmi akan langsung diterbitkan setelah order dibuat.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight active payment card option
        const paymentCards = document.querySelectorAll('.payment-card-option');
        const paymentRadios = document.querySelectorAll('.payment-radio');

        paymentRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                paymentCards.forEach(card => card.classList.remove('selected'));
                if (this.checked) {
                    const parentCard = this.closest('.payment-card-option');
                    if (parentCard) parentCard.classList.add('selected');
                }
            });
        });

        // Quick Voucher Chips click handler
        const voucherInput = document.getElementById('voucher_code');
        document.querySelectorAll('.btn-voucher-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                if (voucherInput && code) {
                    voucherInput.value = code;
                    voucherInput.focus();
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>