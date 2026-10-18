<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/portfolio-data.php';

$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$item = null;
if ($pdo && $id > 0) {
    try {
        ensure_portfolio_table($pdo);
        $stmt = $pdo->prepare('SELECT id,image,product_name,price,quantity,customer_name,created_at FROM portfolios WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        $item = null;
    }
}

?>
<link rel="stylesheet" href="<?php echo asset_url('css/portfolio-page.css'); ?>?v=<?php echo filemtime(__DIR__ . '/assets/css/portfolio-page.css'); ?>">
<?php include __DIR__ . '/includes/header.php'; ?>
<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="portfolio-detail-page">
    <div class="container">
        <div class="catalog-breadcrumb portfolio-page-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('portfolio.php'); ?>">Portofolio</a>
            <?php if ($item): ?>
                <i class="bi bi-chevron-right"></i>
                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$item): ?>
            <div class="portfolio-empty">
                <i class="bi bi-images"></i>
                <h2>Portofolio tidak ditemukan</h2>
                <p>Portofolio yang Anda cari belum tersedia atau sudah dihapus.</p>
                <a class="portfolio-back-btn" href="<?php echo page_url('portfolio.php'); ?>">Kembali ke Portofolio</a>
            </div>
        <?php else: ?>
            <section class="portfolio-detail-card">
                <div class="portfolio-detail-image-wrap">
                    <img class="portfolio-detail-image" src="<?php echo htmlspecialchars(portfolio_image_url($item['image'])); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                </div>
                <div class="portfolio-detail-info">
                    <span class="portfolio-page-kicker">PORTOFOLIO ROSA INDAH PRINTING</span>
                    <h1><?php echo htmlspecialchars($item['product_name']); ?></h1>
                    <div class="portfolio-detail-list">
                        <div><span>Harga</span><strong>Rp<?php echo number_format((float)$item['price'], 0, ',', '.'); ?></strong></div>
                        <div><span>Jumlah Pesanan</span><strong><?php echo number_format((int)$item['quantity'], 0, ',', '.'); ?> pcs</strong></div>
                        <div><span>Nama Pelanggan</span><strong><?php echo htmlspecialchars($item['customer_name']); ?></strong></div>
                    </div>
                    <a class="portfolio-back-btn" href="<?php echo page_url('portfolio.php'); ?>"><i class="bi bi-arrow-left"></i> Kembali ke Semua Portofolio</a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
