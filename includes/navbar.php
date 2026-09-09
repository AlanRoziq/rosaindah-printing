<?php
require_once __DIR__ . '/product-categories.php'; require_once __DIR__ . '/cart.php'; ?>
<?php require_once __DIR__ . '/../config/customer-auth.php'; $customerPdo = db(); if ($customerPdo) { try { customer_auth_init($customerPdo); } catch (Throwable $e) {} } ?>
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

        <button class="navbar-toggler" type="button" aria-controls="menu" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center" id="menu">
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo page_url('index.php'); ?>">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?php echo page_url('produk.php'); ?>" data-bs-toggle="dropdown">
                        Produk
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo page_url('produk.php'); ?>">Semua Produk</a></li>
                        <?php foreach (rosa_product_categories() as $menuCategory): ?>
                            <li><a class="dropdown-item" href="<?php echo page_url('produk.php?kategori=' . urlencode($menuCategory)); ?>"><?php echo htmlspecialchars($menuCategory); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a class="nav-link" href="<?php echo page_url('promo.php'); ?>"><i class="bi bi-ticket-perforated"></i> Promo Member</a></li>
                <li><a class="nav-link" href="<?php echo page_url('tentang.php'); ?>">Tentang Kami</a></li>
                <li><a class="nav-link" href="<?php echo page_url('cara-pemesanan.php'); ?>">Cara Pemesanan</a></li>
                <li><a class="nav-link" href="<?php echo page_url('kontak.php'); ?>">Kontak</a></li>
                <li><a class="nav-link" href="<?php echo page_url('lacak-pesanan.php'); ?>"><i class="bi bi-geo-alt"></i> Lacak Pesanan</a></li>
            </ul>
        </div>

        <div class="right-menu">
            <a href="<?php echo page_url('produk.php'); ?>" class="search-btn" aria-label="Cari produk">
                <i class="bi bi-search"></i>
            </a>
            <a href="<?php echo page_url('cart.php'); ?>" class="cart-nav-btn" aria-label="Keranjang belanja">
                <i class="bi bi-bag"></i>
                <?php if (cart_count() > 0): ?><span class="cart-nav-count"><?php echo cart_count(); ?></span><?php endif; ?>
            </a>
            <?php if (customer_logged_in()): ?>
                <?php require_once __DIR__ . '/notifications.php'; $navNotifCount = 0; if ($customerPdo) { try { $navNotifCount = member_notification_unread_count($customerPdo, (int)customer_id()); } catch (Throwable $e) {} } ?>
                <a href="<?php echo page_url('notifikasi-member.php'); ?>" class="notification-nav-btn" aria-label="Notifikasi Member" title="Notifikasi Member">
                    <i class="bi bi-bell"></i><?php if ($navNotifCount > 0): ?><span class="notification-nav-count"><?php echo min(99, $navNotifCount); ?></span><?php endif; ?>
                </a>
                <a href="<?php echo page_url('akun.php'); ?>" class="login-btn">
                    <i class="bi bi-person-circle"></i>
                    <?php echo htmlspecialchars(customer_user()['name'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo page_url('login.php'); ?>" class="login-btn">
                    <i class="bi bi-person"></i>
                    <span class="login-label-text">Login Member</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
