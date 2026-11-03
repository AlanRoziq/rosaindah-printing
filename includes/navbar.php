<?php
require_once __DIR__ . '/product-categories.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/../config/customer-auth.php';
$customerPdo = db();
if ($customerPdo) {
    try {
        customer_auth_init($customerPdo);
    } catch (Throwable $e) {}
}
?>
<style>
.custom-navbar {
  min-height: 68px !important;
  height: auto !important;
  padding: 8px 0 !important;
  background: #ffffff !important;
  box-shadow: 0 4px 20px rgba(225, 29, 100, 0.05) !important;
  border-bottom: 1px solid #f8dce8 !important;
  font-family: 'Poppins', sans-serif !important;
}
.custom-navbar > .container {
  max-width: 1220px !important;
  padding-left: 20px !important;
  padding-right: 20px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
}
.custom-navbar .navbar-brand {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-decoration: none !important;
  flex-shrink: 0 !important;
}
.custom-navbar .logo-box {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}
.custom-navbar .logo-box img {
  width: 38px !important;
  height: 38px !important;
  flex: 0 0 38px !important;
  object-fit: contain !important;
}
.custom-navbar .brand-text h2 {
  font-size: 14.5px !important;
  font-weight: 800 !important;
  color: #e11d64 !important;
  letter-spacing: 0.4px !important;
  margin: 0 !important;
  line-height: 1.08 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text h3 {
  font-size: 8.5px !important;
  font-weight: 700 !important;
  letter-spacing: 2.2px !important;
  color: #e11d64 !important;
  margin: 1.5px 0 0 !important;
  line-height: 1.08 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text p {
  font-size: 6.5px !important;
  font-weight: 500 !important;
  color: #6b7280 !important;
  letter-spacing: 0.5px !important;
  margin: 1.5px 0 0 !important;
  line-height: 1.05 !important;
  white-space: nowrap !important;
}
@media (min-width: 992px) {
  .custom-navbar .navbar-nav {
    gap: 16px !important;
    margin: 0 auto !important;
    display: flex !important;
    align-items: center !important;
  }
  .custom-navbar .nav-link {
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    padding: 8px 6px !important;
    margin: 0 !important;
    transition: color 0.2s ease !important;
    white-space: nowrap !important;
  }
}
.custom-navbar .nav-link:hover,
.custom-navbar .nav-link.active {
  color: #e11d64 !important;
}
.custom-navbar .right-menu {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  flex-shrink: 0 !important;
}
.custom-navbar .search-btn,
.custom-navbar .cart-nav-btn {
  width: 38px !important;
  height: 38px !important;
  flex: 0 0 38px !important;
  border-radius: 50% !important;
  background: #fff0f5 !important;
  color: #e11d64 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 16px !important;
  text-decoration: none !important;
  border: 1px solid #fbcfe8 !important;
  position: relative !important;
  transition: 0.2s ease !important;
  margin: 0 !important;
}
.custom-navbar .search-btn:hover,
.custom-navbar .cart-nav-btn:hover {
  background: #ffe4ed !important;
  color: #be124f !important;
  border-color: #f43f7e !important;
}
.custom-navbar .cart-nav-count {
  position: absolute !important;
  top: -4px !important;
  right: -4px !important;
  background: #e11d64 !important;
  color: #fff !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  border-radius: 50% !important;
  width: 19px !important;
  height: 19px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 2px solid #fff !important;
}
.custom-navbar .login-btn {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 8px 20px !important;
  font-size: 13px !important;
  font-weight: 700 !important;
  border-radius: 30px !important;
  background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
  color: #fff !important;
  text-decoration: none !important;
  box-shadow: 0 15px 35px -5px rgba(244, 63, 126, 0.35) !important;
  white-space: nowrap !important;
  transition: all 0.25s ease !important;
}
.custom-navbar .login-btn:hover {
  background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 20px 40px -5px rgba(244, 63, 126, 0.45) !important;
  color: #fff !important;
}
@media (max-width: 991.98px) {
  .custom-navbar {
    min-height: 54px !important;
    padding: 6px 0 !important;
  }
  .custom-navbar .logo-box img {
    width: 34px !important;
    height: 34px !important;
    flex: 0 0 34px !important;
  }
  .custom-navbar .brand-text h2 {
    font-size: 13px !important;
  }
  .custom-navbar .brand-text h3 {
    font-size: 7.2px !important;
    letter-spacing: 1.8px !important;
  }
  .custom-navbar .brand-text p {
    font-size: 5.5px !important;
  }
}
@media (min-width: 768px) {
  nav.custom-navbar .right-menu a.cart-nav-btn,
  .custom-navbar .cart-nav-btn,
  .custom-navbar .right-menu .cart-nav-btn {
    display: inline-flex !important;
  }
}
@media (max-width: 767.98px) {
  .custom-navbar {
    min-height: 50px !important;
    padding: 6px 0 !important;
  }
  .custom-navbar > .container {
    padding-left: 12px !important;
    padding-right: 12px !important;
    gap: 6px !important;
    flex-wrap: nowrap !important;
  }
  .custom-navbar .navbar-brand {
    order: 1 !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
  }
  .custom-navbar .logo-box {
    gap: 6px !important;
  }
  .custom-navbar .logo-box img {
    width: 30px !important;
    height: 30px !important;
    flex: 0 0 30px !important;
  }
  .custom-navbar .brand-text h2 {
    font-size: 11.5px !important;
    letter-spacing: 0.3px !important;
    line-height: 1.05 !important;
  }
  .custom-navbar .brand-text h3 {
    font-size: 6.2px !important;
    letter-spacing: 1.6px !important;
    line-height: 1.05 !important;
    margin-top: 1px !important;
  }
  .custom-navbar .brand-text p {
    font-size: 4.8px !important;
    letter-spacing: 0.3px !important;
    line-height: 1.05 !important;
    margin-top: 1px !important;
  }
  /* Sembunyikan icon keranjang di header saat mobile karena sudah ada di bottom tab */
  nav.custom-navbar .right-menu a.cart-nav-btn,
  .custom-navbar .cart-nav-btn,
  .custom-navbar .right-menu .cart-nav-btn {
    display: none !important;
  }
  .custom-navbar .right-menu {
    order: 2 !important;
    width: auto !important;
    flex: 0 0 auto !important;
    margin: 0 !important;
    gap: 6px !important;
  }
  .custom-navbar .search-btn,
  .custom-navbar .right-menu .search-btn {
    width: 32px !important;
    height: 32px !important;
    flex: 0 0 32px !important;
    font-size: 13.5px !important;
  }
  .custom-navbar .login-btn,
  .custom-navbar .right-menu .login-btn {
    padding: 6px 11px !important;
    min-height: 32px !important;
    font-size: 10px !important;
    gap: 4px !important;
    border-radius: 999px !important;
  }
  .custom-navbar .navbar-toggler {
    order: 3 !important;
    width: 32px !important;
    height: 32px !important;
    flex: 0 0 32px !important;
    padding: 4px !important;
    margin: 0 !important;
    border-radius: 8px !important;
    border: 1px solid #fbcfe8 !important;
  }
}
@media (max-width: 390px) {
  .custom-navbar > .container {
    padding-left: 8px !important;
    padding-right: 8px !important;
    gap: 4px !important;
  }
  .custom-navbar .logo-box img {
    width: 27px !important;
    height: 27px !important;
    flex: 0 0 27px !important;
  }
  .custom-navbar .brand-text h2 {
    font-size: 10.5px !important;
  }
  .custom-navbar .brand-text h3 {
    font-size: 5.6px !important;
    letter-spacing: 1.4px !important;
  }
  .custom-navbar .brand-text p {
    font-size: 4.3px !important;
  }
  .custom-navbar .right-menu {
    gap: 4px !important;
  }
  .custom-navbar .search-btn,
  .custom-navbar .right-menu .search-btn {
    width: 29px !important;
    height: 29px !important;
    flex: 0 0 29px !important;
    font-size: 12px !important;
  }
  .custom-navbar .login-btn,
  .custom-navbar .right-menu .login-btn {
    padding: 5px 9px !important;
    min-height: 29px !important;
    font-size: 9px !important;
  }
  .custom-navbar .navbar-toggler {
    width: 29px !important;
    height: 29px !important;
    flex: 0 0 29px !important;
  }
}
.notif-dropdown-wrap {
  position: relative;
  display: inline-flex;
}
.notif-dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: -45px;
  width: 320px;
  max-width: calc(100vw - 24px);
  background: #ffffff;
  border: 1.5px solid #f2dce7;
  border-radius: 18px;
  box-shadow: 0 16px 40px rgba(24, 36, 58, 0.16);
  z-index: 1080;
  display: none;
  overflow: hidden;
  text-align: left;
}
.notif-dropdown-menu.show {
  display: block;
}
.notif-dropdown-head {
  padding: 12px 16px;
  background: linear-gradient(135deg, #fff0f6 0%, #ffffff 100%);
  border-bottom: 1px solid #f2dce7;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.notif-dropdown-head strong {
  font-size: 13.5px;
  font-weight: 800;
  color: #18243a;
}
.notif-dropdown-list {
  max-height: 310px;
  overflow-y: auto;
}
.notif-dropdown-item {
  display: flex;
  gap: 10px;
  padding: 11px 15px;
  border-bottom: 1px solid #f8eef3;
  text-decoration: none;
  color: #18243a;
  transition: background 0.15s ease;
}
.notif-dropdown-item:hover {
  background: #fff5f9;
}
.notif-dropdown-item.unread {
  background: #fff9fc;
}
.notif-dropdown-icon {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: #fff0f6;
  color: #e11d64;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}
.notif-dropdown-foot {
  display: block;
  text-align: center;
  padding: 10px 14px;
  font-size: 12px;
  font-weight: 800;
  color: #e11d64;
  background: #fffafc;
  text-decoration: none;
}
.notif-dropdown-foot:hover {
  background: #fff0f6;
  color: #be124f;
}
</style>
<nav class="navbar navbar-expand-lg custom-navbar">
    <div class="container">
        <a class="navbar-brand" href="<?php echo page_url('index.php'); ?>">
            <div class="logo-box">
                <img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing">
                <div class="brand-text">
                    <h2>ROSA INDAH</h2>
                    <h3>-PRINTING-</h3>
                    <p>CETAK IDE JADI KENYATAAN</p>
                </div>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="menu">
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo page_url('index.php'); ?>">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?php echo page_url('produk.php'); ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Produk
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo page_url('produk.php'); ?>">Semua Produk</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach (rosa_product_categories() as $menuCategory): ?>
                            <li><a class="dropdown-item" href="<?php echo page_url('produk.php?kategori=' . urlencode($menuCategory)); ?>"><?php echo htmlspecialchars($menuCategory); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('portfolio.php'); ?>"><i class="bi bi-images"></i> Portofolio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('promo.php'); ?>"><i class="bi bi-ticket-perforated"></i> Promo Member</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('tentang.php'); ?>">Tentang Kami</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('cara-pemesanan.php'); ?>">Cara Pemesanan</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('kontak.php'); ?>">Kontak</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo page_url('lacak-pesanan.php'); ?>"><i class="bi bi-geo-alt"></i> Lacak Pesanan</a></li>
            </ul>
        </div>

        <div class="right-menu">
            <a href="<?php echo page_url('produk.php'); ?>" class="search-btn" aria-label="Cari produk" title="Cari produk">
                <i class="bi bi-search"></i>
            </a>
            <a href="<?php echo page_url('cart.php'); ?>" class="cart-nav-btn d-none d-md-inline-flex" aria-label="Keranjang belanja" title="Keranjang Belanja">
                <i class="bi bi-bag"></i>
                <?php if (cart_count() > 0): ?><span class="cart-nav-count"><?php echo cart_count(); ?></span><?php endif; ?>
            </a>
            <?php if (customer_logged_in()): ?>
                <?php
                require_once __DIR__ . '/notifications.php';
                $navNotifCount = 0;
                $navNotifItems = [];
                $cUser = customer_user();
                $cId = (int)customer_id();
                if ($customerPdo && $cId > 0) {
                    try {
                        $existingNotifs = member_notifications($customerPdo, $cId, 8);
                        if (empty($existingNotifs)) {
                            member_notification_add(
                                $customerPdo,
                                $cId,
                                'welcome',
                                'Selamat Datang di Rosa Indah Member! 🎉',
                                'Kumpulkan poin dari setiap pesanan selesai dan gunakan kode voucher di halaman Checkout.',
                                page_url('akun.php?tab=poin')
                            );
                            $existingNotifs = member_notifications($customerPdo, $cId, 8);
                        }
                        $navNotifItems = $existingNotifs;
                        $navNotifCount = member_notification_unread_count($customerPdo, $cId);
                    } catch (Throwable $e) {}
                }
                if (empty($navNotifItems)) {
                    $navNotifItems = [
                        [
                            'id' => 1,
                            'type' => 'promo',
                            'title' => 'Promo & Voucher Member Aktif 🎟️',
                            'message' => 'Klaim kode voucher diskon dan pantau status pesanan cetak Anda secara langsung.',
                            'link' => page_url('akun.php?tab=poin'),
                            'is_read' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    ];
                }
                $cFirstName = explode(' ', trim((string)($cUser['name'] ?? 'Member')))[0];
                $cAvatarUrl = function_exists('customer_avatar_url') ? customer_avatar_url($cUser['avatar'] ?? '') : (!empty($cUser['avatar']) ? page_url(ltrim((string)$cUser['avatar'], '/')) : '');
                ?>
                <div class="notif-dropdown-wrap" id="navNotifWrap">
                    <a href="<?php echo page_url('akun.php?tab=notifikasi'); ?>" class="notification-nav-btn" id="navNotifToggle" aria-label="Notifikasi & Status Pesanan" title="Notifikasi Seller & Status Pesanan" onclick="var m=document.getElementById('navNotifMenu'); if(m){event.preventDefault(); m.classList.toggle('show');}">
                        <i class="bi bi-bell"></i><?php if ($navNotifCount > 0): ?><span class="notification-nav-count"><?php echo min(99, $navNotifCount); ?></span><?php endif; ?>
                    </a>
                    <div class="notif-dropdown-menu" id="navNotifMenu">
                        <div class="notif-dropdown-head">
                            <strong><i class="bi bi-bell-fill text-rosa me-1"></i> Notifikasi Member</strong>
                            <?php if ($navNotifCount > 0): ?>
                                <span style="background:#e11d64;color:#fff;font-size:10px;font-weight:800;padding:2px 7px;border-radius:999px;"><?php echo $navNotifCount; ?> Baru</span>
                            <?php endif; ?>
                        </div>
                        <div class="notif-dropdown-list">
                            <?php foreach (array_slice($navNotifItems, 0, 5) as $nItem):
                                $nType = (string)($nItem['type'] ?? 'general');
                                $nIcon = 'bi-megaphone-fill';
                                if (in_array($nType, ['order', 'status'], true)) $nIcon = 'bi-box-seam-fill';
                                elseif ($nType === 'payment') $nIcon = 'bi-credit-card-2-front-fill';
                                elseif (in_array($nType, ['points', 'bonus', 'reward'], true)) $nIcon = 'bi-star-fill';
                                elseif (in_array($nType, ['promo', 'voucher'], true)) $nIcon = 'bi-ticket-perforated-fill';
                                $nHref = !empty($nItem['link']) ? $nItem['link'] : page_url('akun.php?tab=notifikasi');
                            ?>
                                <a href="<?php echo htmlspecialchars($nHref, ENT_QUOTES, 'UTF-8'); ?>" class="notif-dropdown-item <?php echo empty($nItem['is_read']) ? 'unread' : ''; ?>">
                                    <div class="notif-dropdown-icon"><i class="bi <?php echo $nIcon; ?>"></i></div>
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-size:12px;font-weight:800;color:#18243a;line-height:1.3;margin-bottom:2px;"><?php echo htmlspecialchars((string)($nItem['title'] ?? 'Info Member'), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size:11px;color:#6f626b;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?php echo htmlspecialchars((string)($nItem['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo page_url('akun.php?tab=notifikasi'); ?>" class="notif-dropdown-foot">
                            Lihat Semua Notifikasi &rarr;
                        </a>
                    </div>
                </div>
                <script>
                document.addEventListener('click', function(e) {
                    var wrap = document.getElementById('navNotifWrap');
                    var menu = document.getElementById('navNotifMenu');
                    if (wrap && menu && !wrap.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
                </script>
                <a href="<?php echo page_url('akun.php'); ?>" class="login-btn" title="Akun Saya">
                    <img id="navAvatarImg" src="<?php echo htmlspecialchars($cAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($cFirstName, ENT_QUOTES, 'UTF-8'); ?>" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover; border: 1.5px solid #ffffff; <?php echo $cAvatarUrl !== '' ? 'display: inline-block;' : 'display: none;'; ?>" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-block';">
                    <i id="navAvatarIcon" class="bi bi-person-circle" style="<?php echo $cAvatarUrl !== '' ? 'display: none;' : 'display: inline-block;'; ?>"></i>
                    <span><?php echo htmlspecialchars($cFirstName, ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php else: ?>
                <a href="<?php echo page_url('login.php'); ?>" class="login-btn" title="Login Member">
                    <i class="bi bi-person"></i>
                    <span class="login-label-text">Login Member</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
