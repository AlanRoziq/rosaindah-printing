<?php
require_once __DIR__ . '/products-data.php';
$homePortfolios = array_slice(load_portfolios_from_db(), 0, 8);
?>
<section class="portfolio">
    <div class="container">
        <div class="section-title">
            <span>Portofolio Kami</span>
            <h2>Hasil Cetak Terbaru</h2>
            <p>Sebagian hasil produksi terbaik Rosa Indah Printing yang telah dipercaya pelanggan.</p>
        </div>

        <div class="portfolio-grid">
            <?php foreach ($homePortfolios as $item): ?>
                <a href="<?php echo page_url('detail-portofolio.php?id=' . (int)$item['id']); ?>" class="portfolio-card" data-aos="zoom-in" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy">
                    <div class="portfolio-overlay">
                        <span class="portfolio-tag"><i class="bi bi-patch-check-fill me-1"></i> Order Selesai</span>
                        <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                        <span class="portfolio-client"><i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($item['customer_name']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo page_url('portfolio.php'); ?>" class="catalog-primary-btn">Lihat Semua Portofolio</a>
        </div>
    </div>
</section>