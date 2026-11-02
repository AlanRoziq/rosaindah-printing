<?php
/**
 * Invoice / Nota Pesanan Resmi — Rosa Indah Printing
 *
 * Admin dapat membuka & mencetak nota langsung dari panel pesanan atau Nota Manual.
 * Pengunjung umum wajib memasukkan nomor WhatsApp pemesan sebagai verifikasi keamanan.
 */
require_once __DIR__ . '/config/auth.php';

$pdo = db();
if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_address', $cols, true)) $pdo->exec("ALTER TABLE orders ADD customer_address TEXT NULL AFTER customer_phone");
        if (!in_array('payment_status', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
        if (!in_array('payment_method', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
        if (!in_array('paid_amount', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER payment_method");
        if (!in_array('payment_note', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_note TEXT NULL AFTER paid_amount");
        if (!in_array('paid_at', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_at DATETIME NULL AFTER payment_note");
    } catch (Throwable $e) {}
}

$code = trim((string)($_GET['code'] ?? ''));
$phone = trim((string)($_GET['phone'] ?? ''));
$autoPrint = ($_GET['print'] ?? '') === '1';
$error = '';
$order = null;
$items = [];

$paymentLabels = [
    'unpaid' => 'BELUM BAYAR',
    'waiting' => 'MENUNGGU VERIFIKASI / DP',
    'partial' => 'DP / SEBAGIAN',
    'paid' => 'LUNAS',
    'rejected' => 'DITOLAK',
];
$paymentMethods = [
    'transfer' => 'Transfer Bank',
    'qris' => 'QRIS',
    'ewallet' => 'E-Wallet',
    'cash' => 'Tunai / Cash',
    'other' => 'Lainnya',
];
$statusLabels = [
    'pending' => 'BARU',
    'confirmed' => 'DIKONFIRMASI',
    'processing' => 'DIPROSES',
    'printed' => 'DICETAK',
    'completed' => 'SELESAI',
    'cancelled' => 'DIBATALKAN',
];

function invoice_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function invoice_phone_normalize(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
    if (str_starts_with($digits, '8')) return '62' . $digits;
    return $digits;
}

if (!$pdo) {
    $error = 'Database belum terhubung.';
} elseif ($code === '') {
    $error = 'Nomor pesanan belum diberikan.';
} else {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
    $stmt->execute([$code]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $error = 'Pesanan tidak ditemukan.';
    } elseif (!admin_logged_in()) {
        $given = invoice_phone_normalize($phone);
        $saved = invoice_phone_normalize((string)$order['customer_phone']);
        if ($given === '' || $saved === '' || !hash_equals($saved, $given)) {
            $order = null;
            $error = 'Nomor WhatsApp tidak sesuai. Masukkan nomor WhatsApp yang digunakan saat checkout.';
        }
    }

    if ($order) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
        $itemStmt->execute([(int)$order['id']]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$isAdmin = admin_logged_in();
$publicMode = !$isAdmin;
$phoneForm = $phone !== '' ? $phone : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e11d64">
    <link rel="manifest" href="<?php echo page_url('manifest.json'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset_url('images/logo/apple-touch-icon.png'); ?>">
    <title>Nota <?php echo $order ? invoice_e($order['order_code']) : 'Pesanan'; ?> — Rosa Indah Printing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --pink: #e11d64;
            --pink-soft: #fff0f6;
            --ink: #18243a;
            --muted: #6e656d;
            --line: #ebdce5;
            --bg: #f8f5f7;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        .invoice-container {
            max-width: 860px;
            margin: 30px auto;
            padding: 0 16px;
        }
        .invoice-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--ink);
            transition: all 0.15s ease;
        }
        .toolbar-btn:hover {
            background: #fdf5f9;
            border-color: var(--pink);
            color: var(--pink);
        }
        .toolbar-btn.primary {
            background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(225, 29, 100, 0.25);
        }
        .toolbar-btn.primary:hover {
            background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
            transform: translateY(-1px);
            color: #ffffff;
        }
        .toolbar-btn.wa {
            background: #eaf8ef;
            border-color: #c7eed3;
            color: #287a47;
        }
        .toolbar-btn.wa:hover {
            background: #d4f3de;
        }

        /* INVOICE PAPER */
        .invoice-paper {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 15px 45px rgba(214, 74, 138, 0.08);
            padding: 45px;
            position: relative;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--ink);
        }
        .brand-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .brand-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 12px;
        }
        .brand-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }
        .brand-title h1 span {
            color: var(--pink);
        }
        .brand-title p {
            font-size: 12px;
            color: var(--muted);
            margin-top: 3px;
            line-height: 1.4;
        }
        .invoice-badge-block {
            text-align: right;
        }
        .invoice-badge-label {
            display: inline-block;
            background: var(--pink-soft);
            color: var(--pink);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #f2c9db;
        }
        .invoice-code {
            font-size: 22px;
            font-weight: 800;
            color: var(--ink);
            margin: 6px 0 2px;
            letter-spacing: -0.01em;
            font-variant-numeric: tabular-nums;
        }
        .invoice-date {
            font-size: 12px;
            color: var(--muted);
        }

        /* CUSTOMER & ORDER INFO GRID */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 22px 0;
            border-bottom: 1px dashed var(--line);
        }
        .info-card {
            background: #fdfafc;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px 16px;
        }
        .info-card .card-label {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9c919a;
            margin-bottom: 6px;
        }
        .info-card strong {
            display: block;
            font-size: 14px;
            color: var(--ink);
            font-weight: 700;
        }
        .info-card p {
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 3px;
            line-height: 1.5;
        }

        /* TABLE */
        .table-wrap {
            margin-top: 24px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-table th {
            background: #faf4f7;
            border-bottom: 2px solid var(--line);
            padding: 11px 12px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7a7079;
            text-align: left;
        }
        .invoice-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #f2e9ee;
            font-size: 13px;
            vertical-align: middle;
        }
        .invoice-table tr:last-child td {
            border-bottom: 2px solid var(--ink);
        }
        .invoice-table td.num,
        .invoice-table th.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .item-title {
            font-weight: 700;
            color: var(--ink);
            display: block;
        }
        .item-spec {
            font-size: 11px;
            color: #8c828b;
            margin-top: 2px;
            display: block;
        }

        /* SUMMARY ROW */
        .summary-container {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            margin-top: 20px;
            align-items: start;
        }
        .note-box {
            background: #fdfafc;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 12.5px;
        }
        .note-box h4 {
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #8c828b;
            margin-bottom: 6px;
        }
        .note-box p {
            color: #554e54;
            line-height: 1.6;
            white-space: pre-line;
        }
        .calculation-card {
            background: var(--pink-soft);
            border: 1px solid #f2c9db;
            border-radius: 14px;
            padding: 16px 20px;
        }
        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 8px;
            color: #635862;
        }
        .calc-row.total {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px dashed #e5b6cc;
            font-size: 16px;
            font-weight: 800;
            color: var(--pink);
        }
        .calc-row.total strong {
            font-size: 20px;
            font-variant-numeric: tabular-nums;
        }

        /* PAYMENT STATUS BANNER */
        .payment-banner {
            margin-top: 22px;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .payment-banner.paid {
            background: #eaf8ef;
            border-color: #c7eed3;
            color: #25764a;
        }
        .payment-banner.waiting, .payment-banner.partial {
            background: #fff4df;
            border-color: #f7dfaa;
            color: #9a6711;
        }
        .payment-banner.unpaid, .payment-banner.rejected {
            background: #fff0f0;
            border-color: #fbd3d3;
            color: #a33b3b;
        }

        /* SIGNATURE AREA */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 36px;
            padding-top: 20px;
            text-align: center;
        }
        .signature-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 110px;
        }
        .signature-box span {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }
        .signature-line {
            width: 180px;
            border-bottom: 1px solid #18243a;
            margin-top: 50px;
        }
        .signature-box b {
            font-size: 12.5px;
            color: var(--ink);
            margin-top: 6px;
        }

        /* FOOTER ADVICE */
        .invoice-footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #91868f;
        }

        .verify-form {
            max-width: 420px;
            margin: 20px auto 0;
            text-align: left;
        }
        .verify-form label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin: 14px 0 6px;
        }
        .verify-form input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--line);
            border-radius: 11px;
            font: inherit;
        }
        .verify-form button {
            margin-top: 14px;
            width: 100%;
            justify-content: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .invoice-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .invoice-toolbar {
                display: none !important;
            }
            .invoice-paper {
                border: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 10mm 12mm !important;
            }
            .invoice-header {
                border-bottom: 2px solid #000 !important;
            }
            .info-card, .note-box, .calculation-card, .payment-banner {
                break-inside: avoid;
            }
            @page {
                size: A4;
                margin: 8mm;
            }
        }
        @media (max-width: 700px) {
            .invoice-container {
                padding: 0 10px;
                margin: 15px auto;
            }
            .invoice-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .invoice-toolbar > div {
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            .toolbar-btn {
                justify-content: center;
                padding: 9px 14px;
                font-size: 12px;
                width: 100%;
            }
            .invoice-paper {
                padding: 20px 14px;
                border-radius: 14px;
            }
            .invoice-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
                padding-bottom: 16px;
            }
            .invoice-badge-block {
                text-align: left;
            }
            .info-grid,
            .summary-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .signatures {
                gap: 18px;
                margin-top: 24px;
            }
            .signature-line {
                width: 120px;
                margin-top: 35px;
            }
            .invoice-footer {
                flex-direction: column;
                gap: 6px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <?php if (!$order): ?>
        <div class="invoice-toolbar">
            <div>
                <a href="<?php echo page_url($isAdmin ? 'admin/pesanan.php' : 'index.php'); ?>" class="toolbar-btn">
                    <i class="bi bi-arrow-left"></i> <?php echo $isAdmin ? 'Kembali ke Pesanan' : 'Kembali ke Website'; ?>
                </a>
            </div>
        </div>
        <div class="invoice-paper" style="text-align: center; padding: 50px 20px;">
            <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #e11d64;"></i>
            <h2 style="margin: 16px 0 8px;">Nota Pesanan</h2>
            <p style="color: #777; margin-bottom: 20px;"><?php echo invoice_e($error ?: 'Pesanan tidak dapat ditampilkan.'); ?></p>

            <?php if ($code !== '' && $publicMode && $error && str_contains($error, 'WhatsApp')): ?>
                <form class="verify-form" method="get">
                    <input type="hidden" name="code" value="<?php echo invoice_e($code); ?>">
                    <label>Nomor WhatsApp saat checkout</label>
                    <input name="phone" type="tel" value="<?php echo invoice_e($phoneForm); ?>" placeholder="08xxxxxxxxxx" required>
                    <button type="submit" class="toolbar-btn primary"><i class="bi bi-receipt"></i> Lihat Nota</button>
                </form>
            <?php endif; ?>
        </div>
    <?php else:
        $waDigits = invoice_phone_normalize((string)$order['customer_phone']);
        $pStatus = (string)($order['payment_status'] ?? 'unpaid');
        $pMethodCode = (string)($order['payment_method'] ?? 'transfer');
        $pMethodLabel = $paymentMethods[$pMethodCode] ?? strtoupper($pMethodCode ?: 'TRANSFER');
        $subtotalBefore = (float)($order['subtotal_before_discount'] ?? $order['total_estimate']);
        if ($subtotalBefore <= 0) {
            $subtotalBefore = (float)$order['total_estimate'];
        }
        $discountAmt = (float)($order['discount_amount'] ?? 0);
        $totalEst = (float)($order['total_estimate'] ?? 0);
        $paidAmt = (float)($order['paid_amount'] ?? 0);
        $remainingAmt = max(0, $totalEst - $paidAmt);
    ?>
        <!-- Action Toolbar -->
        <div class="invoice-toolbar">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if ($isAdmin): ?>
                    <a href="<?php echo page_url('admin/pesanan.php'); ?>" class="toolbar-btn">
                        <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
                    </a>
                    <a href="<?php echo page_url('admin/nota-manual.php'); ?>" class="toolbar-btn">
                        <i class="bi bi-file-earmark-plus"></i> Buat Nota Manual Baru
                    </a>
                <?php else: ?>
                    <a href="<?php echo page_url('index.php'); ?>" class="toolbar-btn">
                        <i class="bi bi-house"></i> Website
                    </a>
                    <a href="<?php echo page_url('lacak-pesanan.php?code=' . urlencode($order['order_code']) . '&phone=' . urlencode($order['customer_phone'])); ?>" class="toolbar-btn">
                        <i class="bi bi-geo-alt"></i> Lacak Pesanan
                    </a>
                    <a href="<?php echo page_url('pembayaran.php?code=' . urlencode($order['order_code']) . '&phone=' . urlencode($order['customer_phone'])); ?>" class="toolbar-btn">
                        <i class="bi bi-credit-card"></i> Konfirmasi Pembayaran
                    </a>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php if ($waDigits !== ''): ?>
                    <a href="https://wa.me/<?php echo invoice_e($waDigits); ?>" target="_blank" rel="noopener" class="toolbar-btn wa">
                        <i class="bi bi-whatsapp"></i> Chat WhatsApp Pemesan
                    </a>
                <?php endif; ?>
                <button type="button" class="toolbar-btn primary" onclick="window.print()">
                    <i class="bi bi-printer-fill"></i> Cetak Nota / Simpan PDF
                </button>
            </div>
        </div>

        <!-- Printable Invoice Paper -->
        <article class="invoice-paper">
            <!-- Header -->
            <header class="invoice-header">
                <div class="brand-section">
                    <img src="<?php echo invoice_e(asset_url('images/logo/logori.png')); ?>" alt="Logo Rosa Indah Printing" class="brand-logo" onerror="this.style.display='none'">
                    <div class="brand-title">
                        <h1>ROSA INDAH <span>PRINTING</span></h1>
                        <p>
                            Percetakan, Digital Printing & Custom Souvenir<br>
                            Regek, Sambirejo, Rejoso • WhatsApp: 0823-3103-5371
                        </p>
                    </div>
                </div>
                <div class="invoice-badge-block">
                    <div class="invoice-badge-label">BUKTI NOTA TRANSAKSI</div>
                    <div class="invoice-code"><?php echo invoice_e($order['order_code']); ?></div>
                    <div class="invoice-date">
                        <?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB • <b style="color: #e11d64;"><?php echo invoice_e($statusLabels[$order['status']] ?? strtoupper((string)$order['status'])); ?></b>
                    </div>
                </div>
            </header>

            <!-- Customer & Transaction Info -->
            <section class="info-grid">
                <div class="info-card">
                    <div class="card-label">PELANGGAN / PEMESAN</div>
                    <strong><?php echo invoice_e($order['customer_name']); ?></strong>
                    <p>
                        No. WhatsApp: <b><?php echo invoice_e($order['customer_phone']); ?></b>
                        <?php if (!empty($order['customer_address'])): ?>
                            <br>Alamat: <?php echo invoice_e($order['customer_address']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="info-card">
                    <div class="card-label">INFORMASI PEMBAYARAN</div>
                    <p>
                        Metode: <b><?php echo invoice_e($pMethodLabel); ?></b><br>
                        Status Bayar: <b><?php echo invoice_e($paymentLabels[$pStatus] ?? 'BELUM BAYAR'); ?></b><br>
                        Dibayar: <b style="color: #287a47;">Rp<?php echo number_format($paidAmt, 0, ',', '.'); ?></b>
                    </p>
                </div>
            </section>

            <!-- Line Items Table -->
            <div class="table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Deskripsi Item / Spesifikasi Cetak</th>
                            <th style="width: 110px;">Jumlah</th>
                            <th class="num" style="width: 140px;">Harga Satuan</th>
                            <th class="num" style="width: 150px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td>1</td>
                                <td>
                                    <span class="item-title">Jasa Cetak / Produk Custom</span>
                                    <span class="item-spec"><?php echo invoice_e($order['customer_note'] ?: '-'); ?></span>
                                </td>
                                <td>1 pesanan</td>
                                <td class="num">Rp<?php echo number_format($totalEst, 0, ',', '.'); ?></td>
                                <td class="num"><strong>Rp<?php echo number_format($totalEst, 0, ',', '.'); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $idx => $item):
                                $variant = [];
                                if (!empty($item['variant_data'])) {
                                    $decoded = json_decode((string)$item['variant_data'], true);
                                    if (is_array($decoded)) {
                                        $variant = $decoded;
                                    }
                                }
                                $variantParts = array_filter([
                                    !empty($variant['spesifikasi']) ? $variant['spesifikasi'] : '',
                                    !empty($variant['ukuran']) ? $variant['ukuran'] : '',
                                    !empty($variant['bahan']) ? 'Bahan: ' . $variant['bahan'] : '',
                                    !empty($variant['finishing']) ? 'Finishing: ' . $variant['finishing'] : '',
                                    !empty($variant['desain']) ? 'Desain: ' . $variant['desain'] : '',
                                ]);
                            ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td>
                                        <span class="item-title"><?php echo invoice_e($item['product_name']); ?></span>
                                        <?php if (!empty($variantParts)): ?>
                                            <span class="item-spec"><?php echo invoice_e(implode(' • ', $variantParts)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$item['quantity']; ?> <?php echo invoice_e($item['unit'] ?: 'pcs'); ?></td>
                                    <td class="num">Rp<?php echo number_format((float)$item['unit_price'], 0, ',', '.'); ?></td>
                                    <td class="num"><strong>Rp<?php echo number_format((float)$item['subtotal'], 0, ',', '.'); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary & Notes -->
            <div class="summary-container">
                <div class="note-box">
                    <h4>Catatan Khusus / Spesifikasi Order:</h4>
                    <p><?php echo invoice_e(!empty($order['customer_note']) ? $order['customer_note'] : 'Tidak ada catatan tambahan. Dokumen dicetak dan divalidasi resmi oleh sistem Rosa Indah Printing.'); ?></p>
                </div>
                <div class="calculation-card">
                    <div class="calc-row">
                        <span>Subtotal Belanja:</span>
                        <strong>Rp<?php echo number_format($subtotalBefore, 0, ',', '.'); ?></strong>
                    </div>
                    <?php if ($discountAmt > 0): ?>
                        <div class="calc-row" style="color: #287a47;">
                            <span>Potongan Diskon (<?php echo invoice_e(!empty($order['voucher_code']) ? $order['voucher_code'] : 'Promo'); ?>):</span>
                            <strong>-Rp<?php echo number_format($discountAmt, 0, ',', '.'); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div class="calc-row total">
                        <span>Total Tagihan:</span>
                        <strong>Rp<?php echo number_format($totalEst, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="calc-row" style="margin-top: 6px; font-size: 12.5px;">
                        <span>Telah Dibayar:</span>
                        <b style="color: #287a47;">Rp<?php echo number_format($paidAmt, 0, ',', '.'); ?></b>
                    </div>
                    <?php if ($remainingAmt > 0): ?>
                        <div class="calc-row" style="color: #c0392b; font-size: 13px; font-weight: 700;">
                            <span>Sisa Kekurangan:</span>
                            <b>Rp<?php echo number_format($remainingAmt, 0, ',', '.'); ?></b>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Status Banner -->
            <div class="payment-banner <?php echo invoice_e($pStatus); ?>">
                <div>
                    <strong>Status Transaksi: <?php echo invoice_e($paymentLabels[$pStatus] ?? 'BELUM DIBAYAR'); ?></strong>
                    <div style="font-size: 12px; margin-top: 2px;">
                        Metode: <?php echo invoice_e($pMethodLabel); ?> • Total Diterima: Rp<?php echo number_format($paidAmt, 0, ',', '.'); ?>
                    </div>
                </div>
                <div>
                    <span style="font-weight: 800; font-size: 13px;">
                        <?php echo $pStatus === 'paid' ? '✓ LUNAS DITERIMA' : 'MENUNGGU PELUNASAN'; ?>
                    </span>
                </div>
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-box">
                    <span>Tanda Tangan Pelanggan,</span>
                    <div class="signature-line"></div>
                    <b><?php echo invoice_e($order['customer_name']); ?></b>
                </div>
                <div class="signature-box">
                    <span>Hormat Kami (Petugas Kasir),</span>
                    <div class="signature-line"></div>
                    <b>Rosa Indah Printing</b>
                </div>
            </div>

            <!-- Footer Advice -->
            <footer class="invoice-footer">
                <div>
                    Terima kasih telah mempercayakan kebutuhan percetakan Anda kepada Rosa Indah Printing.
                </div>
                <div>
                    Waktu Cetak: <?php echo date('d F Y, H:i'); ?> WIB
                </div>
            </footer>
        </article>
    <?php endif; ?>
</div>

<?php if ($order && $autoPrint): ?>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.print();
        }, 450);
    });
</script>
<?php endif; ?>

</body>
</html>
