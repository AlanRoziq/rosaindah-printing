<footer class="bg-dark text-white text-center p-4 mt-5">
    <div class="container">
        <div class="mb-3 text-center">
            <h5 class="text-white mb-1 fw-bold">Rosa Indah Printing</h5>
            <small class="text-secondary">Cetak Ide Jadi Kenyataan • Solusi Percetakan Online Terlengkap &amp; Cepat</small>
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
<!-- MOBILE APP BOTTOM NAVIGATION BAR (5 MENU: Beranda, Produk, Keranjang, Lacak, Profil) -->
<nav class="mobile-bottom-nav d-md-none" aria-label="Navigasi Menu Mobile">
    <a href="<?php echo page_url(''); ?>" class="mobile-nav-item <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Beranda</span>
    </a>
    <a href="<?php echo page_url('produk.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'produk.php' || $currentScript === 'detail-produk.php') ? 'active' : ''; ?>">
        <i class="bi bi-grid-fill"></i>
        <span>Produk</span>
    </a>

    <!-- Menu Tengah Bulat Berwarna Primary: Keranjang -->
    <a href="<?php echo page_url('cart.php'); ?>" class="mobile-nav-center <?php echo ($currentScript === 'cart.php' || $currentScript === 'checkout.php') ? 'active' : ''; ?>" aria-label="Keranjang">
        <div class="center-bubble">
            <i class="bi bi-cart3"></i>
            <?php if ($cartCount > 0): ?>
                <span class="center-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </div>
        <span class="center-label">Keranjang</span>
    </a>

    <a href="<?php echo page_url('lacak-pesanan.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'lacak-pesanan.php') ? 'active' : ''; ?>">
        <i class="bi bi-search"></i>
        <span>Lacak</span>
    </a>
    <a href="<?php echo page_url('akun.php'); ?>" class="mobile-nav-item <?php echo ($currentScript === 'akun.php' || $currentScript === 'login.php' || $currentScript === 'daftar.php') ? 'active' : ''; ?>">
        <i class="bi bi-person-fill"></i>
        <span>Profil</span>
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