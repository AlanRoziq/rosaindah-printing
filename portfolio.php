<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';

$portfolios = load_portfolios_from_db();

$pageTitle = 'Portofolio Hasil Cetak';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="portfolio-page py-4">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Portofolio</span>
        </div>

        <div class="section-title text-center mb-5">
            <span>Galeri Karya</span>
            <h2>Hasil Cetak Rosa Indah Printing</h2>
            <p class="text-muted">Dokumentasi nyata hasil produksi percetakan mulai dari banner, spanduk, mug souvenir, kartu nama, kalender, hingga kaos DTF.</p>
        </div>

        <div class="portfolio-grid">
            <?php foreach ($portfolios as $item): ?>
                <a href="<?php echo page_url('detail-portofolio.php?id=' . (int)$item['id']); ?>" class="portfolio-card" data-aos="fade-up" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy">
                    <div class="portfolio-overlay">
                        <span class="portfolio-tag"><i class="bi bi-patch-check-fill me-1"></i> Hasil Cetak Nyata</span>
                        <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                        <span class="portfolio-client"><i class="bi bi-person-fill me-1"></i> Dipesan oleh: <?php echo htmlspecialchars($item['customer_name']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
