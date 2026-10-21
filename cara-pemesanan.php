<?php
require_once __DIR__ . '/config/config.php';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main>
    <section class="how-it-works" id="cara-pemesanan" style="padding:70px 0 90px;">
        <div class="container">
            <div class="section-title text-center">
                <span>Proses Pemesanan</span>
                <h1>Cara Pemesanan</h1>
                <p>Hanya 4 langkah mudah untuk mencetak bersama <strong>Rosa Indah Printing</strong>.</p>
            </div>
            <div class="timeline">
                <div class="timeline-line"></div>
                <div class="step">
                    <div class="step-icon"><i class="bi bi-whatsapp"></i></div>
                    <div class="step-number">01</div>
                    <h4>Hubungi Kami</h4>
                    <p>Konsultasikan kebutuhan cetak melalui WhatsApp atau website.</p>
                </div>
                <div class="step">
                    <div class="step-icon"><i class="bi bi-palette"></i></div>
                    <div class="step-number">02</div>
                    <h4>Kirim Desain</h4>
                    <p>Upload desain Anda atau konsultasikan kebutuhan desain dengan tim kami.</p>
                </div>
                <div class="step">
                    <div class="step-icon"><i class="bi bi-credit-card"></i></div>
                    <div class="step-number">03</div>
                    <h4>Konfirmasi</h4>
                    <p>Cek produk, jumlah, harga, lalu lanjutkan proses pembayaran.</p>
                </div>
                <div class="step">
                    <div class="step-icon"><i class="bi bi-truck"></i></div>
                    <div class="step-number">04</div>
                    <h4>Cetak & Kirim</h4>
                    <p>Pesanan diproses, dicetak dengan baik, kemudian dikirim kepada Anda.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
