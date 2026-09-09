<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();
$admin = admin_user();
$flash = get_admin_flash();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> — Rosa Indah Printing</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>"><link rel="stylesheet" href="<?php echo asset_url('css/admin.css'); ?>"><style>
.admin-shell{min-height:100vh;background:#fdf8fb}.admin-top{background:#fff;border-bottom:1px solid #f2dce7;display:flex;align-items:center;justify-content:space-between;padding:15px 28px;position:sticky;top:0;z-index:20}.admin-brand{font-weight:800;color:#18243a}.admin-brand span{color:#d64a8a}.admin-user{display:flex;align-items:center;gap:15px;color:#6c6670;font-size:14px}.admin-user a{color:#d64a8a;text-decoration:none;font-weight:700}.admin-layout{display:grid;grid-template-columns:245px 1fr;min-height:calc(100vh - 65px)}.admin-side{background:#fff;border-right:1px solid #f2dce7;padding:25px 15px}.admin-side a{display:flex;gap:12px;align-items:center;padding:12px 15px;border-radius:12px;color:#5f5a62;text-decoration:none;margin-bottom:6px}.admin-side a:hover,.admin-side a.active{background:#ffe8f1;color:#d64a8a;font-weight:700}.admin-main{padding:30px;max-width:1400px;width:100%;box-sizing:border-box}.admin-title{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:25px}.admin-title h1{margin:0;color:#18243a;font-size:30px}.admin-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;background:#d64a8a;color:#fff;padding:11px 17px;text-decoration:none;font-weight:700;cursor:pointer}.admin-btn.secondary{background:#fff;color:#d64a8a;border:1px solid #efc9da}.admin-btn.danger{background:#e05252}.admin-card{background:#fff;border:1px solid #f1dfe8;border-radius:18px;padding:22px;box-shadow:0 10px 30px rgba(31,24,35,.04)}.admin-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px}.admin-stat{background:#fff;border:1px solid #f1dfe8;border-radius:18px;padding:20px}.admin-stat i{font-size:24px;color:#d64a8a}.admin-stat strong{display:block;font-size:28px;color:#18243a;margin-top:8px}.admin-stat span{color:#817982;font-size:13px}.admin-table{width:100%;border-collapse:collapse}.admin-table th,.admin-table td{padding:13px 10px;border-bottom:1px solid #f2e7ec;text-align:left;vertical-align:middle}.admin-table th{font-size:12px;color:#8b7f87;text-transform:uppercase}.admin-table td{color:#403b41;font-size:14px}.admin-product-img{width:58px;height:58px;object-fit:cover;border-radius:10px;background:#faf1f5}.admin-actions{display:flex;gap:7px;flex-wrap:wrap}.admin-actions a{padding:7px 10px;border-radius:9px;background:#fff1f6;color:#d64a8a;text-decoration:none;font-size:12px;font-weight:700}.admin-form{display:grid;gap:17px}.admin-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:17px}.admin-field label{display:block;font-size:13px;font-weight:700;color:#4e4850;margin-bottom:7px}.admin-field input,.admin-field select,.admin-field textarea{width:100%;box-sizing:border-box;border:1px solid #eadde3;border-radius:11px;padding:11px 13px;font:inherit;outline:none;background:#fff}.admin-field textarea{min-height:115px;resize:vertical}.admin-field input:focus,.admin-field select:focus,.admin-field textarea:focus{border-color:#d64a8a;box-shadow:0 0 0 3px #ffe8f1}.admin-check{display:flex;gap:10px;align-items:center;color:#5e5860}.admin-alert{padding:12px 15px;border-radius:12px;margin-bottom:18px;background:#eaf8ef;color:#287a47}.admin-alert.error{background:#fff0f0;color:#a33b3b}.admin-login{min-height:100vh;background:linear-gradient(135deg,#fff8fb,#fff);display:flex;align-items:center;justify-content:center;padding:20px}.admin-login-card{width:min(430px,100%);background:#fff;border:1px solid #f0dce7;border-radius:22px;padding:32px;box-shadow:0 20px 50px rgba(214,74,138,.1)}.admin-login-card h1{color:#18243a;margin:0 0 7px}.admin-login-card p{color:#7d747c;margin-bottom:25px}.admin-login-card .logo{width:95px;margin-bottom:20px}.admin-login-note{margin-top:18px;font-size:12px;color:#968d93;background:#fff8fb;padding:10px;border-radius:10px}
@media(max-width:900px){.admin-layout{grid-template-columns:1fr}.admin-side{border-right:0;border-bottom:1px solid #f2dce7;display:flex;overflow:auto;padding:10px}.admin-side a{white-space:nowrap}.admin-stats{grid-template-columns:1fr 1fr}.admin-grid-2{grid-template-columns:1fr}.admin-main{padding:18px}.admin-top{padding:13px 18px}}
@media(max-width:550px){.admin-stats{grid-template-columns:1fr}.admin-title{align-items:flex-start;flex-direction:column}.admin-table{min-width:780px}.admin-card{overflow:auto}.admin-user span{display:none}}

/* FINAL ADMIN RESPONSIVE POLISH */
html,body{max-width:100%;overflow-x:hidden}
.admin-shell,.admin-layout,.admin-main{min-width:0}
.admin-top{gap:12px;min-width:0}
.admin-brand{white-space:nowrap}
.admin-user{min-width:0}
.admin-main{overflow-x:hidden}
.admin-card{min-width:0}
.admin-table{min-width:760px}
.admin-form,.admin-grid-2{min-width:0}
.admin-field{min-width:0}
.admin-field input,.admin-field select,.admin-field textarea{max-width:100%}
@media(max-width:900px){
  .admin-top{padding:11px 14px}
  .admin-brand{font-size:14px}
  .admin-user{font-size:12px;gap:9px}
  .admin-layout{display:block}
  .admin-side{display:flex;gap:5px;overflow-x:auto;overflow-y:hidden;padding:8px 10px;white-space:nowrap;-webkit-overflow-scrolling:touch}
  .admin-side a{flex:0 0 auto;margin:0;padding:9px 11px;font-size:12px;gap:7px}
  .admin-main{padding:16px 12px}
  .admin-title{margin-bottom:17px}
  .admin-title h1{font-size:25px}
  .admin-card{padding:16px;border-radius:15px;overflow-x:auto}
  .admin-stats{gap:10px;margin-bottom:16px}
  .admin-stat{padding:15px;border-radius:15px}
  .admin-stat strong{font-size:23px}
  .admin-btn{padding:10px 13px;font-size:12px}
}
@media(max-width:600px){
  .admin-top{align-items:flex-start}
  .admin-brand{font-size:13px}
  .admin-user a{white-space:nowrap}
  .admin-main{padding:13px 10px}
  .admin-title{gap:10px}
  .admin-title h1{font-size:22px}
  .admin-title p{font-size:12px;line-height:1.55}
  .admin-stats{grid-template-columns:1fr 1fr}
  .admin-stat{padding:13px}
  .admin-stat i{font-size:20px}
  .admin-stat strong{font-size:20px;margin-top:5px}
  .admin-stat span{font-size:11px}
  .admin-card{padding:13px}
  .admin-table{min-width:720px}
  .admin-table th,.admin-table td{padding:10px 8px;font-size:12px}
  .admin-product-img{width:46px;height:46px}
  .admin-grid-2{grid-template-columns:1fr}
  .admin-form{gap:13px}
  .admin-field label{font-size:12px}
  .admin-field input,.admin-field select,.admin-field textarea{font-size:13px;padding:10px 11px}
}
@media(max-width:430px){
  .admin-brand{font-size:12px}
  .admin-user span{display:none}
  .admin-side a{font-size:11px;padding:8px 10px}
  .admin-stats{grid-template-columns:1fr}
  .admin-title .admin-btn{width:100%;justify-content:center}
}

</style></head><body><div class="admin-shell"><header class="admin-top"><a class="admin-brand" href="<?php echo page_url('admin/dashboard.php'); ?>">ROSA INDAH <span>PRINTING</span></a><div class="admin-user"><span>Halo, <?php echo htmlspecialchars($admin['name']); ?></span><a href="<?php echo page_url('admin/ubah-password.php'); ?>"><i class="bi bi-shield-lock"></i> Ubah Password</a><a href="<?php echo page_url('admin/logout.php'); ?>"><i class="bi bi-box-arrow-right"></i> Keluar</a></div></header><div class="admin-layout"><aside class="admin-side"><?php $current=basename($_SERVER['PHP_SELF']); ?><a class="<?php echo $current==='dashboard.php'?'active':''; ?>" href="<?php echo page_url('admin/dashboard.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a><a class="<?php echo $current==='produk.php'?'active':''; ?>" href="<?php echo page_url('admin/produk.php'); ?>"><i class="bi bi-box-seam"></i> Produk</a><a class="<?php echo $current==='pesanan.php'?'active':''; ?>" href="<?php echo page_url('admin/pesanan.php'); ?>"><i class="bi bi-cart-check"></i> Pesanan</a><a class="<?php echo $current==='pelanggan.php'?'active':''; ?>" href="<?php echo page_url('admin/pelanggan.php'); ?>"><i class="bi bi-people"></i> Member</a><a class="<?php echo $current==='laporan.php'?'active':''; ?>" href="<?php echo page_url('admin/laporan.php'); ?>"><i class="bi bi-bar-chart-line"></i> Laporan</a><a class="<?php echo $current==='member-report.php'?'active':''; ?>" href="<?php echo page_url('admin/member-report.php'); ?>"><i class="bi bi-graph-up-arrow"></i> Laporan Member</a><a class="<?php echo $current==='promo.php'?'active':''; ?>" href="<?php echo page_url('admin/promo.php'); ?>"><i class="bi bi-ticket-perforated"></i> Promo & Voucher</a><a class="<?php echo $current==='loyalty.php'?'active':''; ?>" href="<?php echo page_url('admin/loyalty.php'); ?>"><i class="bi bi-stars"></i> Poin & Reward</a><a class="<?php echo $current==='notifikasi.php'?'active':''; ?>" href="<?php echo page_url('admin/notifikasi.php'); ?>"><i class="bi bi-bell"></i> Notifikasi Member</a><a href="<?php echo page_url('index.php'); ?>"><i class="bi bi-house"></i> Lihat Website</a></aside><main class="admin-main">
<?php if($flash): ?><div class="admin-alert <?php echo $flash['type']==='error'?'error':''; ?>"><?php echo htmlspecialchars($flash['message']); ?></div><?php endif; ?>
