<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/config/customer-auth.php';
$detailPdo = db(); if ($detailPdo) { try { customer_auth_init($detailPdo); } catch (Throwable $e) {} }

$productId = trim($_GET['id'] ?? '');
$product = null;

foreach ($products as $item) {
    if ($item['id'] === $productId) {
        $product = $item;
        break;
    }
}

include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<?php if (!$product): ?>
<main class="detail-page">
    <div class="container">
        <div class="empty-products detail-empty">
            <i class="bi bi-box"></i>
            <h1>Produk tidak ditemukan</h1>
            <p>Maaf, produk yang Anda cari belum tersedia.</p>
            <a href="<?php echo page_url('produk.php'); ?>" class="catalog-primary-btn">Kembali ke Produk</a>
        </div>
    </div>
</main>
<?php else: ?>
<main class="detail-page">
    <div class="container">
        <div class="catalog-breadcrumb detail-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('produk.php'); ?>">Produk</a>
            <i class="bi bi-chevron-right"></i>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <section class="product-detail-card">
            <div class="detail-gallery">
                <button type="button" class="detail-main-image" data-product-lightbox-open
                        aria-label="Lihat gambar <?php echo htmlspecialchars($product['name']); ?> dalam ukuran penuh">
                    <img src="<?php echo product_image_url($product['image']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         draggable="false">
                    <span class="detail-image-hint"><i class="bi bi-zoom-in"></i> Klik untuk melihat lebih besar</span>
                </button>
                <div class="detail-mini-note">
                    <i class="bi bi-shield-check"></i>
                    <span>Hasil cetak berkualitas & dikerjakan dengan rapi</span>
                </div>
            </div>

            <div class="detail-content">
                <span class="detail-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="detail-rating">
                    <span><i class="bi bi-star-fill"></i> 5.0</span>
                    <span>•</span>
                    <span>Custom sesuai kebutuhan</span>
                </div>
                <?php
                // Jika tersedia harga bertingkat, harga awal mengikuti tier jumlah 1.
                // Dengan begitu tampilan "Mulai dari" selalu sinkron dengan harga checkout.
                $startingPrice = !empty($product['pricing']) ? product_unit_price($product, 1) : (float)$product['price'];
                ?>
                <div class="detail-price">
                    <small>Mulai dari</small>
                    <div class="detail-price-main">
                        <strong>Rp<?php echo number_format($startingPrice, 0, ',', '.'); ?></strong>
                        <span>/ <?php echo htmlspecialchars($product['unit']); ?></span>
                    </div>
                    <?php if (!empty($product['pricing'])): ?>
                        <small class="detail-price-tier-note">Harga bertingkat tersedia berdasarkan jumlah pesanan.</small>
                        <div class="detail-price-tiers" aria-label="Daftar harga bertingkat">
                            <?php foreach ($product['pricing'] as $tier): ?>
                                <div class="detail-price-tier">
                                    <span><?php echo (int)$tier['min_qty']; ?><?php echo $tier['max_qty'] === null ? '+' : '-' . (int)$tier['max_qty']; ?></span>
                                    <b>Rp<?php echo number_format((float)$tier['price'], 0, ',', '.'); ?></b>
                                    <em>/ <?php echo htmlspecialchars($product['unit']); ?></em>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (trim((string)$product['description']) !== ''): ?>
                    <div class="detail-description-wrap">
                        <div class="detail-description is-collapsed" id="productDescription">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                        <button type="button" class="detail-description-toggle" id="descriptionToggle" aria-expanded="false" aria-controls="productDescription">
                            <span>Selengkapnya</span> <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="detail-features">
                    <h3>Keunggulan Produk</h3>
                    <ul>
                        <?php foreach ($product['features'] as $feature): ?>
                            <li><i class="bi bi-check-circle-fill"></i><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="member-benefit-box" style="margin:18px 0;padding:15px 16px;border:1px solid #f2d1e0;border-radius:14px;background:linear-gradient(135deg,#fff5fa,#fff);display:flex;gap:12px;align-items:flex-start;">
                    <div style="width:38px;height:38px;flex:0 0 38px;border-radius:50%;display:grid;place-items:center;background:#d64a8a;color:#fff;"><i class="bi bi-gift"></i></div>
                    <div style="flex:1"><strong style="display:block;color:#18243a;margin-bottom:4px;">🎁 Keuntungan Akun Member</strong><span style="display:block;color:#756d75;font-size:13px;line-height:1.55;">Daftar atau login sebagai Member untuk mendapatkan informasi <b>promo, voucher, dan diskon</b> dari Rosa Indah Printing.</span><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:9px;">
                        <?php if (function_exists('customer_logged_in') && customer_logged_in()): ?>
                            <span style="font-size:12px;color:#287a54;font-weight:800;"><i class="bi bi-check-circle-fill"></i> Anda sudah login sebagai Member</span>
                        <?php else: ?>
                            <a href="<?php echo page_url('daftar.php'); ?>" style="color:#d64a8a;font-size:12px;font-weight:900;text-decoration:none;">Daftar Member</a>
                            <a href="<?php echo page_url('login.php'); ?>" style="color:#18243a;font-size:12px;font-weight:900;text-decoration:none;">Login Member</a>
                        <?php endif; ?>
                    </div></div>
                </div>

                <div class="detail-order-box">
                    <div>
                        <strong>Butuh produk ini?</strong>
                        <span>Konsultasikan ukuran, desain, bahan, dan jumlah pesanan.</span>
                    </div>
                    <div class="detail-order-actions">
                        <form method="post" action="<?php echo page_url('cart.php'); ?>" class="detail-add-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                            <button type="submit" class="catalog-secondary-btn"><i class="bi bi-bag-plus"></i> Tambah ke Keranjang</button>
                        </form>
                        <a class="catalog-primary-btn"
                           href="<?php echo page_url('checkout.php?buy=' . urlencode($product['id'])); ?>">
                            <i class="bi bi-cart-check"></i> Pesan Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-info-section">
            <div class="detail-info-title">
                <span class="small-title">INFORMASI PEMESANAN</span>
                <h2>Pesan Sesuai Kebutuhan Anda</h2>
            </div>
            <div class="detail-info-grid">
                <div><i class="bi bi-pencil-square"></i><h3>Desain Custom</h3><p>Kirim desain sendiri atau konsultasikan konsep kepada kami.</p></div>
                <div><i class="bi bi-rulers"></i><h3>Ukuran Fleksibel</h3><p>Ukuran dan jumlah dapat disesuaikan dengan kebutuhan.</p></div>
                <div><i class="bi bi-truck"></i><h3>Proses Cepat</h3><p>Pesanan diproses dengan rapi dan sesuai kesepakatan.</p></div>
            </div>
        </section>

        <section class="related-products">
            <div class="catalog-heading">
                <div>
                    <span class="small-title">MUNGKIN ANDA SUKA</span>
                    <h2>Produk Lainnya</h2>
                </div>
                <a href="<?php echo page_url('produk.php'); ?>" class="reset-filter">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="catalog-grid related-grid">
                <?php
                $shown = 0;
                foreach ($products as $item) {
                    if ($item['id'] === $product['id'] || $shown >= 4) continue;
                    if ($item['category'] !== $product['category']) continue;
                    include __DIR__ . '/includes/components/product-card.php';
                    $shown++;
                }
                if ($shown === 0) {
                    foreach ($products as $item) {
                        if ($item['id'] === $product['id'] || $shown >= 4) continue;
                        include __DIR__ . '/includes/components/product-card.php';
                        $shown++;
                    }
                }
                ?>
            </div>
        </section>
    </div>
</main>
<?php endif; ?>

<?php if ($product): ?>
<div class="product-lightbox" id="productLightbox" aria-hidden="true">
    <div class="product-lightbox-backdrop" data-product-lightbox-close></div>
    <div class="product-lightbox-dialog" role="dialog" aria-modal="true" aria-label="Pratinjau gambar produk">
        <button type="button" class="product-lightbox-close" data-product-lightbox-close aria-label="Tutup pratinjau">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="product-lightbox-stage" data-lightbox-stage>
            <img id="productLightboxImage"
                 src="<?php echo product_image_url($product['image']); ?>"
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 draggable="false">
        </div>

        <div class="product-lightbox-toolbar" aria-label="Kontrol gambar">
            <button type="button" data-lightbox-zoom-out aria-label="Perkecil gambar"><i class="bi bi-dash-lg"></i></button>
            <button type="button" data-lightbox-reset aria-label="Atur ulang zoom"><span data-lightbox-zoom-label>100%</span></button>
            <button type="button" data-lightbox-zoom-in aria-label="Perbesar gambar"><i class="bi bi-plus-lg"></i></button>
            <button type="button" data-lightbox-fullscreen aria-label="Mode layar penuh"><i class="bi bi-arrows-fullscreen"></i></button>
        </div>

        <p class="product-lightbox-help">Scroll / tombol + − untuk zoom · Geser gambar saat diperbesar · HP: gunakan dua jari</p>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const description = document.getElementById('productDescription');
    const toggle = document.getElementById('descriptionToggle');
    if (!description || !toggle) return;

    toggle.addEventListener('click', function () {
        const expanded = description.classList.toggle('is-expanded');
        description.classList.toggle('is-collapsed', !expanded);
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        toggle.querySelector('span').textContent = expanded ? 'Sembunyikan' : 'Selengkapnya';
        const icon = toggle.querySelector('i');
        if (icon) icon.className = expanded ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
