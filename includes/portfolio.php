<?php
require_once __DIR__ . '/portfolio-data.php';

$portfolioItems = [];
$portfolioPdo = db();
if ($portfolioPdo) {
    try {
        $portfolioItems = load_portfolios($portfolioPdo, 8);
    } catch (Throwable $e) {
        $portfolioItems = [];
    }
}

// Fallback lama dipertahankan agar desain homepage tetap tampil.
// Jika Admin baru mengisi sebagian portofolio, kartu lama hanya menjadi pengisi
// sampai total 8 kartu sehingga layout homepage tidak berubah.
$portfolioFallback = [
    ['image'=>'assets/images/products/banner.png','product_name'=>'Banner Indoor'],
    ['image'=>'assets/images/products/spanduk.png','product_name'=>'Spanduk'],
    ['image'=>'assets/images/products/mug.png','product_name'=>'Mug Custom'],
    ['image'=>'assets/images/products/sablon.png','product_name'=>'Kaos DTF'],
    ['image'=>'assets/images/products/kartunama.png','product_name'=>'Kartu Nama'],
    ['image'=>'assets/images/products/kalender.png','product_name'=>'Kalender'],
    ['image'=>'assets/images/products/undnikah.jpeg','product_name'=>'Undangan'],
    ['image'=>'assets/images/products/lanyard.png','product_name'=>'Lanyard'],
];
if (count($portfolioItems) < 8) {
    $usedNames = array_map(static fn($item) => (string)($item['product_name'] ?? ''), $portfolioItems);
    foreach ($portfolioFallback as $fallback) {
        if (count($portfolioItems) >= 8) break;
        if (!in_array($fallback['product_name'], $usedNames, true)) {
            $portfolioItems[] = $fallback;
            $usedNames[] = $fallback['product_name'];
        }
    }
}
?>
<section class="portfolio">

    <div class="container">

        <div class="section-title">

            <span>Portofolio Kami</span>

            <h2>Hasil Cetak Terbaru</h2>

            <p>
                Sebagian hasil produksi terbaik Rosa Indah Printing
                yang telah dipercaya oleh berbagai pelanggan.
            </p>

        </div>

        <div class="portfolio-grid">

            <?php foreach ($portfolioItems as $item):
                $image = (string)($item['image'] ?? '');
                $imageUrl = str_starts_with(ltrim($image, '/'), 'uploads/')
                    ? page_url(ltrim($image, '/'))
                    : (str_starts_with(ltrim($image, '/'), 'assets/') ? page_url(ltrim($image, '/')) : asset_url('images/' . ltrim($image, '/')));
            ?>
            <div class="portfolio-item">
                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Portofolio'); ?>" loading="lazy">
                <div class="overlay">
                    <h4><?php echo htmlspecialchars($item['product_name'] ?? 'Portofolio'); ?></h4>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <div class="portfolio-button">

            <a href="<?php echo page_url('portfolio.php'); ?>" class="btn-portfolio">
                Lihat Semua Portofolio
            </a>

        </div>

    </div>

</section>