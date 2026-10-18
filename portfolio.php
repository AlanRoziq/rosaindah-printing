<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/portfolio-data.php';

$pdo = db();
$portfolios = [];
$error = false;
if ($pdo) {
    try {
        $portfolios = load_portfolios($pdo, null);
    } catch (Throwable $e) {
        $error = true;
    }
}

?>
<link rel="stylesheet" href="<?php echo asset_url('css/portfolio-page.css'); ?>?v=<?php echo filemtime(__DIR__ . '/assets/css/portfolio-page.css'); ?>">
<?php include __DIR__ . '/includes/header.php'; ?>
<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="portfolio-page">
    <div class="container">
        <div class="catalog-breadcrumb portfolio-page-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Portofolio</span>
        </div>

        <section class="portfolio-page-head">
            <div>
                <span class="portfolio-page-kicker">PORTOFOLIO KAMI</span>
                <h1>Hasil Cetak Rosa Indah Printing</h1>
                <p>Kumpulan hasil produksi yang pernah kami kerjakan untuk berbagai pelanggan.</p>
            </div>
        </section>

        <?php if ($error): ?>
            <div class="portfolio-empty">
                <i class="bi bi-exclamation-circle"></i>
                <h2>Portofolio belum dapat dimuat</h2>
                <p>Silakan coba lagi beberapa saat kemudian.</p>
            </div>
        <?php elseif (!$portfolios): ?>
            <div class="portfolio-empty">
                <i class="bi bi-images"></i>
                <h2>Belum ada portofolio</h2>
                <p>Portofolio hasil produksi akan tampil di halaman ini setelah ditambahkan Admin.</p>
            </div>
        <?php else: ?>
            <div class="portfolio-page-grid">
                <?php foreach ($portfolios as $item): ?>
                    <a class="portfolio-page-item" href="<?php echo page_url('detail-portofolio.php?id=' . (int)$item['id']); ?>" aria-label="Lihat portofolio <?php echo htmlspecialchars($item['product_name']); ?>">
                        <img src="<?php echo htmlspecialchars(portfolio_image_url($item['image'])); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
