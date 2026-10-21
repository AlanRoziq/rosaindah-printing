<section class="featured-products">
    <div class="container">
        <div class="section-title">
            <div>
                <span>PRODUK TERLARIS</span>
                <h2>Produk Unggulan</h2>
                <p>Berbagai kebutuhan percetakan dengan kualitas terbaik dan harga terjangkau.</p>
            </div>
            <a href="<?php echo page_url('produk.php'); ?>" class="lihat-semua">Lihat Semua →</a>
        </div>

        <div class="product-grid">
            <?php
            require_once __DIR__ . '/products-data.php';
            $featured = array_slice($products, 0, 8);
            foreach ($featured as $item):
            ?>
                <?php include __DIR__ . '/components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
