<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();
$pdo = db();
$pageTitle = 'Laporan Member & Loyalty';

$monthStart = date('Y-m-01 00:00:00');
$monthEnd = date('Y-m-d H:i:s', strtotime('first day of next month'));
$stats = [
  'members'=>0,'new_members'=>0,'points_balance'=>0,'points_earned'=>0,'points_redeemed'=>0,
  'active_promos'=>0,'active_vouchers'=>0,'voucher_used'=>0,'reward_redeemed'=>0,
  'unread_notifications'=>0
];
$topMembers=[];$promoRows=[];$voucherRows=[];$rewardRows=[];$pointRows=[];$notifRows=[];

if($pdo){
  $stats['members']=(int)$pdo->query("SELECT COUNT(*) FROM customers WHERE is_verified=1")->fetchColumn();
  $s=$pdo->prepare("SELECT COUNT(*) FROM customers WHERE is_verified=1 AND created_at>=? AND created_at<?");$s->execute([$monthStart,$monthEnd]);$stats['new_members']=(int)$s->fetchColumn();
  $stats['points_balance']=(int)$pdo->query("SELECT COALESCE(SUM(x.balance),0) FROM (SELECT customer_id, COALESCE(SUM(points),0) balance FROM loyalty_transactions GROUP BY customer_id) x")->fetchColumn();
  $stats['points_earned']=(int)$pdo->query("SELECT COALESCE(SUM(points),0) FROM loyalty_transactions WHERE type='earn'")->fetchColumn();
  $stats['points_redeemed']=(int)$pdo->query("SELECT COALESCE(SUM(ABS(points)),0) FROM loyalty_transactions WHERE type='redeem'")->fetchColumn();
  $s=$pdo->prepare("SELECT COUNT(*) FROM promotions WHERE status='active' AND starts_at<=NOW() AND ends_at>=NOW()");$s->execute();$stats['active_promos']=(int)$s->fetchColumn();
  $s=$pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE status='active' AND starts_at<=NOW() AND ends_at>=NOW()");$s->execute();$stats['active_vouchers']=(int)$s->fetchColumn();
  $stats['voucher_used']=(int)$pdo->query("SELECT COUNT(*) FROM voucher_redemptions")->fetchColumn();
  $stats['reward_redeemed']=(int)$pdo->query("SELECT COUNT(*) FROM loyalty_redemptions")->fetchColumn();
  $stats['unread_notifications']=(int)$pdo->query("SELECT COUNT(*) FROM member_notifications WHERE is_read=0")->fetchColumn();

  $topMembers=$pdo->query("SELECT c.id,c.name,c.phone,c.created_at,COALESCE(p.points,0) points,COALESCE(o.orders,0) orders,COALESCE(o.spend,0) spend,COALESCE(n.unread,0) unread FROM customers c LEFT JOIN (SELECT customer_id,SUM(points) points FROM loyalty_transactions GROUP BY customer_id) p ON p.customer_id=c.id LEFT JOIN (SELECT customer_phone,COUNT(*) orders,SUM(CASE WHEN status<>'cancelled' THEN total_estimate ELSE 0 END) spend FROM orders GROUP BY customer_phone) o ON o.customer_phone=c.phone LEFT JOIN (SELECT customer_id,COUNT(*) unread FROM member_notifications WHERE is_read=0 GROUP BY customer_id) n ON n.customer_id=c.id WHERE c.is_verified=1 ORDER BY points DESC,spend DESC LIMIT 15")->fetchAll();
  $promoRows=$pdo->query("SELECT name,discount_type,discount_value,usage_count,quota,starts_at,ends_at,status FROM promotions ORDER BY usage_count DESC,created_at DESC LIMIT 10")->fetchAll();
  $voucherRows=$pdo->query("SELECT code,name,discount_type,discount_value,usage_count,quota,starts_at,ends_at,status FROM vouchers ORDER BY usage_count DESC,created_at DESC LIMIT 10")->fetchAll();
  $rewardRows=$pdo->query("SELECT r.name,r.points_cost,r.discount_type,r.discount_value,r.redeemed_count,r.quota,r.status,COUNT(lr.id) actual_redemptions FROM loyalty_rewards r LEFT JOIN loyalty_redemptions lr ON lr.reward_id=r.id GROUP BY r.id ORDER BY actual_redemptions DESC,r.points_cost ASC LIMIT 10")->fetchAll();
  $pointRows=$pdo->query("SELECT type,COUNT(*) transactions,COALESCE(SUM(points),0) points FROM loyalty_transactions GROUP BY type ORDER BY transactions DESC")->fetchAll();
  $notifRows=$pdo->query("SELECT type,COUNT(*) total,SUM(is_read=0) unread FROM member_notifications GROUP BY type ORDER BY total DESC")->fetchAll();
}
function mr($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function rupiah($v){return 'Rp'.number_format((float)$v,0,',','.');}
require __DIR__ . '/_header.php';
?>
<div class="admin-title">
 <div><h1>Member, Promo & Loyalty</h1><p style="color:#777;margin:7px 0 0">Ringkasan performa Member, promo, voucher, poin, reward, dan notifikasi.</p></div>
 <div class="report-actions"><button class="admin-btn" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Laporan</button></div>
</div>

<div class="admin-stats mr-stats">
 <div class="admin-stat"><i class="bi bi-people"></i><strong><?php echo number_format($stats['members'],0,',','.'); ?></strong><span>Total Member terverifikasi</span></div>
 <div class="admin-stat"><i class="bi bi-person-plus"></i><strong><?php echo number_format($stats['new_members'],0,',','.'); ?></strong><span>Member baru bulan ini</span></div>
 <div class="admin-stat"><i class="bi bi-stars"></i><strong><?php echo number_format($stats['points_balance'],0,',','.'); ?></strong><span>Total saldo poin</span></div>
 <div class="admin-stat"><i class="bi bi-ticket-perforated"></i><strong><?php echo number_format($stats['active_promos']+$stats['active_vouchers'],0,',','.'); ?></strong><span>Promo + voucher aktif</span></div>
 <div class="admin-stat"><i class="bi bi-gift"></i><strong><?php echo number_format($stats['reward_redeemed'],0,',','.'); ?></strong><span>Reward ditukar</span></div>
 <div class="admin-stat"><i class="bi bi-bell"></i><strong><?php echo number_format($stats['unread_notifications'],0,',','.'); ?></strong><span>Notifikasi belum dibaca</span></div>
</div>

<div class="mr-grid">
 <section class="admin-card"><div class="mr-head"><div><h2>Ringkasan Loyalty</h2><p>Pergerakan poin seluruh Member.</p></div></div>
  <div class="mr-kpis"><div><span>Poin diperoleh</span><strong>+<?php echo number_format($stats['points_earned'],0,',','.'); ?></strong></div><div><span>Poin ditukar</span><strong>-<?php echo number_format($stats['points_redeemed'],0,',','.'); ?></strong></div><div><span>Penukaran voucher</span><strong><?php echo number_format($stats['voucher_used'],0,',','.'); ?></strong></div></div>
  <div class="mr-list"><?php foreach($pointRows as $r): ?><div class="mr-row"><span><?php echo mr(ucfirst($r['type'])); ?></span><b><?php echo number_format((int)$r['transactions'],0,',','.'); ?> transaksi · <?php echo number_format((int)$r['points'],0,',','.'); ?> poin</b></div><?php endforeach; if(!$pointRows): ?><div class="mr-empty">Belum ada transaksi loyalty.</div><?php endif; ?></div>
 </section>
 <section class="admin-card"><div class="mr-head"><div><h2>Notifikasi</h2><p>Distribusi notifikasi Member.</p></div></div>
  <div class="mr-list"><?php foreach($notifRows as $r): ?><div class="mr-row"><span><?php echo mr(ucfirst($r['type'])); ?></span><b><?php echo (int)$r['total']; ?> total · <?php echo (int)$r['unread']; ?> belum dibaca</b></div><?php endforeach; if(!$notifRows): ?><div class="mr-empty">Belum ada notifikasi.</div><?php endif; ?></div>
 </section>
</div>

<section class="admin-card mr-section"><div class="mr-head"><div><h2>🏆 Member Teratas</h2><p>Prioritas berdasarkan saldo poin, kemudian nilai belanja.</p></div><a class="admin-btn secondary" href="<?php echo page_url('admin/pelanggan.php'); ?>">Kelola Member</a></div>
<div class="mr-table-wrap"><table class="admin-table"><thead><tr><th>Member</th><th>WhatsApp</th><th>Poin</th><th>Pesanan</th><th>Total Belanja</th><th>Notif.</th><th>Bergabung</th></tr></thead><tbody>
<?php foreach($topMembers as $m): ?><tr><td><strong><?php echo mr($m['name']); ?></strong></td><td><?php echo mr($m['phone']); ?></td><td><strong style="color:#d64a8a"><?php echo number_format((int)$m['points'],0,',','.'); ?></strong></td><td><?php echo (int)$m['orders']; ?></td><td><?php echo rupiah($m['spend']); ?></td><td><?php echo (int)$m['unread']; ?></td><td><?php echo date('d/m/Y',strtotime($m['created_at'])); ?></td></tr><?php endforeach; if(!$topMembers): ?><tr><td colspan="7"><div class="mr-empty">Belum ada Member.</div></td></tr><?php endif; ?></tbody></table></div>
</section>

<div class="mr-grid">
<section class="admin-card"><div class="mr-head"><div><h2>🎟️ Performa Promo</h2><p>Promo dengan penggunaan tertinggi.</p></div><a class="admin-btn secondary" href="<?php echo page_url('admin/promo.php'); ?>">Kelola Promo</a></div><div class="mr-list"><?php foreach($promoRows as $r): ?><div class="mr-row mr-row-block"><div><strong><?php echo mr($r['name']); ?></strong><small><?php echo mr($r['status']); ?> · <?php echo date('d/m/Y',strtotime($r['starts_at'])); ?>–<?php echo date('d/m/Y',strtotime($r['ends_at'])); ?></small></div><b><?php echo (int)$r['usage_count']; ?> terpakai</b></div><?php endforeach; if(!$promoRows): ?><div class="mr-empty">Belum ada promo.</div><?php endif; ?></div></section>
<section class="admin-card"><div class="mr-head"><div><h2>🎫 Performa Voucher</h2><p>Kode voucher dengan penggunaan tertinggi.</p></div><a class="admin-btn secondary" href="<?php echo page_url('admin/promo.php'); ?>">Kelola Voucher</a></div><div class="mr-list"><?php foreach($voucherRows as $r): ?><div class="mr-row mr-row-block"><div><strong class="mr-code"><?php echo mr($r['code']); ?></strong><small><?php echo mr($r['name']); ?> · <?php echo mr($r['status']); ?></small></div><b><?php echo (int)$r['usage_count']; ?> terpakai</b></div><?php endforeach; if(!$voucherRows): ?><div class="mr-empty">Belum ada voucher.</div><?php endif; ?></div></section>
</div>

<section class="admin-card mr-section"><div class="mr-head"><div><h2>🎁 Performa Reward</h2><p>Reward yang paling sering ditukar Member.</p></div><a class="admin-btn secondary" href="<?php echo page_url('admin/loyalty.php'); ?>">Kelola Reward</a></div><div class="mr-table-wrap"><table class="admin-table"><thead><tr><th>Reward</th><th>Biaya Poin</th><th>Diskon</th><th>Ditukar</th><th>Kuota</th><th>Status</th></tr></thead><tbody><?php foreach($rewardRows as $r): ?><tr><td><strong><?php echo mr($r['name']); ?></strong></td><td><?php echo number_format((int)$r['points_cost'],0,',','.'); ?> poin</td><td><?php echo $r['discount_type']==='fixed'?rupiah($r['discount_value']):number_format($r['discount_value'],0,',','.').'%'; ?></td><td><?php echo (int)$r['actual_redemptions']; ?></td><td><?php echo $r['quota']===null?'∞':(int)$r['quota']; ?></td><td><?php echo mr($r['status']); ?></td></tr><?php endforeach; if(!$rewardRows): ?><tr><td colspan="6"><div class="mr-empty">Belum ada reward.</div></td></tr><?php endif; ?></tbody></table></div></section>

<style>
.mr-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;margin-bottom:18px}.mr-section{margin-bottom:18px}.mr-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:16px}.mr-head h2{margin:0;color:#18243a;font-size:19px}.mr-head p{margin:5px 0 0;color:#817982;font-size:13px}.mr-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}.mr-kpis div{background:#fff8fb;border:1px solid #f1dfe8;border-radius:12px;padding:13px}.mr-kpis span{display:block;color:#8b7f87;font-size:11px}.mr-kpis strong{display:block;color:#18243a;font-size:18px;margin-top:5px}.mr-list{display:grid;gap:2px}.mr-row{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:11px 0;border-bottom:1px solid #f4e9ee;font-size:13px}.mr-row:last-child{border-bottom:0}.mr-row span{color:#5f5860}.mr-row b{color:#3f3940;font-size:12px;text-align:right}.mr-row-block{align-items:flex-start}.mr-row-block strong{display:block;color:#3e3940}.mr-row small{display:block;color:#938990;font-size:10px;margin-top:4px}.mr-code{color:#d64a8a;letter-spacing:1px}.mr-empty{padding:25px 10px;text-align:center;color:#968b91}.mr-table-wrap{overflow:auto}.mr-stats{grid-template-columns:repeat(3,1fr)}
@media(max-width:900px){.mr-grid{grid-template-columns:1fr}.mr-stats{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.mr-stats{grid-template-columns:1fr 1fr}.mr-kpis{grid-template-columns:1fr}.mr-head{flex-direction:column}.mr-head .admin-btn{width:100%;justify-content:center}.mr-table-wrap .admin-table{min-width:760px}.mr-row{font-size:12px}}
@media print{.mr-head .admin-btn,.report-actions{display:none!important}.mr-grid{grid-template-columns:1fr 1fr}.mr-stats{grid-template-columns:repeat(3,1fr)}}
</style>
<?php require __DIR__ . '/_footer.php'; ?>
