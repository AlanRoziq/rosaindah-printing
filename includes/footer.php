<footer class="bg-dark text-white text-center p-4 mt-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 text-start">
            <div>
                <h5 class="text-white mb-1 fw-bold">Rosa Indah Printing</h5>
                <small class="text-secondary">Cetak Ide Jadi Kenyataan • Solusi Percetakan Online Terlengkap &amp; Cepat</small>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="<?php echo page_url('admin/dashboard.php'); ?>" class="text-secondary text-decoration-none small" style="opacity: 0.65;">
                    <i class="bi bi-shield-lock"></i> Panel Admin
                </a>
            </div>
        </div>
        <hr class="border-secondary opacity-25">
        <p class="mb-0 text-secondary small">
            © <?php echo date("Y"); ?> Rosa Indah Printing. Hak Cipta Dilindungi.
        </p>
    </div>
</footer>

<?php
// Hitung jumlah item keranjang jika ada sesi
$cartCount = 0;
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cItem) {
        $cartCount += isset($cItem['qty']) ? (int)$cItem['qty'] : (isset($cItem['quantity']) ? (int)$cItem['quantity'] : 1);
    }
}
$currentScript = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!-- MOBILE APP BOTTOM NAVIGATION BAR (5 MENU DENGAN 1 DI TENGAH BULAT PRIMARY) -->
<nav class="mobile-bottom-nav d-md-none" aria-label="Navigasi Menu Mobile">
    <a href="<?php echo page_url(''); ?>" class="mobile-nav-item <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Beranda</span>
    </a>
    <a href="<?php echo page_url('produk.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'produk.php' || $currentScript === 'detail-produk.php') ? 'active' : ''; ?>">
        <i class="bi bi-grid-fill"></i>
        <span>Katalog</span>
    </a>

    <!-- Menu Tengah Bulat Berwarna Primary -->
    <a href="<?php echo page_url('cart.php'); ?>" class="mobile-nav-center <?php echo ($currentScript === 'cart.php' || $currentScript === 'checkout.php') ? 'active' : ''; ?>" aria-label="Pesan &amp; Keranjang">
        <div class="center-bubble">
            <i class="bi bi-bag-plus-fill"></i>
            <?php if ($cartCount > 0): ?>
                <span class="center-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </div>
        <span class="center-label">Pesan</span>
    </a>

    <a href="<?php echo page_url('promo.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'promo.php') ? 'active' : ''; ?>">
        <i class="bi bi-ticket-perforated-fill"></i>
        <span>Promo</span>
    </a>
    <a href="<?php echo page_url('lacak-pesanan.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'lacak-pesanan.php') ? 'active' : ''; ?>">
        <i class="bi bi-search"></i>
        <span>Lacak</span>
    </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>
<script src="<?php echo asset_url('js/main.js'); ?>"></script>
</body>

</html>