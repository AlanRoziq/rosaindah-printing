<?php
require_once __DIR__ . '/config/config.php';
$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
$pageTitle = 'Panduan Cara Pemesanan';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="py-5">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Cara Pemesanan</span>
        </div>

        <div class="section-title text-center mb-5">
            <span>Panduan Praktis</span>
            <h2>Cara Pemesanan di Rosa Indah Printing</h2>
            <p class="text-muted">Ikuti langkah sederhana ini untuk mencetak kebutuhan promosi dan dokumen Anda dengan cepat.</p>
        </div>

        <div class="row g-4 max-w-900 mx-auto mb-5">
            <div class="col-md-6">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">1</div>
                        <h5 class="fw-bold mb-0">Pilih Produk & Spesifikasi</h5>
                    </div>
                    <p class="text-muted">Buka halaman <a href="<?php echo page_url('produk.php'); ?>">Katalog Produk</a>, tentukan jenis cetakan (banner, kartu nama, undangan, dll.), dan masukkan jumlah kebutuhan Anda.</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">2</div>
                        <h5 class="fw-bold mb-0">Checkout & Dapatkan Order Code</h5>
                    </div>
                    <p class="text-muted">Masukkan data nama, nomor WhatsApp, dan alamat tujuan. Anda akan langsung mendapatkan nomor pesanan resmi (contoh: <code>RIP-2026...</code>).</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">3</div>
                        <h5 class="fw-bold mb-0">Konfirmasi File Desain & DP</h5>
                    </div>
                    <p class="text-muted">Kirimkan file desain siap cetak via WhatsApp atau upload bukti pembayaran di halaman Lacak Pesanan agar masuk antrian mesin produksi.</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 48px; height: 48px;">4</div>
                        <h5 class="fw-bold mb-0">Produksi & Pengiriman</h5>
                    </div>
                    <p class="text-muted">Pesanan dicetak dengan teliti, dilakukan quality check, dan dapat diambil langsung di workshop atau dikirimkan ke alamat Anda.</p>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="https://wa.me/<?php echo rawurlencode($waNumber); ?>" target="_blank" rel="noopener" class="btn btn-success btn-lg px-4 me-2">
                <i class="bi bi-whatsapp me-1"></i> Konsultasi via WhatsApp
            </a>
            <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-cart me-1"></i> Mulai Pesan
            </a>
        </div>
    </div>
</main>

<style>
.max-w-900 { max-width: 900px; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
