<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/config/customer-auth.php';

$detailPdo = db();
if ($detailPdo) {
    try {
        customer_auth_init($detailPdo);
    } catch (Throwable $e) {
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$productId = trim($_GET['id'] ?? '');
$product = null;

foreach ($products as $item) {
    if ($item['id'] === $productId) {
        $product = $item;
        break;
    }
}

$relatedProducts = [];
if ($product) {
    foreach ($products as $item) {
        if ($item['id'] === $product['id']) continue;
        if (($item['category'] ?? '') === ($product['category'] ?? '')) {
            $relatedProducts[] = $item;
            if (count($relatedProducts) >= 4) break;
        }
    }
    if (count($relatedProducts) < 4) {
        foreach ($products as $item) {
            if ($item['id'] === $product['id']) continue;
            $alreadyIn = false;
            foreach ($relatedProducts as $rp) {
                if ($rp['id'] === $item['id']) {
                    $alreadyIn = true;
                    break;
                }
            }
            if (!$alreadyIn) {
                $relatedProducts[] = $item;
                if (count($relatedProducts) >= 4) break;
            }
        }
    }
}

$currentUser = function_exists('customer_logged_in') && customer_logged_in() ? customer_user() : null;
$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
$pageTitle = $product ? $product['name'] : 'Detail Produk';
include __DIR__ . '/includes/header.php';
?>

<style>
.btn-detail-cart:hover {
    background: linear-gradient(135deg, #ffe0ee 0%, #ffd0e3 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(225, 29, 100, 0.22) !important;
    color: #be124f !important;
}
.btn-detail-buynow:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(225, 29, 100, 0.45) !important;
    color: #ffffff !important;
    text-decoration: none !important;
}
.btn-detail-wa:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 211, 102, 0.38) !important;
    color: #ffffff !important;
    text-decoration: none !important;
    filter: brightness(1.05);
}
@media (max-width: 600px) {
    .detail-order-btn-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<?php if (!$product): ?>
<main class="detail-page" style="padding: 60px 0;">
    <div class="container">
        <div class="empty-products detail-empty text-center p-5 bg-white rounded-4 shadow-sm" style="max-width:600px; margin: 0 auto; border: 1px solid #f2dce7;">
            <i class="bi bi-box display-3" style="color: #e11d64;"></i>
            <h2 class="mt-3" style="color: #18243a; font-weight: 800;">Produk Tidak Ditemukan</h2>
            <p style="color: #7d737b; font-size: 14px;">Maaf, produk percetakan yang Anda cari belum tersedia atau telah dinonaktifkan.</p>
            <a href="<?php echo page_url('produk.php'); ?>" class="catalog-primary-btn" style="display:inline-flex; align-items:center; gap:8px; margin-top:10px;">
                <i class="bi bi-arrow-left"></i> Kembali ke Katalog Produk
            </a>
        </div>
    </div>
</main>
<?php else: ?>
<?php
    $startingPrice = !empty($product['pricing']) ? product_unit_price($product, 1) : (float)$product['price'];
    $shortDesc = (string)($product['short_description'] ?? $product['description'] ?? '');
    $fullDesc = (string)($product['full_description'] ?? ((!empty($product['description']) && $product['description'] !== $shortDesc) ? $product['description'] : ''));
    $specifications = (string)($product['specifications'] ?? '');
?>
<main class="detail-page">
    <div class="container">
        <!-- Breadcrumb Navigation -->
        <div class="catalog-breadcrumb detail-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('produk.php'); ?>">Produk</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('produk.php?kategori=' . urlencode($product['category'])); ?>"><?php echo htmlspecialchars($product['category']); ?></a>
            <i class="bi bi-chevron-right"></i>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Main Product Detail Card -->
        <section class="product-detail-card">
            <!-- Left Column: Gallery & Trust Badges -->
            <div class="detail-gallery">
                <button type="button" class="detail-main-image" data-product-lightbox-open
                        aria-label="Lihat gambar <?php echo htmlspecialchars($product['name']); ?> dalam ukuran penuh">
                    <img src="<?php echo product_image_url($product['image']); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         draggable="false">
                    <span class="detail-image-hint"><i class="bi bi-zoom-in"></i> Klik untuk melihat lebih besar</span>
                </button>

                <div class="detail-mini-note" style="margin-top: 14px;">
                    <i class="bi bi-shield-check"></i>
                    <span>Hasil cetak beresolusi tinggi, warna tajam & dikerjakan rapi oleh teknisi berpengalaman.</span>
                </div>

                <!-- Trust Guarantee Strip -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px;">
                    <div style="background: #ffffff; border: 1px solid #f0dce6; border-radius: 12px; padding: 10px 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-patch-check-fill" style="color: #e11d64; font-size: 18px;"></i>
                        <span style="font-size: 11.5px; color: #4e4850; font-weight: 700;">Garansi Hasil Cetak</span>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #f0dce6; border-radius: 12px; padding: 10px 12px; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-truck" style="color: #e11d64; font-size: 18px;"></i>
                        <span style="font-size: 11.5px; color: #4e4850; font-weight: 700;">Pengiriman & Pickup Aman</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Product Info & Order Options -->
            <div class="detail-content">
                <span class="detail-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="detail-rating">
                    <span style="color: #ff9800; font-weight: 700;"><i class="bi bi-star-fill"></i> 5.0 (Rating Tertinggi)</span>
                    <span>•</span>
                    <span>Melayani Satuan & Partai Grosir</span>
                </div>

                <!-- Price Box & Tiered Discounts -->
                <div class="detail-price">
                    <small>Mulai dari</small>
                    <div class="detail-price-main">
                        <strong id="currentUnitPrice"><?php echo format_rupiah($startingPrice); ?></strong>
                        <span>/ <?php echo htmlspecialchars($product['unit'] ?: 'pcs'); ?></span>
                    </div>

                    <?php if (!empty($product['pricing'])): ?>
                        <small class="detail-price-tier-note" style="color: #796f78; font-weight: 600; margin-top: 10px; display: block;">
                            <i class="bi bi-tags-fill" style="color: #e11d64;"></i> Harga bertingkat grosir otomatis diterapkan saat checkout:
                        </small>
                        <div class="detail-price-tiers">
                            <?php foreach ($product['pricing'] as $tier): ?>
                                <div class="detail-price-tier" style="background: #ffffff; border: 1px solid #f2dce7; border-radius: 8px; padding: 5px 9px;">
                                    <span style="font-weight: 600;"><?php echo (int)$tier['min_qty']; ?><?php echo !empty($tier['max_qty']) ? '-' . (int)$tier['max_qty'] : '+'; ?> <?php echo htmlspecialchars($product['unit']); ?>:</span>
                                    <b style="color: #e11d64;"><?php echo format_rupiah($tier['price']); ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 1. DESKRIPSI SINGKAT -->
                <?php if (trim($shortDesc) !== ''): ?>
                    <div class="detail-short-desc" style="margin: 18px 0; padding: 14px 16px; background: #fffcfd; border-left: 3px solid #e11d64; border-radius: 4px 12px 12px 4px; color: #524a51; font-size: 13.5px; line-height: 1.7;">
                        <strong style="color: #18243a; display: block; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bi bi-text-left" style="color: #e11d64;"></i> Ringkasan Produk:
                        </strong>
                        <?php echo nl2br(htmlspecialchars($shortDesc)); ?>
                    </div>
                <?php endif; ?>

                <!-- SPESIFIKASI PRODUK -->
                <?php if (trim($specifications) !== ''): ?>
                    <div class="detail-specs-card" style="margin: 18px 0; padding: 16px 18px; border: 1px solid #f2dce7; border-radius: 14px; background: #fff8fb;">
                        <h4 style="margin: 0 0 10px; font-size: 13.5px; font-weight: 800; color: #18243a; display: flex; align-items: center; gap: 7px;">
                            <i class="bi bi-card-checklist" style="color: #e11d64; font-size: 16px;"></i> Spesifikasi Produk:
                        </h4>
                        <div style="font-size: 13px; color: #5f545e; white-space: pre-line; line-height: 1.75;">
                            <?php echo htmlspecialchars($specifications); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 2. DESKRIPSI LENGKAP -->
                <?php if (trim($fullDesc) !== ''): ?>
                    <div class="detail-description-wrap" style="margin: 20px 0;">
                        <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 800; color: #18243a; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-file-earmark-text" style="color: #e11d64;"></i> Deskripsi Lengkap:
                        </h4>
                        <div class="detail-description is-collapsed" id="productDescription" style="white-space: pre-line; font-size: 13.5px; line-height: 1.8; color: #665f66;">
                            <?php echo htmlspecialchars($fullDesc); ?>
                        </div>
                        <button type="button" class="detail-description-toggle" id="descriptionToggle" aria-expanded="false" aria-controls="productDescription">
                            <span>Selengkapnya</span> <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- KEUNGGULAN PRODUK -->
                <?php if (!empty($product['features'])): ?>
                    <div class="detail-features" style="margin-top: 22px;">
                        <h3>Keunggulan Produk</h3>
                        <ul>
                            <?php foreach ($product['features'] as $feat): ?>
                                <li><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($feat); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- MEMBER BENEFIT BOX -->
                <div class="member-benefit-box" style="margin: 22px 0 18px; padding: 15px 16px; border: 1px solid #f2d1e0; border-radius: 14px; background: linear-gradient(135deg, #fff5fa, #ffffff); display: flex; gap: 12px; align-items: flex-start;">
                    <div style="width: 38px; height: 38px; flex: 0 0 38px; border-radius: 50%; display: grid; place-items: center; background: #e11d64; color: #fff;">
                        <i class="bi bi-gift"></i>
                    </div>
                    <div style="flex: 1;">
                        <strong style="display: block; color: #18243a; margin-bottom: 3px; font-size: 13.5px;">🎁 Keuntungan Akun Member</strong>
                        <span style="display: block; color: #756d75; font-size: 12px; line-height: 1.55;">Daftar atau login sebagai Member untuk mendapatkan <b>poin transaksi, potongan voucher, dan promo spesial</b> dari Rosa Indah Printing.</span>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                            <?php if ($currentUser): ?>
                                <span style="font-size: 12px; color: #287a54; font-weight: 700;"><i class="bi bi-check-circle-fill"></i> Akun Anda: <b><?php echo htmlspecialchars($currentUser['name']); ?></b> (Member Aktif)</span>
                            <?php else: ?>
                                <a href="<?php echo page_url('daftar.php'); ?>" style="color: #e11d64; font-size: 12px; font-weight: 800; text-decoration: none;">Daftar Member</a>
                                <span style="color: #d0c6cd;">•</span>
                                <a href="<?php echo page_url('login.php'); ?>" style="color: #18243a; font-size: 12px; font-weight: 800; text-decoration: none;">Login Member</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- INTERACTIVE ORDER & LIVE CALCULATOR BOX -->
                <div style="background: #ffffff; border: 1.5px solid #f2dce7; border-radius: 20px; padding: 22px; box-shadow: 0 12px 35px rgba(225, 29, 100, 0.06); margin-top: 24px;">
                    <form action="<?php echo page_url('cart.php'); ?>" method="POST" id="orderCalculatorForm">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                            <div>
                                <label for="quantityInput" style="display: block; font-size: 13px; font-weight: 800; color: #18243a; margin-bottom: 6px;">
                                    Jumlah Pemesanan (<?php echo htmlspecialchars($product['unit'] ?: 'pcs'); ?>):
                                </label>
                                <div style="display: inline-flex; align-items: center; border: 1.5px solid #ecdbe4; border-radius: 12px; overflow: hidden; background: #fff;">
                                    <button type="button" id="qtyMinus" style="border: 0; background: #faf4f7; padding: 9px 15px; font-size: 16px; font-weight: 700; color: #e11d64; cursor: pointer; transition: background 0.15s;">−</button>
                                    <input type="number" id="quantityInput" name="quantity" value="1" min="1" max="100000" style="width: 72px; text-align: center; border: 0; font-size: 15px; font-weight: 800; color: #18243a; outline: none; padding: 8px 0;">
                                    <button type="button" id="qtyPlus" style="border: 0; background: #faf4f7; padding: 9px 15px; font-size: 16px; font-weight: 700; color: #e11d64; cursor: pointer; transition: background 0.15s;">+</button>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <small style="display: block; font-size: 11.5px; color: #887d86; font-weight: 600;">Total Estimasi Harga:</small>
                                <strong id="totalEstimateDisplay" style="font-size: 26px; font-weight: 800; color: #e11d64; font-variant-numeric: tabular-nums;">
                                    <?php echo format_rupiah($startingPrice); ?>
                                </strong>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="detail-order-btn-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <!-- Tombol 1: Tambah ke Keranjang -->
                            <button type="submit" class="btn-detail-cart" style="
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                gap: 9px;
                                border: 1.5px solid #f29bbd;
                                background: linear-gradient(135deg, #fff2f7 0%, #ffe1ee 100%);
                                color: #e11d64;
                                padding: 13px 20px;
                                border-radius: 999px;
                                font-weight: 800;
                                font-size: 14px;
                                cursor: pointer;
                                transition: all 0.25s ease;
                                box-shadow: 0 4px 14px rgba(225, 29, 100, 0.12);
                                width: 100%;
                            ">
                                <i class="bi bi-cart-plus-fill" style="font-size: 17px;"></i> Tambah ke Keranjang
                            </button>

                            <!-- Tombol 2: Pesan Langsung -->
                            <a id="buyNowBtn" href="<?php echo page_url('checkout.php?buy=' . urlencode($product['id']) . '&qty=1'); ?>" class="btn-detail-buynow" style="
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                gap: 9px;
                                border: 0;
                                background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
                                color: #ffffff !important;
                                padding: 13px 22px;
                                border-radius: 999px;
                                text-decoration: none !important;
                                font-weight: 800;
                                font-size: 14px;
                                cursor: pointer;
                                box-shadow: 0 8px 24px rgba(225, 29, 100, 0.35);
                                transition: all 0.25s ease;
                                width: 100%;
                            ">
                                <i class="bi bi-lightning-charge-fill" style="color: #fff; font-size: 16px;"></i> Pesan Langsung
                            </a>
                        </div>

                        <!-- Tombol 3: WhatsApp Consultation Button -->
                        <div style="margin-top: 12px;">
                            <a id="waOrderBtn" href="https://wa.me/<?php echo rawurlencode($waNumber); ?>?text=<?php echo rawurlencode('Halo Rosa Indah Printing, saya ingin konsultasi produk ' . $product['name']); ?>" target="_blank" rel="noopener" class="btn-detail-wa" style="
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 8px;
                                width: 100%;
                                box-sizing: border-box;
                                background: linear-gradient(90deg, #25d366 0%, #128c7e 100%);
                                color: #ffffff !important;
                                text-decoration: none !important;
                                padding: 12px 18px;
                                border-radius: 999px;
                                font-size: 13.5px;
                                font-weight: 700;
                                box-shadow: 0 4px 14px rgba(37, 211, 102, 0.25);
                                transition: all 0.2s ease;
                            ">
                                <i class="bi bi-whatsapp" style="font-size: 17px;"></i> Konsultasi Desain via WhatsApp
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Informasi Pemesanan Section -->
        <section class="detail-info-section">
            <div class="detail-info-title">
                <span class="small-title" style="color: #e11d64; font-weight: 800; font-size: 12px; letter-spacing: 1px; display: block; text-transform: uppercase;">INFORMASI PEMESANAN</span>
                <h2>Pesan Sesuai Kebutuhan Anda</h2>
            </div>
            <div class="detail-info-grid">
                <div>
                    <i class="bi bi-pencil-square"></i>
                    <h3>Desain Custom</h3>
                    <p>Kirim desain Anda sendiri siap cetak atau konsultasikan konsep baru kepada tim desainer Rosa Indah Printing.</p>
                </div>
                <div>
                    <i class="bi bi-rulers"></i>
                    <h3>Ukuran Fleksibel</h3>
                    <p>Ukuran, panjang, ketebalan bahan, dan jumlah dapat disesuaikan tepat dengan kebutuhan Anda.</p>
                </div>
                <div>
                    <i class="bi bi-truck"></i>
                    <h3>Proses Cepat</h3>
                    <p>Pesanan diproses dengan teliti, warna akurat, pengerjaan rapi dan tepat waktu.</p>
                </div>
            </div>
        </section>

        <!-- Related Products Section -->
        <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <div class="catalog-heading" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
                    <div>
                        <span class="small-title" style="color: #e11d64; font-weight: 800; font-size: 12px; letter-spacing: 1px; display: block; text-transform: uppercase;">MUNGKIN ANDA SUKA</span>
                        <h2 style="color: #18243a; font-size: 26px; font-weight: 800; margin: 4px 0 0;">Produk Lainnya</h2>
                    </div>
                    <a href="<?php echo page_url('produk.php'); ?>" class="reset-filter" style="color: #e11d64; text-decoration: none; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                        Lihat Semua Katalog <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="catalog-grid related-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 18px;">
                    <?php foreach ($relatedProducts as $item): ?>
                        <div class="catalog-card" style="background: #ffffff; border: 1px solid #f2dce7; border-radius: 18px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                            <div class="catalog-card-image" style="height: 190px; background: #fff8fb; overflow: hidden;">
                                <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="catalog-card-body" style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 11px; font-weight: 700; color: #e11d64; background: #fff0f6; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                    <h3 style="font-size: 15px; font-weight: 800; color: #18243a; margin: 0 0 6px;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h3>
                                    <p style="font-size: 12px; color: #7d727b; margin: 0 0 12px; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; line-height: 1.5;">
                                        <?php echo htmlspecialchars($item['short_description'] ?? $item['description'] ?? ''); ?>
                                    </p>
                                </div>
                                <div class="catalog-card-bottom" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f6e8ef; padding-top: 10px; margin-top: auto;">
                                    <div>
                                        <small style="font-size: 10.5px; color: #948992; display: block;">Mulai</small>
                                        <strong style="color: #e11d64; font-size: 14.5px; font-weight: 800;"><?php echo format_rupiah($item['price']); ?></strong>
                                    </div>
                                    <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['id'])); ?>" class="catalog-secondary-btn" style="padding: 7px 13px; font-size: 12px; border-radius: 8px;">
                                        Detail <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<!-- Fullscreen Product Lightbox & Interactive Zoom -->
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

<script>
(function() {
    // 1. Description Accordion / Toggle
    const description = document.getElementById('productDescription');
    const toggle = document.getElementById('descriptionToggle');
    if (description && toggle) {
        toggle.addEventListener('click', function () {
            const expanded = description.classList.toggle('is-expanded');
            description.classList.toggle('is-collapsed', !expanded);
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            toggle.querySelector('span').textContent = expanded ? 'Sembunyikan' : 'Selengkapnya';
            const icon = toggle.querySelector('i');
            if (icon) icon.className = expanded ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        });
    }

    // 2. Lightbox Controller Engine
    const lightbox = document.getElementById("productLightbox");
    if (lightbox) {
        const stage = lightbox.querySelector("[data-lightbox-stage]");
        const image = document.getElementById("productLightboxImage");
        const openButton = document.querySelector("[data-product-lightbox-open]");
        const closeButtons = lightbox.querySelectorAll("[data-product-lightbox-close]");
        const zoomInButton = lightbox.querySelector("[data-lightbox-zoom-in]");
        const zoomOutButton = lightbox.querySelector("[data-lightbox-zoom-out]");
        const resetButton = lightbox.querySelector("[data-lightbox-reset]");
        const fullscreenButton = lightbox.querySelector("[data-lightbox-fullscreen]");
        const zoomLabel = lightbox.querySelector("[data-lightbox-zoom-label]");

        let scale = 1;
        let translateX = 0;
        let translateY = 0;
        let dragging = false;
        let dragPointerId = null;
        let startX = 0;
        let startY = 0;
        let startTranslateX = 0;
        let startTranslateY = 0;
        const pointers = new Map();
        let pinchStartDistance = 0;
        let pinchStartScale = 1;

        const MIN_SCALE = 1;
        const MAX_SCALE = 4;
        const STEP = 0.25;

        function clamp(val, min, max) {
            return Math.min(Math.max(val, min), max);
        }

        function setTransform() {
            scale = clamp(scale, MIN_SCALE, MAX_SCALE);
            if (scale === 1) {
                translateX = 0;
                translateY = 0;
            }
            if (image) {
                image.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${scale})`;
            }
            if (zoomLabel) zoomLabel.textContent = `${Math.round(scale * 100)}%`;
        }

        function resetZoom() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            setTransform();
        }

        function changeZoom(amount) {
            const oldScale = scale;
            scale = clamp(scale + amount, MIN_SCALE, MAX_SCALE);
            if (scale !== oldScale && scale === 1) {
                translateX = 0;
                translateY = 0;
            }
            setTransform();
        }

        function openLightbox() {
            lightbox.classList.add("is-open");
            lightbox.setAttribute("aria-hidden", "false");
            document.body.classList.add("lightbox-open");
            resetZoom();
        }

        function closeLightbox() {
            lightbox.classList.remove("is-open");
            lightbox.setAttribute("aria-hidden", "true");
            document.body.classList.remove("lightbox-open");
            resetZoom();
            pointers.clear();
            dragging = false;
            dragPointerId = null;
            if (stage) stage.classList.remove("is-dragging");
            if (document.fullscreenElement) {
                document.exitFullscreen?.().catch(() => {});
            }
        }

        if (openButton) openButton.addEventListener("click", openLightbox);
        closeButtons.forEach(btn => btn.addEventListener("click", closeLightbox));
        if (zoomInButton) zoomInButton.addEventListener("click", () => changeZoom(STEP));
        if (zoomOutButton) zoomOutButton.addEventListener("click", () => changeZoom(-STEP));
        if (resetButton) resetButton.addEventListener("click", resetZoom);

        if (fullscreenButton) {
            fullscreenButton.addEventListener("click", async () => {
                try {
                    if (!document.fullscreenElement) {
                        await lightbox.requestFullscreen?.();
                    } else {
                        await document.exitFullscreen?.();
                    }
                } catch (e) {}
            });
        }

        if (stage) {
            stage.addEventListener("wheel", (event) => {
                event.preventDefault();
                changeZoom(event.deltaY < 0 ? STEP : -STEP);
            }, { passive: false });

            stage.addEventListener("pointerdown", (event) => {
                pointers.set(event.pointerId, event);
                stage.setPointerCapture?.(event.pointerId);

                if (pointers.size === 2) {
                    const [a, b] = [...pointers.values()];
                    pinchStartDistance = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                    pinchStartScale = scale;
                    dragging = false;
                    stage.classList.remove("is-dragging");
                    return;
                }

                if (event.pointerType === "mouse" && event.button !== 0) return;
                if (scale <= 1) return;

                dragging = true;
                dragPointerId = event.pointerId;
                startX = event.clientX;
                startY = event.clientY;
                startTranslateX = translateX;
                startTranslateY = translateY;
                stage.classList.add("is-dragging");
            });

            stage.addEventListener("pointermove", (event) => {
                if (pointers.has(event.pointerId)) {
                    pointers.set(event.pointerId, event);
                }

                if (pointers.size === 2) {
                    const [a, b] = [...pointers.values()];
                    const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
                    if (!pinchStartDistance) return;
                    scale = clamp(pinchStartScale * (dist / pinchStartDistance), MIN_SCALE, MAX_SCALE);
                    setTransform();
                    return;
                }

                if (!dragging || dragPointerId !== event.pointerId || scale <= 1) return;
                translateX = startTranslateX + (event.clientX - startX);
                translateY = startTranslateY + (event.clientY - startY);
                setTransform();
            });

            function endPointer(event) {
                pointers.delete(event.pointerId);
                if (pointers.size < 2) pinchStartDistance = 0;
                if (dragPointerId === event.pointerId) {
                    dragging = false;
                    dragPointerId = null;
                    stage.classList.remove("is-dragging");
                }
            }

            stage.addEventListener("pointerup", endPointer);
            stage.addEventListener("pointercancel", endPointer);
            stage.addEventListener("pointerleave", (event) => {
                if (event.pointerType === "mouse" && dragging) endPointer(event);
            });

            stage.addEventListener("dblclick", () => {
                if (scale > 1) resetZoom();
                else scale = 2;
                setTransform();
            });
        }

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && lightbox.classList.contains("is-open")) {
                closeLightbox();
            }
        });
    }

    // 3. Dynamic Price Calculation
    const pricingTiers = <?php echo json_encode(!empty($product['pricing']) ? $product['pricing'] : [], JSON_UNESCAPED_UNICODE); ?>;
    const basePrice = <?php echo (float)($product['price'] ?? 0); ?>;
    const unitName = <?php echo json_encode($product['unit'] ?: 'pcs', JSON_UNESCAPED_UNICODE); ?>;
    const prodName = <?php echo json_encode($product['name'], JSON_UNESCAPED_UNICODE); ?>;
    const prodId = <?php echo json_encode($product['id'], JSON_UNESCAPED_UNICODE); ?>;
    const waNumber = <?php echo json_encode($waNumber, JSON_UNESCAPED_UNICODE); ?>;
    const checkoutBaseUrl = <?php echo json_encode(page_url('checkout.php'), JSON_UNESCAPED_UNICODE); ?>;

    function computeUnitPrice(qty) {
        if (!pricingTiers || pricingTiers.length === 0) return basePrice;
        for (const tier of pricingTiers) {
            const min = tier.min_qty || 1;
            const max = tier.max_qty;
            if (qty >= min && (max === null || max === undefined || qty <= max)) {
                return Number(tier.price);
            }
        }
        return basePrice;
    }

    function formatRupiah(num) {
        return 'Rp' + Number(num || 0).toLocaleString('id-ID');
    }

    const qtyInput = document.getElementById('quantityInput');
    const minusBtn = document.getElementById('qtyMinus');
    const plusBtn = document.getElementById('qtyPlus');
    const unitPriceDisplay = document.getElementById('currentUnitPrice');
    const totalDisplay = document.getElementById('totalEstimateDisplay');
    const buyNowBtn = document.getElementById('buyNowBtn');
    const waBtn = document.getElementById('waOrderBtn');

    function updateCalculations() {
        if (!qtyInput) return;
        let qty = parseInt(qtyInput.value, 10);
        if (isNaN(qty) || qty < 1) qty = 1;
        qtyInput.value = qty;

        const unitPrice = computeUnitPrice(qty);
        const subtotal = unitPrice * qty;

        if (unitPriceDisplay) unitPriceDisplay.innerText = formatRupiah(unitPrice);
        if (totalDisplay) totalDisplay.innerText = formatRupiah(subtotal);

        if (buyNowBtn) {
            buyNowBtn.href = `${checkoutBaseUrl}?buy=${encodeURIComponent(prodId)}&qty=${qty}`;
        }

        if (waBtn) {
            const msg = `Halo Rosa Indah Printing, saya tertarik dengan produk:\n• Produk: ${prodName}\n• Jumlah: ${qty} ${unitName}\n• Estimasi Harga: ${formatRupiah(subtotal)}\n\nBisa dibantu proses pemesanannya?`;
            waBtn.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(msg)}`;
        }
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', updateCalculations);
        if (minusBtn) minusBtn.addEventListener('click', () => {
            let v = parseInt(qtyInput.value, 10) || 1;
            if (v > 1) { qtyInput.value = v - 1; updateCalculations(); }
        });
        if (plusBtn) plusBtn.addEventListener('click', () => {
            let v = parseInt(qtyInput.value, 10) || 1;
            qtyInput.value = v + 1; updateCalculations();
        });
        updateCalculations();
    }
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
