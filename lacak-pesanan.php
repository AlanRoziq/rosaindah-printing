<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/order-tracking.php';

$pdo = db();
$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$phone = trim((string)($_GET['phone'] ?? ''));
$error = '';
$order = null;
$history = [];
$notifications = [];
$items = [];

$statusLabels = [
    'pending' => 'Pesanan Baru',
    'confirmed' => 'Dikonfirmasi',
    'processing' => 'Diproses',
    'printed' => 'Dicetak',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];
$statusSteps = ['pending','confirmed','processing','printed','completed'];

if (!$pdo) {
    $error = 'Database belum terhubung.';
} else {
    try { order_tracking_ensure_tables($pdo); } catch (Throwable $e) { $error = 'Sistem tracking belum siap. Silakan coba lagi.'; }

    if ($error === '' && $code !== '') {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code=? LIMIT 1');
        $stmt->execute([$code]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = 'Nomor pesanan tidak ditemukan.';
        } else {
            $given = order_normalize_phone($phone);
            $saved = order_normalize_phone((string)$order['customer_phone']);
            if ($given === '' || $saved === '' || !hash_equals($saved, $given)) {
                $order = null;
                $error = 'Nomor WhatsApp tidak sesuai dengan data pesanan.';
            }
        }

        if ($order) {
            $s = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC, id ASC');
            $s->execute([(int)$order['id']]);
            $history = $s->fetchAll();
            $n = $pdo->prepare('SELECT * FROM order_notifications WHERE order_id=? ORDER BY created_at DESC, id DESC LIMIT 20');
            $n->execute([(int)$order['id']]);
            $notifications = $n->fetchAll();
            $i = $pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC');
            $i->execute([(int)$order['id']]);
            $items = $i->fetchAll();
        }
    }
}

$currentStatus = $order['status'] ?? '';
$currentIndex = array_search($currentStatus, $statusSteps, true);
if ($currentIndex === false) $currentIndex = -1;
$phoneForm = $phone !== '' ? $phone : '';
function tracking_e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tracking_date($value): string { return date('d M Y, H:i', strtotime((string)$value)) . ' WIB'; }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lacak Pesanan — Rosa Indah Printing</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{--pink:#d64a8a;--pink2:#f6d7e5;--soft:#fff5fa;--ink:#18243a;--muted:#756d75;--line:#eadde4;--green:#27865b;--red:#b13d48}
*{box-sizing:border-box}body{margin:0;background:#faf7f9;color:var(--ink);font-family:Arial,Helvetica,sans-serif}.track-wrap{max-width:1000px;margin:0 auto;padding:24px 18px 60px}.top{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px}.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--ink);font-weight:900}.brand img{width:48px;height:48px;object-fit:contain}.brand span{color:var(--pink)}.home-link{display:inline-flex;align-items:center;gap:7px;text-decoration:none;color:var(--pink);font-weight:800}.hero{text-align:center;padding:28px 10px 24px}.eyebrow{display:inline-block;background:var(--pink2);color:var(--pink);padding:8px 14px;border-radius:999px;font-size:12px;font-weight:900;letter-spacing:.8px}.hero h1{font-size:38px;margin:14px 0 8px}.hero h1 span{color:var(--pink)}.hero p{margin:0;color:var(--muted);line-height:1.7}.search-card,.card{background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 15px 45px rgba(30,20,30,.06)}.search-card{max-width:720px;margin:0 auto 25px;padding:20px}.search-grid{display:grid;grid-template-columns:1fr 1fr auto;gap:10px}.search-grid label{font-size:12px;font-weight:800;color:#625961}.search-grid input{width:100%;margin-top:6px;padding:12px;border:1px solid var(--line);border-radius:10px;font:inherit}.search-btn{align-self:end;border:0;background:var(--pink);color:#fff;border-radius:10px;padding:13px 18px;font-weight:900;cursor:pointer}.error{max-width:720px;margin:0 auto 20px;background:#fff0f0;color:#a33b3b;border:1px solid #f2caca;border-radius:12px;padding:13px}.order-head{display:flex;justify-content:space-between;gap:20px;align-items:center;padding:22px}.order-head h2{margin:3px 0;font-size:24px}.order-head p{margin:0;color:var(--muted);font-size:13px}.status-pill{display:inline-flex;padding:8px 12px;border-radius:999px;background:var(--soft);color:var(--pink);font-weight:900;font-size:12px}.status-pill.cancelled{background:#fff0f0;color:var(--red)}.progress{padding:25px 22px}.steps{display:grid;grid-template-columns:repeat(5,1fr);position:relative;gap:8px}.steps:before{content:"";position:absolute;left:10%;right:10%;top:17px;height:3px;background:#eee3e8}.step{text-align:center;position:relative;z-index:1}.dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;background:#f0eaed;color:#9b9298;border:4px solid #fff;box-shadow:0 0 0 1px #eee3e8;font-weight:900}.step.done .dot,.step.current .dot{background:var(--pink);color:#fff;box-shadow:0 0 0 1px var(--pink)}.step span{display:block;font-size:11px;color:#8b8288;font-weight:800}.step.done span,.step.current span{color:var(--pink)}.cancel-banner{margin:0 22px 22px;background:#fff0f0;color:var(--red);border:1px solid #f2caca;border-radius:12px;padding:13px;font-size:13px}.columns{display:grid;grid-template-columns:1.25fr .75fr;gap:18px;margin-top:18px}.card{padding:22px}.card h3{margin:0 0 15px;font-size:16px}.items{display:grid;gap:10px}.item{display:flex;justify-content:space-between;gap:15px;padding:12px;background:#faf8f9;border:1px solid #f0e8ec;border-radius:11px}.item strong{display:block}.item small{color:var(--muted)}.timeline{display:grid;gap:0}.timeline-item{display:grid;grid-template-columns:32px 1fr;gap:12px;position:relative;padding-bottom:19px}.timeline-item:not(:last-child):before{content:"";position:absolute;left:15px;top:30px;bottom:0;width:2px;background:#eee3e8}.timeline-icon{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--soft);color:var(--pink);position:relative;z-index:1}.timeline-item h4{margin:1px 0 4px;font-size:13px}.timeline-item p{margin:0;color:var(--muted);font-size:12px;line-height:1.55}.timeline-item time{display:block;margin-top:5px;color:#9a9097;font-size:10px}.notification{padding:13px;border:1px solid #f0e8ec;border-radius:11px;background:#fff;margin-bottom:9px}.notification:last-child{margin-bottom:0}.notification strong{display:block;font-size:13px}.notification p{margin:4px 0 0;color:var(--muted);font-size:12px;line-height:1.55}.notification time{display:block;margin-top:5px;color:#9a9097;font-size:10px}.actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}.action{display:inline-flex;align-items:center;gap:7px;padding:11px 14px;border-radius:10px;text-decoration:none;font-weight:800;font-size:12px;border:1px solid var(--line);color:var(--ink);background:#fff}.action.primary{background:var(--pink);color:#fff;border-color:var(--pink)}.empty{color:var(--muted);font-size:13px}.footer{text-align:center;color:#9a9097;font-size:11px;margin-top:25px}@media(max-width:760px){.track-wrap{padding:15px 10px 40px}.top{align-items:flex-start}.hero h1{font-size:30px}.search-grid{grid-template-columns:1fr}.search-btn{width:100%}.order-head{align-items:flex-start;flex-direction:column}.steps{gap:2px}.steps:before{left:8%;right:8%}.step span{font-size:9px}.columns{grid-template-columns:1fr}.card{padding:17px}.progress{padding:20px 12px}.cancel-banner{margin:0 12px 18px}}
</style>
</head>
<body>
<div class="track-wrap">
    <div class="top"><a class="brand" href="<?php echo page_url('index.php'); ?>"><img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing"><span>ROSA INDAH PRINTING</span></a><a class="home-link" href="<?php echo page_url('index.php'); ?>"><i class="bi bi-house"></i> Website</a></div>
    <section class="hero"><span class="eyebrow">TRACKING PESANAN</span><h1>Lacak <span>Pesanan Anda</span></h1><p>Masukkan nomor pesanan dan nomor WhatsApp yang digunakan saat checkout untuk melihat status, notifikasi, dan perkembangan pesanan.</p></section>

    <form class="search-card" method="get">
        <div class="search-grid">
            <label>Nomor Pesanan<input name="code" value="<?php echo tracking_e($code); ?>" placeholder="Contoh: RIP-20260820-ABC1" required></label>
            <label>Nomor WhatsApp<input name="phone" value="<?php echo tracking_e($phoneForm); ?>" placeholder="08xxxxxxxxxx" required></label>
            <button class="search-btn" type="submit"><i class="bi bi-search"></i> Lacak</button>
        </div>
    </form>

    <?php if ($error): ?><div class="error"><i class="bi bi-exclamation-circle"></i> <?php echo tracking_e($error); ?></div><?php endif; ?>

    <?php if ($order): ?>
        <section class="card">
            <div class="order-head"><div><p>NOMOR PESANAN</p><h2><?php echo tracking_e($order['order_code']); ?></h2><p><?php echo tracking_e($order['customer_name']); ?> · <?php echo tracking_e($order['customer_phone']); ?></p></div><span class="status-pill <?php echo $currentStatus==='cancelled'?'cancelled':''; ?>"><?php echo tracking_e($statusLabels[$currentStatus] ?? $currentStatus); ?></span></div>
            <?php if ($currentStatus === 'cancelled'): ?><div class="cancel-banner"><strong>Pesanan dibatalkan.</strong> Silakan hubungi Admin Rosa Indah Printing jika Anda membutuhkan penjelasan lebih lanjut.</div><?php endif; ?>
            <?php if ($currentStatus !== 'cancelled'): ?>
            <div class="progress"><div class="steps">
                <?php foreach($statusSteps as $idx=>$step): $done=$idx < $currentIndex; $current=$idx===$currentIndex; ?>
                    <div class="step <?php echo $done?'done ':''; echo $current?'current':''; ?>"><div class="dot"><?php echo $done?'✓':($idx+1); ?></div><span><?php echo tracking_e($statusLabels[$step]); ?></span></div>
                <?php endforeach; ?>
            </div></div>
            <?php endif; ?>
        </section>

        <div class="columns">
            <section class="card"><h3><i class="bi bi-bell"></i> Notifikasi Terbaru</h3>
                <?php if ($notifications): foreach($notifications as $n): ?>
                    <div class="notification"><strong><?php echo tracking_e($n['title']); ?></strong><p><?php echo tracking_e($n['message']); ?></p><time><?php echo tracking_date($n['created_at']); ?></time></div>
                <?php endforeach; else: ?><p class="empty">Belum ada notifikasi tambahan.</p><?php endif; ?>
            </section>
            <section class="card"><h3><i class="bi bi-receipt"></i> Ringkasan Pesanan</h3>
                <div class="items">
                    <?php foreach($items as $item): ?><div class="item"><div><strong><?php echo tracking_e($item['product_name']); ?></strong><small><?php echo (int)$item['quantity']; ?> <?php echo tracking_e($item['unit']); ?></small></div><strong>Rp<?php echo number_format((float)$item['subtotal'],0,',','.'); ?></strong></div><?php endforeach; ?>
                </div>
                <div style="display:flex;justify-content:space-between;gap:10px;margin-top:15px;padding-top:14px;border-top:1px solid var(--line)"><span>Estimasi Total</span><strong style="color:var(--pink)">Rp<?php echo number_format((float)$order['total_estimate'],0,',','.'); ?></strong></div>
                <div class="actions"><a class="action primary" href="<?php echo page_url('invoice.php?code='.urlencode($order['order_code']).'&phone='.urlencode($order['customer_phone'])); ?>"><i class="bi bi-receipt"></i> Lihat Nota</a><a class="action" href="<?php echo page_url('pembayaran.php?code='.urlencode($order['order_code']).'&phone='.urlencode($order['customer_phone'])); ?>"><i class="bi bi-credit-card"></i> Pembayaran</a></div>
            </section>
        </div>

        <section class="card" style="margin-top:18px"><h3><i class="bi bi-clock-history"></i> Riwayat Status Pesanan</h3>
            <?php if ($history): ?><div class="timeline"><?php foreach($history as $h): ?><div class="timeline-item"><div class="timeline-icon"><i class="bi bi-check2"></i></div><div><h4><?php echo tracking_e($h['title']); ?></h4><p><?php echo tracking_e($h['message']); ?></p><time><?php echo tracking_date($h['created_at']); ?></time></div></div><?php endforeach; ?></div><?php else: ?><p class="empty">Riwayat status belum tersedia.</p><?php endif; ?>
        </section>
    <?php endif; ?>
    <div class="footer">Rosa Indah Printing · Sistem tracking pesanan otomatis</div>
</div>
</body>
</html>
