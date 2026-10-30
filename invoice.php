<?php
/**
 * Invoice / Nota Pesanan — Rosa Indah Printing
 * Stage 14
 *
 * Admin dapat membuka nota langsung dari panel pesanan.
 * Pengunjung umum wajib memasukkan nomor WhatsApp pemesan sebagai verifikasi.
 */
require_once __DIR__ . '/config/auth.php';

$pdo = db();
if ($pdo) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('payment_status', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
        if (!in_array('payment_method', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
        if (!in_array('paid_amount', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER payment_method");
        if (!in_array('payment_note', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_note TEXT NULL AFTER paid_amount");
        if (!in_array('paid_at', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_at DATETIME NULL AFTER payment_note");
    } catch (Throwable $e) {}
}
$code = trim((string)($_GET['code'] ?? ''));
$phone = trim((string)($_GET['phone'] ?? ''));
$error = '';
$order = null;
$items = [];

$paymentLabels = ['unpaid'=>'Belum Bayar','waiting'=>'Menunggu Verifikasi','partial'=>'DP / Sebagian','paid'=>'Lunas','rejected'=>'Ditolak'];
$paymentMethods = ['transfer'=>'Transfer Bank','qris'=>'QRIS','ewallet'=>'E-Wallet','cash'=>'Tunai','other'=>'Lainnya'];

$statusLabels = [
    'pending' => 'Baru',
    'confirmed' => 'Dikonfirmasi',
    'processing' => 'Diproses',
    'printed' => 'Dicetak',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
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
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code=? LIMIT 1');
    $stmt->execute([$code]);
    $order = $stmt->fetch();

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
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC');
        $itemStmt->execute([(int)$order['id']]);
        $items = $itemStmt->fetchAll();
    }
}

$publicMode = !admin_logged_in();
$phoneForm = $phone !== '' ? $phone : '';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#d81b60">
<link rel="manifest" href="<?php echo page_url('manifest.json'); ?>">
<link rel="apple-touch-icon" href="<?php echo asset_url('images/logo/apple-touch-icon.png'); ?>">
<title><?php echo $order ? 'Nota ' . invoice_e($order['order_code']) : 'Nota Pesanan'; ?> — Rosa Indah Printing</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{--pink:#d64a8a;--pink-soft:#fff1f7;--ink:#18243a;--muted:#756d75;--line:#eadde4}
*{box-sizing:border-box}
body{margin:0;background:#f8f5f7;color:var(--ink);font-family:Arial,Helvetica,sans-serif}
.invoice-wrap{max-width:900px;margin:32px auto;padding:0 18px}
.invoice-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}
.toolbar-left{display:flex;gap:9px;align-items:center}.toolbar-left a,.print-btn{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--line);background:#fff;color:var(--ink);padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;cursor:pointer}.print-btn{background:var(--pink);border-color:var(--pink);color:#fff}
.invoice-paper{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 18px 50px rgba(35,20,30,.08);padding:42px}
.invoice-head{display:flex;justify-content:space-between;gap:28px;padding-bottom:24px;border-bottom:2px solid var(--ink)}
.brand-block{display:flex;align-items:center;gap:14px}.brand-block img{width:72px;height:72px;object-fit:contain}.brand-block h1{font-size:22px;margin:0;font-weight:900}.brand-block h1 span{color:var(--pink)}.brand-block p{margin:5px 0 0;color:var(--muted);font-size:13px}
.invoice-meta{text-align:right}.invoice-label{font-size:12px;letter-spacing:1.5px;font-weight:800;color:var(--pink)}.invoice-meta h2{margin:5px 0 4px;font-size:24px}.invoice-meta p{margin:0;color:var(--muted);font-size:13px;line-height:1.6}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px;padding:25px 0}.info-card{background:#faf8f9;border:1px solid var(--line);border-radius:12px;padding:15px}.info-card .label{font-size:11px;font-weight:800;letter-spacing:1px;color:#9a9097;margin-bottom:7px}.info-card strong{display:block;font-size:15px;margin-bottom:4px}.info-card p{white-space:pre-line;margin:0;color:var(--muted);font-size:13px;line-height:1.65}
.status{display:inline-flex;padding:6px 10px;border-radius:999px;background:var(--pink-soft);color:var(--pink);font-weight:800;font-size:12px}
.section-title{font-size:14px;font-weight:900;margin:5px 0 12px;text-transform:uppercase;letter-spacing:.7px}
table{width:100%;border-collapse:collapse}.items th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#8a8188;background:#faf8f9;padding:11px;border-bottom:1px solid var(--line)}.items td{padding:13px 11px;border-bottom:1px solid #f0e8ec;font-size:13px;vertical-align:top}.items .num{text-align:right;white-space:nowrap}.item-name{font-weight:800;color:var(--ink)}.item-meta{display:block;margin-top:4px;color:var(--muted);font-size:11px;line-height:1.5}
.total-row{display:flex;justify-content:flex-end;margin-top:18px}.total-box{width:320px;background:var(--pink-soft);border:1px solid #f2c9db;border-radius:13px;padding:16px}.total-box div{display:flex;justify-content:space-between;gap:20px}.total-box span{color:var(--muted);font-size:13px}.total-box strong{font-size:20px;color:var(--pink)}
.payment-box{margin-top:24px;border:1px solid var(--line);border-radius:12px;padding:15px;background:#fff9fc}.payment-box .label{font-size:11px;font-weight:800;letter-spacing:1px;color:#9a9097;margin-bottom:8px}.payment-line{display:flex;justify-content:space-between;align-items:center;gap:12px}.payment-line strong{font-size:18px;color:var(--pink)}.payment-status-paid{background:#e8f8ef;color:#25764a}.payment-status-waiting{background:#fff4df;color:#9a6711}.payment-status-partial{background:#fff0d8;color:#9a5b00}.payment-status-rejected{background:#ffecec;color:#a33b3b}.payment-box p{margin:8px 0 0;color:var(--muted);font-size:12px}.note-box{margin-top:24px;border:1px solid var(--line);border-radius:12px;padding:15px}.note-box .label{font-size:11px;font-weight:800;letter-spacing:1px;color:#9a9097;margin-bottom:8px}.note-box p{white-space:pre-line;margin:0;color:#5f575e;font-size:13px;line-height:1.7}
.footer-note{margin-top:28px;padding-top:18px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:20px;color:var(--muted);font-size:11px;line-height:1.6}.footer-note strong{color:var(--ink)}
.error-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:35px;box-shadow:0 18px 50px rgba(35,20,30,.07)}.error-card h1{margin-top:0}.error-card p{color:var(--muted);line-height:1.6}.verify-form{max-width:420px}.verify-form label{display:block;font-size:13px;font-weight:800;margin:15px 0 7px}.verify-form input{width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font:inherit}.verify-form button{margin-top:14px;width:100%;padding:12px;border:0;border-radius:10px;background:var(--pink);color:#fff;font-weight:800;cursor:pointer}
@media(max-width:700px){.invoice-wrap{margin:12px auto;padding:0 10px}.invoice-paper{padding:22px 16px;border-radius:13px}.invoice-head{flex-direction:column}.invoice-meta{text-align:left}.info-grid{grid-template-columns:1fr;gap:10px}.items th:nth-child(2),.items td:nth-child(2){display:none}.total-row{justify-content:stretch}.total-box{width:100%}.invoice-toolbar{flex-direction:column;align-items:stretch}.toolbar-left{justify-content:space-between}.toolbar-left a,.print-btn{justify-content:center}.footer-note{flex-direction:column}}
@media print{body{background:#fff}.invoice-wrap{max-width:none;margin:0;padding:0}.invoice-toolbar{display:none}.invoice-paper{box-shadow:none;border:0;border-radius:0;padding:12mm}.invoice-head{border-bottom:2px solid #222}.note-box,.info-card,.total-box{break-inside:avoid}.items{break-inside:auto}a{color:inherit;text-decoration:none}@page{size:A4;margin:8mm}}
</style>
</head>
<body>
<div class="invoice-wrap">
<?php if (!$order): ?>
    <div class="invoice-toolbar"><div class="toolbar-left"><a href="<?php echo page_url('index.php'); ?>"><i class="bi bi-house"></i> Website</a></div></div>
    <section class="error-card">
        <h1><?php echo $error ? 'Nota Pesanan' : 'Pesanan'; ?></h1>
        <p><?php echo invoice_e($error ?: 'Pesanan tidak dapat ditampilkan.'); ?></p>
        <?php if ($code !== '' && $publicMode && $error && str_contains($error, 'WhatsApp')): ?>
            <form class="verify-form" method="get">
                <input type="hidden" name="code" value="<?php echo invoice_e($code); ?>">
                <label>Nomor WhatsApp saat checkout</label>
                <input name="phone" type="tel" value="<?php echo invoice_e($phoneForm); ?>" placeholder="08xxxxxxxxxx" required>
                <button type="submit"><i class="bi bi-receipt"></i> Lihat Nota</button>
            </form>
        <?php endif; ?>
    </section>
<?php else: ?>
    <div class="invoice-toolbar">
        <div class="toolbar-left">
            <?php if (admin_logged_in()): ?><a href="<?php echo page_url('admin/pesanan.php'); ?>"><i class="bi bi-arrow-left"></i> Kembali ke Pesanan</a><?php else: ?><a href="<?php echo page_url('index.php'); ?>"><i class="bi bi-house"></i> Website</a><?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end"><a class="print-btn" style="text-decoration:none;background:#fff;color:var(--pink);border-color:var(--pink)" href="<?php echo page_url('lacak-pesanan.php?code='.urlencode($order['order_code']).'&phone='.urlencode($order['customer_phone'])); ?>"><i class="bi bi-geo-alt"></i> Lacak Pesanan</a><a class="print-btn" style="text-decoration:none" href="<?php echo page_url('pembayaran.php?code='.urlencode($order['order_code']).'&phone='.urlencode($order['customer_phone'])); ?>"><i class="bi bi-credit-card"></i> Konfirmasi Pembayaran</a><button class="print-btn" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button></div>
    </div>

    <article class="invoice-paper">
        <header class="invoice-head">
            <div class="brand-block">
                <img src="<?php echo invoice_e(asset_url('images/logo/logori.png')); ?>" alt="Rosa Indah Printing">
                <div><h1>ROSA INDAH <span>PRINTING</span></h1><p>Solusi kebutuhan printing, percetakan & custom.</p></div>
            </div>
            <div class="invoice-meta">
                <div class="invoice-label">NOTA PESANAN</div>
                <h2><?php echo invoice_e($order['order_code']); ?></h2>
                <p><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB<br><span class="status"><?php echo invoice_e($statusLabels[$order['status']] ?? $order['status']); ?></span></p>
            </div>
        </header>

        <section class="info-grid">
            <div class="info-card"><div class="label">PEMESAN</div><strong><?php echo invoice_e($order['customer_name']); ?></strong><p>WhatsApp: <?php echo invoice_e($order['customer_phone']); ?></p></div>
            <div class="info-card"><div class="label">INFORMASI PESANAN</div><p>Nomor pesanan: <strong><?php echo invoice_e($order['order_code']); ?></strong>\nStatus: <?php echo invoice_e($statusLabels[$order['status']] ?? $order['status']); ?>\nPembayaran: <strong><?php echo invoice_e($paymentLabels[$order['payment_status'] ?? 'unpaid'] ?? 'Belum Bayar'); ?></strong>\n<?php if (!empty($order['payment_method']) && isset($paymentMethods[$order['payment_method']])): ?>Metode: <?php echo invoice_e($paymentMethods[$order['payment_method']]); ?><?php endif; ?></p></div>
        </section>

        <section>
            <div class="section-title">Detail Pesanan</div>
            <table class="items">
                <thead><tr><th>Produk</th><th>Qty</th><th class="num">Harga Satuan</th><th class="num">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item):
                    $variant = [];
                    if (!empty($item['variant_data'])) { $decoded = json_decode($item['variant_data'], true); if (is_array($decoded)) $variant = $decoded; }
                    $variantParts = array_filter([
                        !empty($variant['ukuran']) ? 'Ukuran: ' . $variant['ukuran'] : '',
                        !empty($variant['bahan']) ? 'Bahan: ' . $variant['bahan'] : '',
                        !empty($variant['finishing']) ? 'Finishing: ' . $variant['finishing'] : '',
                        !empty($variant['desain']) ? 'Desain: ' . $variant['desain'] : '',
                    ]);
                ?>
                    <tr><td><span class="item-name"><?php echo invoice_e($item['product_name']); ?></span><?php if ($variantParts): ?><span class="item-meta"><?php echo invoice_e(implode(' • ', $variantParts)); ?></span><?php endif; ?></td><td><?php echo (int)$item['quantity']; ?> <?php echo invoice_e($item['unit']); ?></td><td class="num">Rp<?php echo number_format((float)$item['unit_price'],0,',','.'); ?></td><td class="num"><strong>Rp<?php echo number_format((float)$item['subtotal'],0,',','.'); ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-row"><div class="total-box"><div><span>Subtotal</span><strong>Rp<?php echo number_format((float)($order['subtotal_before_discount'] ?? $order['total_estimate']),0,',','.'); ?></strong></div><?php if((float)($order['discount_amount'] ?? 0)>0): ?><div><span><?php echo !empty($order['voucher_code']) ? 'Voucher '.htmlspecialchars($order['voucher_code']) : 'Promo Member'; ?></span><strong style="color:#2f8a58">-Rp<?php echo number_format((float)$order['discount_amount'],0,',','.'); ?></strong></div><?php endif; ?><div><span>Total Estimasi</span><strong>Rp<?php echo number_format((float)$order['total_estimate'],0,',','.'); ?></strong></div></div></div>
        </section>

        <section class="payment-box"><div class="label">STATUS PEMBAYARAN</div><div class="payment-line"><span class="status payment-status-<?php echo invoice_e($order['payment_status'] ?? 'unpaid'); ?>"><?php echo invoice_e($paymentLabels[$order['payment_status'] ?? 'unpaid'] ?? 'Belum Bayar'); ?></span><strong>Rp<?php echo number_format((float)($order['paid_amount'] ?? 0),0,',','.'); ?></strong></div><?php if (!empty($order['payment_note'])): ?><p><?php echo invoice_e($order['payment_note']); ?></p><?php endif; ?></section>

<section class="note-box"><div class="label">CATATAN / ALAMAT / SPESIFIKASI</div><p><?php echo invoice_e($order['customer_note'] ?: '-'); ?></p></section>

        <footer class="footer-note"><div><strong>Terima kasih telah mempercayakan kebutuhan printing Anda kepada Rosa Indah Printing.</strong><br>Nota ini merupakan ringkasan pesanan dan estimasi. Harga final dapat dikonfirmasi Admin sesuai spesifikasi produksi.</div><div style="text-align:right">Rosa Indah Printing<br>Dokumen dibuat otomatis oleh sistem</div></footer>
    </article>
<?php endif; ?>
</div>
</body>
</html>
