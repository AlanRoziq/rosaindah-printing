<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$pdo = db();
$pageTitle = 'Laporan';
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$allowedStatuses = ['pending','confirmed','processing','printed','completed','cancelled'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = date('Y-m-d');
if ($start > $end) { $tmp=$start; $start=$end; $end=$tmp; }
if (!in_array($status, $allowedStatuses, true)) $status='';

$where = 'WHERE o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
$params = [$start, $end];
if ($status !== '') { $where .= ' AND o.status = ?'; $params[] = $status; }

$metrics = ['orders'=>0,'gross'=>0,'paid'=>0,'outstanding'=>0,'completed'=>0,'avg'=>0,'customers'=>0];
$daily=[]; $topProducts=[]; $statusRows=[]; $paymentRows=[]; $recent=[];
if ($pdo) {
    $stmt=$pdo->prepare("SELECT COUNT(*) orders, COALESCE(SUM(total_estimate),0) gross, COALESCE(SUM(paid_amount),0) paid, COALESCE(SUM(CASE WHEN total_estimate>paid_amount THEN total_estimate-paid_amount ELSE 0 END),0) outstanding, COALESCE(SUM(CASE WHEN status='completed' THEN total_estimate ELSE 0 END),0) completed, COALESCE(AVG(total_estimate),0) avg, COUNT(DISTINCT customer_phone) customers FROM orders o $where");
    $stmt->execute($params); $metrics=array_merge($metrics,$stmt->fetch() ?: []);

    $stmt=$pdo->prepare("SELECT DATE(o.created_at) day, COUNT(*) orders, COALESCE(SUM(o.total_estimate),0) gross, COALESCE(SUM(o.paid_amount),0) paid FROM orders o $where GROUP BY DATE(o.created_at) ORDER BY day ASC");
    $stmt->execute($params); $daily=$stmt->fetchAll();

    $stmt=$pdo->prepare("SELECT oi.product_name, SUM(oi.quantity) qty, COALESCE(SUM(oi.subtotal),0) revenue FROM order_items oi JOIN orders o ON o.id=oi.order_id $where GROUP BY oi.product_id, oi.product_name ORDER BY qty DESC, revenue DESC LIMIT 8");
    $stmt->execute($params); $topProducts=$stmt->fetchAll();

    $stmt=$pdo->prepare("SELECT o.status, COUNT(*) total, COALESCE(SUM(o.total_estimate),0) value FROM orders o $where GROUP BY o.status ORDER BY total DESC");
    $stmt->execute($params); $statusRows=$stmt->fetchAll();

    $stmt=$pdo->prepare("SELECT COALESCE(o.payment_status,'unpaid') payment_status, COUNT(*) total, COALESCE(SUM(o.paid_amount),0) paid FROM orders o $where GROUP BY COALESCE(o.payment_status,'unpaid') ORDER BY total DESC");
    $stmt->execute($params); $paymentRows=$stmt->fetchAll();

    $stmt=$pdo->prepare("SELECT order_code, customer_name, total_estimate, paid_amount, status, payment_status, created_at FROM orders o $where ORDER BY created_at DESC LIMIT 10");
    $stmt->execute($params); $recent=$stmt->fetchAll();
}

if (isset($_GET['export']) && $_GET['export']==='csv') {
    if (!$pdo) { http_response_code(500); exit('Database tidak tersedia.'); }
    $stmt=$pdo->prepare("SELECT o.order_code, o.created_at, o.customer_name, o.customer_phone, o.total_estimate, o.paid_amount, o.payment_status, o.status FROM orders o $where ORDER BY o.created_at DESC");
    $stmt->execute($params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-rosa-indah-' . $start . '-sampai-' . $end . '.csv"');
    $out=fopen('php://output','w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out,['No. Pesanan','Tanggal','Pelanggan','WhatsApp','Total Order','Sudah Dibayar','Status Pembayaran','Status Pesanan']);
    while($r=$stmt->fetch()) fputcsv($out,[$r['order_code'],$r['created_at'],$r['customer_name'],$r['customer_phone'],$r['total_estimate'],$r['paid_amount'],$r['payment_status'],$r['status']]);
    fclose($out); exit;
}

$statusLabels=['pending'=>'Baru','confirmed'=>'Dikonfirmasi','processing'=>'Diproses','printed'=>'Dicetak','completed'=>'Selesai','cancelled'=>'Dibatalkan'];
$paymentLabels=['unpaid'=>'Belum Bayar','waiting'=>'Menunggu Verifikasi','partial'=>'DP / Sebagian','paid'=>'Lunas','rejected'=>'Ditolak'];
$maxDaily=1; foreach($daily as $d) $maxDaily=max($maxDaily,(float)$d['gross']);
$maxQty=1; foreach($topProducts as $p) $maxQty=max($maxQty,(float)$p['qty']);
require __DIR__ . '/_header.php';
?>
<div class="admin-title">
  <div><h1>Laporan & Statistik</h1><p style="color:#7d747c">Pantau omzet, pesanan, pembayaran, dan produk terlaris Rosa Indah Printing.</p></div>
  <div class="report-actions"><a class="admin-btn secondary" href="<?php echo page_url('admin/laporan.php?start='.urlencode($start).'&end='.urlencode($end).'&status='.urlencode($status).'&export=csv'); ?>"><i class="bi bi-filetype-csv"></i> Export CSV</a><button class="admin-btn" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Laporan</button></div>
</div>
<div class="admin-card report-filter-card">
<form class="report-filter" method="get">
 <div class="admin-field"><label>Dari tanggal</label><input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>"></div>
 <div class="admin-field"><label>Sampai tanggal</label><input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>"></div>
 <div class="admin-field"><label>Status pesanan</label><select name="status"><option value="">Semua status</option><?php foreach($allowedStatuses as $s): ?><option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo htmlspecialchars($statusLabels[$s]); ?></option><?php endforeach; ?></select></div>
 <button class="admin-btn report-filter-btn" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
 <a class="admin-btn secondary report-filter-btn" href="<?php echo page_url('admin/laporan.php'); ?>">Bulan Ini</a>
</form>
<div class="report-period"><i class="bi bi-calendar3"></i> Periode <strong><?php echo date('d/m/Y',strtotime($start)); ?></strong> s/d <strong><?php echo date('d/m/Y',strtotime($end)); ?></strong><?php if($status): ?> · Status <strong><?php echo htmlspecialchars($statusLabels[$status]); ?></strong><?php endif; ?></div>
</div>
<div class="admin-stats report-stats">
 <div class="admin-stat"><i class="bi bi-cart-check"></i><strong><?php echo number_format((int)$metrics['orders'],0,',','.'); ?></strong><span>Total pesanan</span></div>
 <div class="admin-stat"><i class="bi bi-cash-stack"></i><strong>Rp<?php echo number_format((float)$metrics['gross'],0,',','.'); ?></strong><span>Nilai pesanan</span></div>
 <div class="admin-stat"><i class="bi bi-wallet2"></i><strong>Rp<?php echo number_format((float)$metrics['paid'],0,',','.'); ?></strong><span>Sudah dibayar</span></div>
 <div class="admin-stat"><i class="bi bi-hourglass-split"></i><strong>Rp<?php echo number_format((float)$metrics['outstanding'],0,',','.'); ?></strong><span>Sisa pembayaran</span></div>
 <div class="admin-stat"><i class="bi bi-check-circle"></i><strong>Rp<?php echo number_format((float)$metrics['completed'],0,',','.'); ?></strong><span>Nilai pesanan selesai</span></div>
 <div class="admin-stat"><i class="bi bi-graph-up-arrow"></i><strong>Rp<?php echo number_format((float)$metrics['avg'],0,',','.'); ?></strong><span>Rata-rata per order</span></div>
</div>
<div class="report-grid">
 <section class="admin-card"><div class="report-card-head"><div><h2>Tren Penjualan</h2><p>Nilai order per hari dalam periode terpilih.</p></div></div>
 <?php if($daily): ?><div class="report-bars"><?php foreach($daily as $d): $pct=max(4,min(100,((float)$d['gross']/$maxDaily)*100)); ?><div class="report-bar-item"><div class="report-bar-value">Rp<?php echo number_format($d['gross'],0,',','.'); ?></div><div class="report-bar-track"><div class="report-bar-fill" style="height:<?php echo $pct; ?>%"></div></div><span><?php echo date('d/m',strtotime($d['day'])); ?></span></div><?php endforeach; ?></div><?php else: ?><div class="report-empty">Belum ada data pada periode ini.</div><?php endif; ?></section>
 <section class="admin-card"><div class="report-card-head"><div><h2>Status Pesanan</h2><p>Distribusi jumlah dan nilai order.</p></div></div><div class="report-list"><?php foreach($statusRows as $r): ?><div class="report-row"><span class="report-status-dot status-<?php echo htmlspecialchars($r['status']); ?>"></span><div><strong><?php echo htmlspecialchars($statusLabels[$r['status']] ?? $r['status']); ?></strong><small><?php echo (int)$r['total']; ?> pesanan</small></div><b>Rp<?php echo number_format($r['value'],0,',','.'); ?></b></div><?php endforeach; ?><?php if(!$statusRows): ?><div class="report-empty">Belum ada data.</div><?php endif; ?></div></section>
</div>
<div class="report-grid">
 <section class="admin-card"><div class="report-card-head"><div><h2>Produk Terlaris</h2><p>Diurutkan berdasarkan jumlah item terjual.</p></div></div><div class="report-products"><?php foreach($topProducts as $p): $pct=max(5,((float)$p['qty']/$maxQty)*100); ?><div class="report-product"><div class="report-product-title"><strong><?php echo htmlspecialchars($p['product_name']); ?></strong><span><?php echo (int)$p['qty']; ?> item · Rp<?php echo number_format($p['revenue'],0,',','.'); ?></span></div><div class="report-progress"><span style="width:<?php echo min(100,$pct); ?>%"></span></div></div><?php endforeach; ?><?php if(!$topProducts): ?><div class="report-empty">Belum ada produk terjual.</div><?php endif; ?></div></section>
 <section class="admin-card"><div class="report-card-head"><div><h2>Status Pembayaran</h2><p>Jumlah order dan pembayaran yang masuk.</p></div></div><div class="report-list"><?php foreach($paymentRows as $r): ?><div class="report-row"><span class="payment-pill payment-<?php echo htmlspecialchars($r['payment_status']); ?>"><?php echo htmlspecialchars($paymentLabels[$r['payment_status']] ?? $r['payment_status']); ?></span><div><small><?php echo (int)$r['total']; ?> pesanan</small></div><b>Rp<?php echo number_format($r['paid'],0,',','.'); ?></b></div><?php endforeach; ?><?php if(!$paymentRows): ?><div class="report-empty">Belum ada data.</div><?php endif; ?></div></section>
</div>
<section class="admin-card"><div class="report-card-head"><div><h2>Pesanan Terbaru dalam Periode</h2><p>10 pesanan terbaru yang sesuai filter laporan.</p></div><span class="report-customer-count"><i class="bi bi-people"></i> <?php echo (int)$metrics['customers']; ?> pelanggan</span></div>
<div class="admin-order-table-wrap"><table class="admin-table"><thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Dibayar</th><th>Status</th><th>Tanggal</th><th></th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td><strong><?php echo htmlspecialchars($r['order_code']); ?></strong></td><td><?php echo htmlspecialchars($r['customer_name']); ?></td><td>Rp<?php echo number_format($r['total_estimate'],0,',','.'); ?></td><td>Rp<?php echo number_format($r['paid_amount'],0,',','.'); ?></td><td><span class="status-pill status-pill-<?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars($statusLabels[$r['status']] ?? $r['status']); ?></span></td><td><?php echo date('d/m/Y H:i',strtotime($r['created_at'])); ?></td><td><a class="admin-small-btn" target="_blank" rel="noopener" href="<?php echo page_url('invoice.php?code='.urlencode($r['order_code'])); ?>">Nota</a></td></tr><?php endforeach; ?><?php if(!$recent): ?><tr><td colspan="7"><div class="report-empty">Belum ada pesanan.</div></td></tr><?php endif; ?></tbody></table></div></section>
<style>
.report-actions{display:flex;gap:9px;flex-wrap:wrap}.report-filter-card{margin-bottom:22px}.report-filter{display:grid;grid-template-columns:1fr 1fr 1.2fr auto auto;gap:12px;align-items:end}.report-filter-btn{height:43px;box-sizing:border-box;white-space:nowrap}.report-period{margin-top:14px;padding-top:13px;border-top:1px solid #f2e7ec;color:#82777f;font-size:13px}.report-stats{grid-template-columns:repeat(3,1fr)}.report-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:18px;margin-bottom:18px}.report-card-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:18px}.report-card-head h2{margin:0;color:#18243a;font-size:19px}.report-card-head p{margin:5px 0 0;color:#817982;font-size:13px}.report-bars{height:250px;display:flex;align-items:stretch;gap:8px;overflow-x:auto;padding:10px 4px 0}.report-bar-item{min-width:42px;flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:7px}.report-bar-value{font-size:9px;color:#8a7d85;white-space:nowrap;transform:rotate(-35deg);height:22px}.report-bar-track{height:170px;width:100%;max-width:42px;background:#fff0f6;border-radius:9px 9px 4px 4px;display:flex;align-items:flex-end;overflow:hidden}.report-bar-fill{width:100%;background:linear-gradient(180deg,#d64a8a,#ef9abd);border-radius:8px 8px 3px 3px;min-height:5px}.report-bar-item>span{font-size:10px;color:#8b7f87}.report-list{display:grid;gap:4px}.report-row{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;padding:11px 0;border-bottom:1px solid #f5eaee}.report-row:last-child{border-bottom:0}.report-row strong{display:block;color:#3e3940;font-size:13px}.report-row small{display:block;color:#8c8188;font-size:11px;margin-top:3px}.report-row b{font-size:12px;color:#4c4650}.report-status-dot{width:10px;height:10px;border-radius:50%;background:#d8cfd4}.report-status-dot.status-pending{background:#e5a940}.report-status-dot.status-confirmed{background:#4b8fd8}.report-status-dot.status-processing{background:#8e68c9}.report-status-dot.status-printed{background:#e67b39}.report-status-dot.status-completed{background:#43a66b}.report-status-dot.status-cancelled{background:#d85c5c}.report-products{display:grid;gap:15px}.report-product-title{display:flex;justify-content:space-between;gap:10px;font-size:13px}.report-product-title strong{color:#3e3940}.report-product-title span{color:#8b7f87;font-size:11px}.report-progress{height:7px;background:#f6e9ee;border-radius:99px;overflow:hidden;margin-top:7px}.report-progress span{display:block;height:100%;background:#d64a8a;border-radius:99px}.report-empty{padding:30px 10px;text-align:center;color:#94888f}.report-customer-count{font-size:13px;color:#7e737b;background:#fff8fb;padding:8px 11px;border-radius:10px}.admin-small-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border-radius:9px;background:#fff1f6;color:#d64a8a;text-decoration:none;font-size:12px;font-weight:700}.admin-small-btn.wa{background:#eaf8ef;color:#23864a}@media(max-width:1000px){.report-filter{grid-template-columns:1fr 1fr}.report-grid{grid-template-columns:1fr}.report-stats{grid-template-columns:1fr 1fr}}@media(max-width:600px){.report-actions{width:100%}.report-actions .admin-btn{flex:1;justify-content:center}.report-filter{grid-template-columns:1fr}.report-stats{grid-template-columns:1fr}.report-card-head{flex-direction:column}.report-customer-count{align-self:flex-start}}
@media print{.admin-top,.admin-side,.admin-title .report-actions,.report-filter-card,.admin-alert{display:none!important}.admin-layout{display:block}.admin-main{max-width:none;padding:0}.admin-card{box-shadow:none;break-inside:avoid}.report-grid{grid-template-columns:1fr 1fr}.report-stats{grid-template-columns:repeat(3,1fr)}body{background:#fff}.admin-shell{background:#fff}}
</style>
<?php require __DIR__ . '/_footer.php';
