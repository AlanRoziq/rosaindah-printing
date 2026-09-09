<?php
if (!isset($item)) {
    return;
}
$detailUrl = page_url('detail-produk.php?id=' . urlencode($item['id']));
?>
<article class="catalog-card">
    <a href="<?php echo $detailUrl; ?>" class="catalog-card-image">
        <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
        <span class="catalog-badge"><?php echo htmlspecialchars($item['category']); ?></span>
    </a>
    <div class="catalog-card-body">
        <div class="catalog-rating"><i class="bi bi-star-fill"></i> 5.0 <span>•</span> Produk custom</div>
        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
        <p><?php echo htmlspecialchars($item['description']); ?></p>
        <div class="catalog-card-bottom">
            <div>
                <small>Mulai dari</small>
                <strong>Rp<?php echo number_format($item['price'], 0, ',', '.'); ?> <em>/ <?php echo htmlspecialchars($item['unit']); ?></em></strong>
            </div>
            <a href="<?php echo $detailUrl; ?>" class="catalog-arrow" aria-label="Lihat detail">
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</article>
