<?php
require_once __DIR__ . '/config/config.php';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main>
    <section class="page-section" style="padding:70px 0 90px;">
        <div class="container" style="max-width:1000px;">
            <div class="section-title text-center">
                <span>Hubungi Kami</span>
                <h1>Kontak Rosa Indah Printing</h1>
                <p>Silakan hubungi kami untuk konsultasi produk, harga, desain, dan pesanan.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                        <i class="bi bi-telephone-fill" style="font-size:30px;color:#d64a8a;"></i>
                        <h2 class="h5 mt-3">Telepon / WhatsApp</h2>
                        <p class="text-secondary mb-2">0823-3103-5371</p>
                        <a class="btn-primary d-inline-block" style="padding:10px 18px;" href="https://wa.me/6282331035371" target="_blank" rel="noopener">Chat WhatsApp</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                        <i class="bi bi-envelope-fill" style="font-size:30px;color:#d64a8a;"></i>
                        <h2 class="h5 mt-3">Email</h2>
                        <p class="text-secondary mb-0">rosaindahprinting@gmail.com</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                        <i class="bi bi-geo-alt-fill" style="font-size:30px;color:#d64a8a;"></i>
                        <h2 class="h5 mt-3">Alamat</h2>
                        <p class="text-secondary mb-0">regek-sambirejo-rejoso</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
