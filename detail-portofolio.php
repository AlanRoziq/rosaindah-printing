<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$portfolios = load_portfolios_from_db();

$id = (int)($_GET['id'] ?? ($portfolios[0]['id'] ?? 1));
$item = $portfolios[0] ?? null;
foreach ($portfolios as $p) {
    if ((int)$p['id'] === $id) {
        $item = $p;
        break;
    }
}

$otherPortfolios = [];
if ($item) {
    foreach ($portfolios as $p) {
        if ((int)$p['id'] !== (int)$item['id']) {
            $otherPortfolios[] = $p;
            if (count($otherPortfolios) >= 4) break;
        }
    }
}

$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
$pageTitle = $item ? ($item['product_name'] . ' - Detail Portofolio') : 'Detail Portofolio';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="py-4 py-md-5 bg-light-subtle">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-4">
            <a href="<?php echo page_url('index.php'); ?>"><i class="bi bi-house-door me-1"></i> Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('portfolio.php'); ?>">Portofolio</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($item['product_name']); ?></span>
        </div>

        <?php if ($item): ?>
            <div class="card p-3 p-md-5 rounded-4 shadow-sm border-0 mb-5 bg-white">
                <div class="row g-4 align-items-center">
                    <!-- Image Preview -->
                    <div class="col-lg-6">
                        <div class="portfolio-detail-img-box text-center p-3 rounded-4 position-relative" style="background: linear-gradient(135deg, #fff5f8 0%, #fff 100%); border: 1.5px solid #fedfe9;">
                            <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm" style="background: #e11d64; font-size: 11.5px;">
                                <i class="bi bi-camera-fill me-1"></i> 100% Foto Hasil Asli
                            </span>
                            <span class="badge position-absolute top-0 end-0 m-3 px-2 py-1 rounded-pill bg-success-subtle text-success fw-semibold" style="font-size: 11px;">
                                <i class="bi bi-patch-check-fill me-1"></i> Order Selesai
                            </span>
                            <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-fluid rounded-3" style="max-height: 440px; width: 100%; object-fit: contain;">
                        </div>
                    </div>

                    <!-- Details Content -->
                    <div class="col-lg-6">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: #ffe4ed; color: #be124f; font-size: 11.5px; font-weight: 700;">
                            <i class="bi bi-award-fill"></i> Dokumentasi Percetakan
                        </div>
                        <h1 class="h2 fw-bold text-dark mb-2"><?php echo htmlspecialchars($item['product_name']); ?></h1>
                        <p class="text-muted mb-4" style="font-size: 13.5px; line-height: 1.6;">
                            Dokumentasi riil pesanan cetak pelanggan yang diproduksi di workshop <strong>Rosa Indah Printing</strong>. Dicetak menggunakan mesin beresolusi tinggi dengan kalibrasi warna akurat dan finishing rapi siap pakai.
                        </p>

                        <!-- Specification Table / Info Grid -->
                        <div class="p-3 rounded-3 mb-4" style="background: #faf8f9; border: 1px solid #f3e2ea;">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary" style="font-size: 13px;"><i class="bi bi-person-fill text-rosa me-1"></i> Pemesan / Klien</span>
                                <span class="fw-bold text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($item['customer_name']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary" style="font-size: 13px;"><i class="bi bi-box-seam text-rosa me-1"></i> Jumlah Cetak</span>
                                <span class="fw-bold text-dark" style="font-size: 13px;"><?php echo (int)($item['quantity'] ?? 1); ?> pcs / paket</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary" style="font-size: 13px;"><i class="bi bi-tag-fill text-rosa me-1"></i> Nilai Transaksi</span>
                                <span class="fw-bold" style="color: #e11d64; font-size: 14px;"><?php echo format_rupiah($item['price']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary" style="font-size: 13px;"><i class="bi bi-shield-check text-success me-1"></i> Standar Kualitas</span>
                                <span class="badge bg-success-subtle text-success px-2 py-1" style="font-size: 11px;">Warna Akurat & QC Passed</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-secondary" style="font-size: 13px;"><i class="bi bi-clock-history text-rosa me-1"></i> Waktu Pengerjaan</span>
                                <span class="fw-semibold text-dark" style="font-size: 13px;">Proses Cepat (1-2 Hari)</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="https://wa.me/<?php echo rawurlencode($waNumber); ?>?text=Halo%20Rosa%20Indah%20Printing,%20saya%20melihat%20hasil%20cetak%20*<?php echo rawurlencode($item['product_name']); ?>*%20di%20website%20dan%20ingin%20konsultasi%20pesan%20seperti%20ini." target="_blank" rel="noopener" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="bi bi-whatsapp fs-5"></i> Konsultasi Cetak Ini via WhatsApp
                            </a>
                            <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                <i class="bi bi-cart-plus"></i> Pesan Produk Serupa
                            </a>
                        </div>
                        <div>
                            <a href="<?php echo page_url('portfolio.php'); ?>" class="text-decoration-none text-muted fw-semibold small d-inline-flex align-items-center gap-1">
                                <i class="bi bi-arrow-left"></i> Kembali ke Galeri Portofolio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hasil Cetak & Portofolio Lainnya -->
        <?php if (!empty($otherPortfolios)): ?>
            <div class="mt-5">
                <div class="section-title d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <span class="small-title">KARYA TERBARU</span>
                        <h2 class="h3 fw-bold mb-0">Hasil Cetak Lainnya</h2>
                    </div>
                    <a href="<?php echo page_url('portfolio.php'); ?>" class="lihat-semua text-decoration-none fw-bold" style="color: #e11d64;">
                        Lihat Semua Portofolio &rarr;
                    </a>
                </div>

                <div class="portfolio-grid">
                    <?php foreach ($otherPortfolios as $p): ?>
                        <a href="<?php echo page_url('detail-portofolio.php?id=' . (int)$p['id']); ?>" class="portfolio-card" data-aos="fade-up" title="<?php echo htmlspecialchars($p['product_name']); ?>">
                            <img src="<?php echo product_image_url($p['image']); ?>" alt="<?php echo htmlspecialchars($p['product_name']); ?>" loading="lazy">
                            <div class="portfolio-overlay">
                                <span class="portfolio-tag"><i class="bi bi-patch-check-fill me-1"></i> Order Selesai</span>
                                <h5><?php echo htmlspecialchars($p['product_name']); ?></h5>
                                <span class="portfolio-client"><i class="bi bi-person-fill me-1"></i> <?php echo htmlspecialchars($p['customer_name']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
