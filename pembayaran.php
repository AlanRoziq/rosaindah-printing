<?php
require_once __DIR__ . '/config/config.php';
if (is_file(__DIR__ . '/config/auth.php')) {
    require_once __DIR__ . '/config/auth.php';
}
if (is_file(__DIR__ . '/includes/order-tracking.php')) {
    require_once __DIR__ . '/includes/order-tracking.php';
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$pageTitle = 'Pembayaran & Upload Bukti Bayar';
$pdo = function_exists('db') ? db() : null;

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

if (!function_exists('order_normalize_phone')) {
    function order_normalize_phone(string $value): string {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') return '';
        if (strpos($digits, '0') === 0) return '62' . substr($digits, 1);
        if (strpos($digits, '8') === 0) return '62' . $digits;
        return $digits;
    }
}

// Daftar Rekening Bank Resmi Rosa Indah Printing
// Catatan: Saat ke depannya sudah memiliki rekening BRI dan BCA, cukup ubah 'active' => true dan isi 'account_number'.
$bankAccounts = [
    [
        'bank_code'      => 'MANDIRI',
        'bank_name'      => 'Bank Mandiri',
        'account_number' => '1440020363831',
        'account_holder' => 'Muhammad Alan Roziqi',
        'active'         => true,
    ],
    [
        'bank_code'      => 'BRI',
        'bank_name'      => 'Bank BRI',
        'account_number' => '', // Isi nomor rekening BRI saat sudah tersedia
        'account_holder' => 'Muhammad Alan Roziqi',
        'active'         => false,
    ],
    [
        'bank_code'      => 'BCA',
        'bank_name'      => 'Bank BCA',
        'account_number' => '', // Isi nomor rekening BCA saat sudah tersedia
        'account_holder' => 'Muhammad Alan Roziqi',
        'active'         => false,
    ],
];
$activeBanks = array_values(array_filter($bankAccounts, function ($b) {
    return !empty($b['active']) && !empty($b['account_number']);
}));

$code = strtoupper(trim((string)($_POST['order_code'] ?? $_GET['code'] ?? '')));
$phone = trim((string)($_POST['customer_phone'] ?? $_GET['phone'] ?? ''));
$isNew = !empty($_GET['new']);
$uploaded = !empty($_GET['uploaded']);
$error = '';
$uploadSuccess = '';

// Pastikan tabel/kolom pembayaran tersedia di database
if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
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
        if (!in_array('payment_proof', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD payment_proof VARCHAR(500) NULL AFTER payment_note");
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            method VARCHAR(30) NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            proof_path VARCHAR(500) NULL,
            proof_name VARCHAR(255) NULL,
            note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'waiting',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_payments_order (order_id)
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {}
}

// Handle POST Upload Bukti Pembayaran
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $postCode = strtoupper(trim((string)($_POST['order_code'] ?? '')));
    $postPhone = trim((string)($_POST['customer_phone'] ?? ''));
    $paymentNote = trim((string)($_POST['payment_note'] ?? ''));
    $selectedMethod = strtolower(trim((string)($_POST['payment_method'] ?? '')));
    $redirectTarget = trim((string)($_POST['redirect_to'] ?? 'lacak'));

    if ($postCode === '') {
        $error = 'Kode pesanan tidak ditemukan.';
    } elseif (empty($_FILES['proof']) || ($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Silakan pilih file foto/screenshot bukti pembayaran terlebih dahulu.';
    } else {
        $tmpName = $_FILES['proof']['tmp_name'];
        $origName = basename((string)$_FILES['proof']['name']);
        $fileSize = (int)($_FILES['proof']['size'] ?? 0);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!in_array($ext, $allowedExts, true)) {
            $error = 'Format bukti pembayaran harus berupa gambar (JPG, PNG, WEBP) atau PDF.';
        } elseif ($fileSize > 8 * 1024 * 1024) {
            $error = 'Ukuran file bukti pembayaran maksimal 8 MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/pembayaran';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $safeCode = preg_replace('/[^A-Za-z0-9\-]/', '', $postCode) ?: 'ORDER';
            $storedFilename = 'bukti-' . strtolower($safeCode) . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
            $destPath = $uploadDir . '/' . $storedFilename;
            $relPath = 'uploads/pembayaran/' . $storedFilename;

            if (@move_uploaded_file($tmpName, $destPath)) {
                // Simpan ke Database apabila tersedia
                if ($pdo) {
                    try {
                        $stmtOrd = $pdo->prepare('SELECT id, payment_status, payment_method, total_estimate FROM orders WHERE order_code = ? LIMIT 1');
                        $stmtOrd->execute([$postCode]);
                        $ordRow = $stmtOrd->fetch(PDO::FETCH_ASSOC);
                        if ($ordRow && strtolower((string)$ordRow['payment_status']) !== 'paid') {
                            $ordId = (int)$ordRow['id'];
                            $methodToSave = in_array($selectedMethod, ['transfer', 'qris', 'cash'], true)
                                ? $selectedMethod
                                : ((string)($ordRow['payment_method'] ?: 'transfer'));
                            $amountToSave = (float)($ordRow['total_estimate'] ?? 0);

                            $insPay = $pdo->prepare('INSERT INTO order_payments (order_id, method, amount, proof_path, proof_name, note, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                            $insPay->execute([
                                $ordId,
                                $methodToSave,
                                $amountToSave,
                                $relPath,
                                $origName,
                                $paymentNote !== '' ? $paymentNote : 'Bukti pembayaran diunggah oleh pelanggan',
                                'waiting',
                            ]);

                            try {
                                $updOrd = $pdo->prepare('UPDATE orders SET payment_status = ?, payment_method = ?, payment_proof = ?, payment_note = ? WHERE id = ?');
                                $updOrd->execute(['waiting', $methodToSave, $relPath, $paymentNote !== '' ? $paymentNote : null, $ordId]);
                            } catch (Throwable $e) {
                                $updOrd2 = $pdo->prepare('UPDATE orders SET payment_status = ?, payment_method = ?, payment_note = ? WHERE id = ?');
                                $updOrd2->execute(['waiting', $methodToSave, $paymentNote !== '' ? $paymentNote : null, $ordId]);
                            }

                            if (function_exists('order_record_payment')) {
                                try {
                                    order_record_payment($pdo, $ordId, 'waiting');
                                } catch (Throwable $e) {}
                            }
                        }
                    } catch (Throwable $e) {}
                }

                // Update sesi fallback jika ada
                if (!empty($_SESSION['session_orders'][$postCode])) {
                    if (strtolower((string)($_SESSION['session_orders'][$postCode]['payment_status'] ?? '')) !== 'paid') {
                        $_SESSION['session_orders'][$postCode]['payment_status'] = 'waiting';
                        $_SESSION['session_orders'][$postCode]['payment_proof'] = $relPath;
                        if (in_array($selectedMethod, ['transfer', 'qris', 'cash'], true)) {
                            $_SESSION['session_orders'][$postCode]['payment_method'] = $selectedMethod;
                        }
                        if ($paymentNote !== '') {
                            $_SESSION['session_orders'][$postCode]['payment_note'] = $paymentNote;
                        }
                        $_SESSION['session_orders'][$postCode]['status_history'][] = [
                            'status'     => 'MENUNGGU VERIFIKASI',
                            'note'       => 'Bukti pembayaran diunggah oleh pelanggan' . ($paymentNote !== '' ? ' (' . $paymentNote . ')' : '') . '. Menunggu konfirmasi Admin.',
                            'created_at' => date('d M Y, H:i'),
                        ];
                    }
                }

                if ($redirectTarget === 'stay') {
                    header('Location: ' . page_url('pembayaran.php?code=' . urlencode($postCode) . '&phone=' . urlencode($postPhone) . '&uploaded=1'));
                } else {
                    header('Location: ' . page_url('lacak-pesanan.php?code=' . urlencode($postCode) . '&phone=' . urlencode($postPhone) . '&uploaded=1'));
                }
                exit;
            } else {
                $error = 'Gagal menyimpan file bukti pembayaran. Pastikan izin folder uploads tersedia.';
            }
        }
    }
}

// Ambil Data Pesanan untuk Ditampilkan di Halaman Pembayaran
$order = null;
if ($code !== '') {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
            $stmt->execute([$code]);
            $dbOrder = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($dbOrder) {
                $stmtItems = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
                $stmtItems->execute([(int)$dbOrder['id']]);
                $dbItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $latestProofPath = (string)($dbOrder['payment_proof'] ?? '');
                $latestProofNote = (string)($dbOrder['payment_note'] ?? '');
                try {
                    $stmtPay = $pdo->prepare('SELECT proof_path, note FROM order_payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
                    $stmtPay->execute([(int)$dbOrder['id']]);
                    $payRow = $stmtPay->fetch(PDO::FETCH_ASSOC);
                    if ($payRow) {
                        if (!empty($payRow['proof_path'])) $latestProofPath = (string)$payRow['proof_path'];
                        if (!empty($payRow['note'])) $latestProofNote = (string)$payRow['note'];
                    }
                } catch (Throwable $e) {}

                $order = [
                    'order_code'      => (string)$dbOrder['order_code'],
                    'customer_name'   => (string)($dbOrder['customer_name'] ?? 'Pelanggan'),
                    'customer_phone'  => (string)($dbOrder['customer_phone'] ?? $phone),
                    'status'          => strtolower((string)($dbOrder['status'] ?? 'pending')),
                    'payment_status'  => strtolower((string)($dbOrder['payment_status'] ?? 'unpaid')),
                    'payment_method'  => strtolower((string)($dbOrder['payment_method'] ?? 'transfer')),
                    'payment_proof'   => $latestProofPath !== '' ? $latestProofPath : null,
                    'payment_note'    => $latestProofNote,
                    'discount_amount' => (float)($dbOrder['discount_amount'] ?? 0),
                    'voucher_code'    => (string)($dbOrder['voucher_code'] ?? ''),
                    'total_estimate'  => (float)($dbOrder['total_estimate'] ?? 0),
                    'created_at'      => !empty($dbOrder['created_at']) ? date('d M Y, H:i', strtotime((string)$dbOrder['created_at'])) : date('d M Y, H:i'),
                    'items'           => array_map(function ($it) {
                        $qty = max(1, (int)($it['quantity'] ?? 1));
                        $sub = (float)($it['subtotal'] ?? 0);
                        $unitPrice = isset($it['unit_price']) && (float)$it['unit_price'] > 0 ? (float)$it['unit_price'] : ($sub / $qty);
                        return [
                            'product_name' => (string)($it['product_name'] ?? 'Produk Cetak'),
                            'quantity'     => $qty,
                            'unit'         => (string)($it['unit'] ?? 'pcs'),
                            'unit_price'   => $unitPrice,
                            'subtotal'     => $sub,
                        ];
                    }, $dbItems),
                ];
            }
        } catch (Throwable $e) {}
    }

    if (!$order && !empty($_SESSION['session_orders'][$code])) {
        $sessOrd = $_SESSION['session_orders'][$code];
        $order = [
            'order_code'      => (string)$sessOrd['order_code'],
            'customer_name'   => (string)($sessOrd['customer_name'] ?? 'Pelanggan'),
            'customer_phone'  => (string)($sessOrd['customer_phone'] ?? $phone),
            'status'          => strtolower((string)($sessOrd['status'] ?? 'pending')),
            'payment_status'  => strtolower((string)($sessOrd['payment_status'] ?? 'unpaid')),
            'payment_method'  => strtolower((string)($sessOrd['payment_method'] ?? 'transfer')),
            'payment_proof'   => $sessOrd['payment_proof'] ?? null,
            'payment_note'    => (string)($sessOrd['payment_note'] ?? ''),
            'discount_amount' => (float)($sessOrd['discount_amount'] ?? 0),
            'voucher_code'    => (string)($sessOrd['voucher_code'] ?? ''),
            'total_estimate'  => (float)($sessOrd['total_estimate'] ?? 0),
            'created_at'      => !empty($sessOrd['created_at']) ? date('d M Y, H:i', strtotime((string)$sessOrd['created_at'])) : date('d M Y, H:i'),
            'items'           => $sessOrd['items'] ?? [],
        ];
    }
}

$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
include __DIR__ . '/includes/header.php';
?>

<style>
.payment-page {
    background: linear-gradient(180deg, #fff5f8 0%, #f8fafc 320px);
    min-height: 75vh;
}
.payment-step-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.payment-step-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
}
.payment-step-pill.done {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #059669;
}
.payment-step-pill.active {
    background: linear-gradient(135deg, #e11d64 0%, #be124f 100%);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(225, 29, 100, 0.25);
}
.payment-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #f1dce5;
    box-shadow: 0 10px 28px rgba(24, 36, 58, 0.05);
}
.method-tab-btn {
    flex: 1;
    min-width: 145px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    border-radius: 14px;
    padding: 12px 14px;
    font-weight: 700;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}
.method-tab-btn:hover {
    border-color: #f43f7e;
    background: #fff5f8;
}
.method-tab-btn.active {
    border-color: #e11d64;
    background: #fff0f6;
    color: #be124f;
    box-shadow: 0 4px 12px rgba(225, 29, 100, 0.12);
}
.mandiri-account-box {
    background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
    border: 1.5px solid #bfdbfe;
    border-radius: 16px;
    padding: 18px;
}
.mandiri-acc-number {
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #0f172a;
    font-family: 'Courier New', Courier, monospace;
}
.qris-frame-box {
    background: #ffffff;
    border: 2px dashed #f43f7e;
    border-radius: 18px;
    padding: 16px;
    text-align: center;
}
.qris-image-preview {
    max-width: 310px;
    width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    cursor: zoom-in;
}
.upload-drop-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    background: #f8fafc;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
}
.upload-drop-zone:hover {
    border-color: #e11d64;
    background: #fff8fb;
}
@media (max-width: 575.98px) {
    .payment-step-pill {
        font-size: 11.5px;
        padding: 6px 12px;
    }
    .mandiri-acc-number {
        font-size: 1.2rem;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="payment-page py-3 py-md-4">
    <div class="container" style="max-width: 960px;">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('checkout.php'); ?>">Checkout</a>
            <i class="bi bi-chevron-right"></i>
            <span>Pembayaran Pesanan</span>
        </div>

        <!-- Stepper Alur Pemesanan -->
        <div class="payment-step-bar">
            <div class="payment-step-pill done">
                <i class="bi bi-check-circle-fill"></i>
                <span>1. Checkout Pesanan</span>
            </div>
            <i class="bi bi-chevron-right text-muted d-none d-sm-inline"></i>
            <div class="payment-step-pill active">
                <i class="bi bi-wallet2"></i>
                <span>2. Pembayaran & Upload Bukti</span>
            </div>
            <i class="bi bi-chevron-right text-muted d-none d-sm-inline"></i>
            <div class="payment-step-pill">
                <i class="bi bi-box-seam"></i>
                <span>3. Data & Lacak Pesanan</span>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($uploaded): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Bukti pembayaran berhasil dikirim!</strong> Admin kami sedang memverifikasi pembayaran Anda.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!$order): ?>
            <div class="payment-card p-4 p-md-5 text-center">
                <div class="mb-3 text-danger" style="font-size: 48px;">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <h1 class="h4 fw-bold mb-2">Data Pesanan Tidak Ditemukan</h1>
                <p class="text-muted mb-4">Masukkan nomor pesanan Anda untuk membuka instruksi pembayaran dan mengunggah bukti transfer/QRIS.</p>
                <form action="<?php echo page_url('pembayaran.php'); ?>" method="GET" class="row g-2 justify-content-center mx-auto" style="max-width: 520px;">
                    <div class="col-sm-7">
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="Contoh: RIP-20260320-1045" value="<?php echo htmlspecialchars($code); ?>" required>
                    </div>
                    <div class="col-sm-5">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-search me-1"></i> Cari Pesanan
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <?php
            $activeMethod = in_array($order['payment_method'], ['transfer', 'qris', 'cash'], true) ? $order['payment_method'] : 'transfer';
            $lacakUrl = page_url('lacak-pesanan.php?code=' . urlencode($order['order_code']) . '&phone=' . urlencode($order['customer_phone']) . ($isNew ? '&new=1' : ''));
            ?>

            <!-- Banner Order Berhasil Dibuat -->
            <div class="payment-card p-3 p-md-4 mb-3 mb-md-4" style="background: linear-gradient(135deg, #fff0f6 0%, #ffffff 100%); border-color: #f9a8d4;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mb-1">
                            <i class="bi bi-clock-history me-1"></i> Langkah Pembayaran Pesanan
                        </span>
                        <h1 class="h4 fw-bold text-dark mb-1">Selesaikan Pembayaran Anda</h1>
                        <p class="text-secondary small mb-0">
                            Silakan lakukan pembayaran sesuai metode di bawah ini, lalu <strong>unggah bukti pembayaran</strong> sebelum melanjutkan ke halaman Data Pesanan.
                        </p>
                    </div>
                    <div class="text-sm-end">
                        <span class="small text-muted d-block">Nomor Pesanan Resmi:</span>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <code class="fs-5 fw-bold text-dark bg-white px-3 py-1 rounded-3 border"><?php echo htmlspecialchars($order['order_code']); ?></code>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-text" data-copy="<?php echo htmlspecialchars($order['order_code']); ?>">
                                <i class="bi bi-clipboard"></i> Salin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-md-4">
                <!-- Kolom Kiri: Instruksi Metode Pembayaran (Bank Mandiri / QRIS) -->
                <div class="col-lg-7">
                    <div class="payment-card p-3 p-md-4 mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-3 border-bottom">
                            <div>
                                <span class="text-muted small d-block">Total Tagihan yang Harus Dibayar:</span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="fs-3 fw-bold text-danger tabular-nums"><?php echo format_rupiah($order['total_estimate']); ?></span>
                                    <button type="button" class="btn btn-sm btn-light border btn-copy-text" data-copy="<?php echo (int)$order['total_estimate']; ?>" title="Salin Nominal">
                                        <i class="bi bi-clipboard me-1"></i> Salin Nominal
                                    </button>
                                </div>
                            </div>
                            <div>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> LUNAS TERVERIFIKASI</span>
                                <?php elseif ($order['payment_status'] === 'waiting' && !empty($order['payment_proof'])): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-hourglass-split me-1"></i> MENUNGGU KONFIRMASI ADMIN</span>
                                <?php else: ?>
                                    <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="bi bi-exclamation-circle me-1"></i> BELUM DIBAYAR</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pilihan Metode Pembayaran Tabs -->
                        <label class="form-label small fw-bold text-secondary mb-2">Pilih / Lihat Instruksi Metode Pembayaran:</label>
                        <div class="d-flex flex-wrap gap-2 mb-4" id="paymentMethodTabs">
                            <button type="button" class="method-tab-btn <?php echo $activeMethod === 'transfer' ? 'active' : ''; ?>" data-target="panel-transfer" data-method="transfer">
                                <i class="bi bi-bank2"></i> Transfer Bank Mandiri
                            </button>
                            <button type="button" class="method-tab-btn <?php echo $activeMethod === 'qris' ? 'active' : ''; ?>" data-target="panel-qris" data-method="qris">
                                <i class="bi bi-qr-code-scan"></i> Scan QRIS Resmi
                            </button>
                            <button type="button" class="method-tab-btn <?php echo $activeMethod === 'cash' ? 'active' : ''; ?>" data-target="panel-cash" data-method="cash">
                                <i class="bi bi-shop"></i> Bayar di Workshop
                            </button>
                        </div>

                        <!-- PANEL 1: TRANSFER BANK MANDIRI -->
                        <div id="panel-transfer" class="payment-method-panel <?php echo $activeMethod === 'transfer' ? '' : 'd-none'; ?>">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h2 class="h6 fw-bold mb-0"><i class="bi bi-bank text-primary me-2"></i>Rekening Resmi Transfer Bank</h2>
                                <span class="badge bg-primary-subtle text-primary border">Verifikasi Cepat</span>
                            </div>
                            <p class="small text-muted mb-3">Silakan transfer tepat sesuai nominal tagihan ke rekening resmi Rosa Indah Printing berikut ini:</p>

                            <?php foreach ($activeBanks as $bank): ?>
                                <div class="mandiri-account-box mb-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning text-dark fw-bold px-2 py-1"><?php echo htmlspecialchars($bank['bank_code']); ?></span>
                                            <strong class="text-dark fs-6"><?php echo htmlspecialchars($bank['bank_name']); ?></strong>
                                        </div>
                                        <span class="small text-secondary">Rekening Resmi</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 bg-white p-3 rounded-3 border my-2">
                                        <div>
                                            <small class="text-muted d-block">Nomor Rekening:</small>
                                            <span class="mandiri-acc-number user-select-all"><?php echo htmlspecialchars($bank['account_number']); ?></span>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 fw-semibold btn-copy-text" data-copy="<?php echo htmlspecialchars($bank['account_number']); ?>">
                                            <i class="bi bi-clipboard me-1"></i> Salin No. Rekening
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small mt-2">
                                        <span class="text-secondary">Atas Nama Penerima:</span>
                                        <strong class="text-dark fs-6">a/n <?php echo htmlspecialchars($bank['account_holder']); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="alert alert-light border small text-secondary mb-0 rounded-3">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                Menerima transfer dari semua bank (Mandiri, BRI, BCA, BNI, BSI, SeaBank, dll) maupun E-Wallet. Simpan struk/screenshot bukti transfer untuk diunggah pada form di samping.
                            </div>
                        </div>

                        <!-- PANEL 2: QRIS STATIS ROSA INDAH PRINTING -->
                        <div id="panel-qris" class="payment-method-panel <?php echo $activeMethod === 'qris' ? '' : 'd-none'; ?>">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h2 class="h6 fw-bold mb-0"><i class="bi bi-qr-code-scan text-danger me-2"></i>Scan QRIS Resmi Rosa Indah Printing</h2>
                                <span class="badge bg-success-subtle text-success border">Semua Aplikasi</span>
                            </div>
                            <p class="small text-muted mb-3">Buka aplikasi GoPay, DANA, OVO, ShopeePay, LinkAja, atau Mobile Banking (Livin' Mandiri, BRImo, BCA Mobile, dll), lalu scan kode QRIS di bawah ini:</p>

                            <div class="qris-frame-box mb-3">
                                <div class="mb-2">
                                    <strong class="d-block text-dark fs-6">ROSA INDAH PRINTING</strong>
                                    <small class="text-muted">NMID : ID1026627457595 • A01</small>
                                </div>
                                <img src="<?php echo asset_url('images/qris-rosa-indah.png'); ?>"
                                     alt="QRIS Resmi Rosa Indah Printing - NMID ID1026627457595"
                                     class="qris-image-preview my-2"
                                     data-bs-toggle="modal"
                                     data-bs-target="#qrisZoomModal">
                                <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#qrisZoomModal">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Perbesar QRIS
                                    </button>
                                    <a href="<?php echo asset_url('images/qris-rosa-indah.png'); ?>" download="QRIS-Rosa-Indah-Printing.png" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-download me-1"></i> Simpan Gambar QRIS
                                    </a>
                                </div>
                            </div>

                            <div class="alert alert-light border small text-secondary mb-0 rounded-3">
                                <i class="bi bi-check2-circle text-success me-1"></i>
                                Masukkan nominal <strong><?php echo format_rupiah($order['total_estimate']); ?></strong> saat melakukan pembayaran QRIS, lalu screenshot bukti berhasilnya dan unggah pada form konfirmasi.
                            </div>
                        </div>

                        <!-- PANEL 3: BAYAR DI WORKSHOP -->
                        <div id="panel-cash" class="payment-method-panel <?php echo $activeMethod === 'cash' ? '' : 'd-none'; ?>">
                            <div class="p-3 rounded-3 bg-light border">
                                <h2 class="h6 fw-bold mb-2"><i class="bi bi-shop text-primary me-2"></i>Pembayaran Tunai di Workshop</h2>
                                <p class="small text-secondary mb-2">
                                    Anda dapat melakukan pembayaran tunai langsung kepada kasir di Workshop <strong>Rosa Indah Printing</strong> (Regek, Sambirejo, Rejoso).
                                </p>
                                <p class="small text-muted mb-0">
                                    Apabila Anda berubah pikiran dan ingin membayar sekarang melalui Transfer Bank Mandiri atau QRIS, silakan pilih tab metode di atas lalu unggah bukti pembayarannya.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Singkat Item Pesanan -->
                    <div class="payment-card p-3 p-md-4">
                        <h3 class="h6 fw-bold mb-3 d-flex align-items-center">
                            <i class="bi bi-bag-check text-primary me-2"></i> Ringkasan Item Pesanan (<?php echo htmlspecialchars($order['customer_name']); ?>)
                        </h3>
                        <div class="mb-2">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                                    <div>
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <span class="text-muted"><?php echo (int)$item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?> × <?php echo format_rupiah($item['unit_price']); ?></span>
                                    </div>
                                    <span class="fw-bold text-dark tabular-nums"><?php echo format_rupiah($item['subtotal']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 fw-bold">
                            <span>Total Tagihan</span>
                            <span class="text-danger fs-5 tabular-nums"><?php echo format_rupiah($order['total_estimate']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Upload Bukti Pembayaran & Lanjut ke Data Pesanan -->
                <div class="col-lg-5">
                    <div class="payment-card p-3 p-md-4 position-sticky" style="top: 85px;">
                        <h2 class="h5 fw-bold mb-2 d-flex align-items-center">
                            <i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i> Upload Bukti Pembayaran
                        </h2>

                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <div class="p-3 rounded-3 bg-success-subtle border border-success text-success mb-3">
                                <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                                    <i class="bi bi-patch-check-fill fs-5"></i>
                                    <span>Pembayaran Telah Dikonfirmasi Lunas</span>
                                </div>
                                <p class="small mb-0">Terima kasih! Pembayaran Anda sudah diverifikasi oleh Admin Rosa Indah Printing dan pesanan sedang diproses.</p>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($order['payment_proof'])): ?>
                                <div class="p-3 rounded-3 bg-warning-subtle border border-warning mb-3">
                                    <div class="d-flex align-items-center gap-2 fw-bold text-dark mb-1">
                                        <i class="bi bi-hourglass-split text-warning-emphasis"></i>
                                        <span>Menunggu Konfirmasi Admin</span>
                                    </div>
                                    <p class="small text-secondary mb-2">
                                        Bukti pembayaran Anda sudah terunggah dan sedang diperiksa oleh Admin. Selama Admin belum mengonfirmasi, Anda masih dapat mengganti/mengunggah ulang bukti di bawah ini.
                                    </p>
                                    <a href="<?php echo page_url($order['payment_proof']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark w-100">
                                        <i class="bi bi-eye me-1"></i> Lihat Bukti Bayar yang Diunggah
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="small text-muted mb-3">
                                    Unggah foto struk ATM, resi transfer, atau screenshot bukti pembayaran Mobile Banking / QRIS Anda agar pesanan segera diverifikasi Admin.
                                </p>
                            <?php endif; ?>

                            <form action="<?php echo page_url('pembayaran.php'); ?>" method="POST" enctype="multipart/form-data" id="uploadProofForm">
                                <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order['order_code']); ?>">
                                <input type="hidden" name="customer_phone" value="<?php echo htmlspecialchars($order['customer_phone']); ?>">
                                <input type="hidden" name="payment_method" id="selectedPaymentMethodInput" value="<?php echo htmlspecialchars($activeMethod); ?>">
                                <input type="hidden" name="redirect_to" value="lacak">

                                <div class="mb-3">
                                    <label for="proofFileInput" class="form-label small fw-bold text-dark">
                                        Foto / Screenshot Bukti Pembayaran <span class="text-danger">*</span>
                                    </label>
                                    <div class="upload-drop-zone" onclick="document.getElementById('proofFileInput').click()" style="cursor: pointer;">
                                        <i class="bi bi-image text-primary fs-2 d-block mb-1"></i>
                                        <span class="small fw-semibold text-dark d-block" id="proofFileNameLabel">Klik untuk pilih foto bukti transfer / QRIS</span>
                                        <small class="text-muted" style="font-size: 11px;">Format: JPG, PNG, WEBP, atau PDF (Maks. 8 MB)</small>
                                        <input type="file" class="form-control d-none" id="proofFileInput" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                    </div>
                                    <div id="proofImagePreviewWrap" class="mt-2 text-center d-none">
                                        <img id="proofImagePreview" src="" alt="Preview Bukti Bayar" class="img-fluid rounded-3 border" style="max-height: 190px;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="payment_note" class="form-label small fw-bold text-dark">Catatan Pembayaran (Opsional)</label>
                                    <input type="text" class="form-control form-control-sm" id="payment_note" name="payment_note" placeholder="Contoh: Transfer Mandiri a/n Budi Santoso" value="<?php echo htmlspecialchars($order['payment_note'] ?? ''); ?>">
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-2">
                                    <i class="bi bi-cloud-check-fill me-1"></i>
                                    <?php echo !empty($order['payment_proof']) ? 'Perbarui Bukti Bayar & Lihat Data Pesanan' : 'Kirim Bukti Bayar & Lanjut ke Data Pesanan'; ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <hr class="my-3">

                        <div class="d-flex flex-column gap-2">
                            <a href="<?php echo htmlspecialchars($lacakUrl); ?>" class="btn btn-outline-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                <?php echo ($order['payment_status'] === 'paid' || !empty($order['payment_proof'])) ? 'Lanjut ke Data & Status Pesanan' : 'Lihat Data Pesanan (Upload Bukti Nanti)'; ?>
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>

                            <a href="https://wa.me/<?php echo htmlspecialchars($waNumber); ?>?text=Halo%20Admin%20Rosa%20Indah%20Printing,%20saya%20ingin%20konfirmasi%20pembayaran%20pesanan%20<?php echo urlencode($order['order_code']); ?>"
                               target="_blank" rel="noopener" class="btn btn-success btn-sm w-100 py-2 fw-semibold">
                                <i class="bi bi-whatsapp me-1"></i> Hubungi Admin via WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Zoom QRIS -->
            <div class="modal fade" id="qrisZoomModal" tabindex="-1" aria-labelledby="qrisZoomModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold" id="qrisZoomModalLabel">QRIS Resmi Rosa Indah Printing</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-4">
                            <img src="<?php echo asset_url('images/qris-rosa-indah.png'); ?>" alt="QRIS Rosa Indah Printing" class="img-fluid rounded-3 border">
                            <div class="mt-3">
                                <strong class="d-block">ROSA INDAH PRINTING</strong>
                                <small class="text-muted">NMID: ID1026627457595 • Total: <?php echo format_rupiah($order['total_estimate']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Method tabs switcher
    const tabBtns = document.querySelectorAll('.method-tab-btn');
    const panels = document.querySelectorAll('.payment-method-panel');
    const methodHiddenInput = document.getElementById('selectedPaymentMethodInput');

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const method = this.getAttribute('data-method');

            tabBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            panels.forEach(function(p) {
                if (p.id === targetId) {
                    p.classList.remove('d-none');
                } else {
                    p.classList.add('d-none');
                }
            });

            if (methodHiddenInput && method) {
                methodHiddenInput.value = method;
            }
        });
    });

    // Copy buttons (No Rekening, Nominal, Kode Order)
    document.querySelectorAll('.btn-copy-text').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            if (text && navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const orig = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
                    setTimeout(() => { this.innerHTML = orig; }, 1800);
                });
            }
        });
    });

    // Live image preview for payment proof
    const fileInput = document.getElementById('proofFileInput');
    const fileNameLabel = document.getElementById('proofFileNameLabel');
    const previewWrap = document.getElementById('proofImagePreviewWrap');
    const previewImg = document.getElementById('proofImagePreview');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (fileNameLabel) {
                    fileNameLabel.textContent = 'File dipilih: ' + file.name;
                }
                if (file.type.startsWith('image/') && previewWrap && previewImg) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewWrap.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                } else if (previewWrap) {
                    previewWrap.classList.add('d-none');
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
