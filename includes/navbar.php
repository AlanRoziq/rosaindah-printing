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
                if ($customerPdo) {
                    try {
                        $navNotifCount = member_notification_unread_count($customerPdo, (int)customer_id());
                    } catch (Throwable $e) {}
                }
                $cUser = customer_user();
                $cFirstName = explode(' ', trim((string)($cUser['name'] ?? 'Member')))[0];
                ?>
                <a href="<?php echo page_url('notifikasi-member.php'); ?>" class="notification-nav-btn" aria-label="Notifikasi Member" title="Notifikasi Member">
                    <i class="bi bi-bell"></i><?php if ($navNotifCount > 0): ?><span class="notification-nav-count"><?php echo min(99, $navNotifCount); ?></span><?php endif; ?>
                </a>
                <a href="<?php echo page_url('akun.php'); ?>" class="login-btn" title="Akun Saya">
                    <?php if (!empty($cUser['avatar'])): ?>
                        <img src="<?php echo '/' . ltrim((string)$cUser['avatar'], '/'); ?>" alt="<?php echo htmlspecialchars($cFirstName, ENT_QUOTES, 'UTF-8'); ?>" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e11d64;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                        <i class="bi bi-person-circle" style="display: none;"></i>
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
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
