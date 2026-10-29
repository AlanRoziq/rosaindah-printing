<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Solusi Percetakan Online Terlengkap';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
/* Sinkronisasi penuh Header (Topbar Promo & Custom Navbar) agar 100% sama persis dengan Beranda Preview */
.topbar-promo {
  background: linear-gradient(90deg, #88133d 0%, #be124f 35%, #e11d64 70%, #f43f7e 100%) !important;
  color: #ffffff !important;
  font-size: 13px !important;
  padding: 8px 0 !important;
  position: relative !important;
  z-index: 50 !important;
  box-shadow: 0 2px 10px rgba(136, 19, 61, 0.25) !important;
  font-family: 'Poppins', sans-serif !important;
}
.topbar-promo .container {
  max-width: 1220px !important;
  padding-left: 20px !important;
  padding-right: 20px !important;
}
.topbar-content {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}
.topbar-promo-text {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-weight: 500 !important;
  font-size: 12.5px !important;
  color: #ffffff !important;
}
.topbar-badge {
  display: inline-flex !important;
  align-items: center !important;
  padding: 2px 10px !important;
  border-radius: 9999px !important;
  font-size: 10.5px !important;
  font-weight: 800 !important;
  background: #ffffff !important;
  color: #be124f !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}
.promo-code {
  text-decoration: underline !important;
  color: #ffe4ed !important;
  cursor: pointer !important;
}
.topbar-info {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  color: #ffe4ed !important;
  font-size: 12px !important;
}
.topbar-separator {
  opacity: 0.6 !important;
}
.topbar-wa-link {
  color: #ffffff !important;
  text-decoration: none !important;
  font-weight: 600 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  background: rgba(255, 255, 255, 0.15) !important;
  padding: 2px 10px !important;
  border-radius: 20px !important;
  width: auto !important;
  height: auto !important;
}
.custom-navbar {
  min-height: 76px !important;
  height: auto !important;
  padding: 10px 0 !important;
  background: #ffffff !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
  border-bottom: 1px solid #f6e6ee !important;
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
  gap: 12px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-decoration: none !important;
  flex-shrink: 0 !important;
}
.custom-navbar .logo-box {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
}
.custom-navbar .logo-box img {
  width: 52px !important;
  height: 52px !important;
  object-fit: contain !important;
}
.custom-navbar .brand-text h2 {
  font-size: 18px !important;
  font-weight: 800 !important;
  color: #d63f84 !important;
  letter-spacing: 0.5px !important;
  margin: 0 !important;
  line-height: 1.1 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text h3 {
  font-size: 11px !important;
  font-weight: 600 !important;
  letter-spacing: 3px !important;
  color: #e05e99 !important;
  margin: 2px 0 0 !important;
  line-height: 1.1 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text p {
  font-size: 8.5px !important;
  font-weight: 500 !important;
  color: #888 !important;
  letter-spacing: 0.8px !important;
  margin: 2px 0 0 !important;
  line-height: 1 !important;
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
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    padding: 8px 6px !important;
    margin: 0 !important;
    white-space: nowrap !important;
  }
}
</style>

<header class="main-header">
    <?php
    $topbarFile = __DIR__ . '/includes/topbar.php';
    $topbarRaw = is_file($topbarFile) ? (string)@file_get_contents($topbarFile) : '';
    if (strpos($topbarRaw, 'topbar-promo') !== false && strpos($topbarRaw, 'ROSAHEMAT10') !== false) {
        include $topbarFile;
    } else {
        ?>
        <div class="topbar-promo">
            <div class="container">
                <div class="topbar-content">
                    <div class="topbar-promo-text">
                        <span class="topbar-badge">Promo Spesial</span>
                        <span>Diskon <strong>20%</strong> Cetak Kemasan, Undangan &amp; Stiker! Kode: <strong class="promo-code">ROSAHEMAT10</strong></span>
                    </div>
                    <div class="topbar-info">
                        <span><i class="bi bi-clock"></i> 08.00 - 21.00 WIB</span>
                        <span class="topbar-separator">•</span>
                        <span><i class="bi bi-truck"></i> Pengiriman Seluruh Indonesia</span>
                        <span class="topbar-separator">•</span>
                        <a href="https://wa.me/<?php echo defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371'; ?>" target="_blank" rel="noopener" class="topbar-wa-link">
                            <i class="bi bi-whatsapp"></i> 0823-3103-5371
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    $navbarFile = __DIR__ . '/includes/navbar.php';
    $navbarRaw = is_file($navbarFile) ? (string)@file_get_contents($navbarFile) : '';
    if (strpos($navbarRaw, 'Portofolio') !== false) {
        include $navbarFile;
    } else {
        require_once __DIR__ . '/includes/product-categories.php';
        require_once __DIR__ . '/includes/cart.php';
        require_once __DIR__ . '/config/customer-auth.php';
        $customerPdo = db();
        if ($customerPdo) {
            try { customer_auth_init($customerPdo); } catch (Throwable $e) {}
        }
        ?>
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
                    <a href="<?php echo page_url('cart.php'); ?>" class="cart-nav-btn" aria-label="Keranjang belanja" title="Keranjang Belanja">
                        <i class="bi bi-bag"></i>
                        <?php if (cart_count() > 0): ?><span class="cart-nav-count"><?php echo cart_count(); ?></span><?php endif; ?>
                    </a>
                    <?php if (customer_logged_in()): ?>
                        <?php
                        require_once __DIR__ . '/includes/notifications.php';
                        $navNotifCount = 0;
                        if ($customerPdo) {
                            try { $navNotifCount = member_notification_unread_count($customerPdo, (int)customer_id()); } catch (Throwable $e) {}
                        }
                        $cUser = customer_user();
                        $cFirstName = explode(' ', trim((string)($cUser['name'] ?? 'Member')))[0];
                        ?>
                        <a href="<?php echo page_url('notifikasi-member.php'); ?>" class="notification-nav-btn" aria-label="Notifikasi Member" title="Notifikasi Member">
                            <i class="bi bi-bell"></i><?php if ($navNotifCount > 0): ?><span class="notification-nav-count"><?php echo min(99, $navNotifCount); ?></span><?php endif; ?>
                        </a>
                        <a href="<?php echo page_url('akun.php'); ?>" class="login-btn" title="Akun Saya">
                            <i class="bi bi-person-circle"></i>
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
        <?php
    }
    ?>
</header>

<?php include __DIR__ . '/includes/hero.php'; ?>

<?php include __DIR__ . '/includes/categories.php'; ?>

<?php include __DIR__ . '/includes/why-us.php'; ?>

<?php include __DIR__ . '/includes/products.php'; ?>

<?php include __DIR__ . '/includes/how-it-works.php'; ?>

<?php include __DIR__ . '/includes/portfolio.php'; ?>

<?php include __DIR__ . '/includes/testimonial.php'; ?>

<?php include __DIR__ . '/includes/statistics.php'; ?>

<?php include __DIR__ . '/includes/faq.php'; ?>

<?php include __DIR__ . '/includes/cta.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>