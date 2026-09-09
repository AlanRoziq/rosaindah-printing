<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/product-categories.php';

$keyword = trim($_GET['q'] ?? '');
$category = trim($_GET['kategori'] ?? '');
$sortPrice = $_GET['harga'] ?? '';
if (!in_array($sortPrice, ['asc', 'desc'], true)) { $sortPrice = ''; }

$categories = rosa_product_categories();

$filteredProducts = array_values(array_filter($products, function ($item) use ($keyword, $category) {
    $matchCategory = $category === '' || $item['category'] === $category;
    $haystack = strtolower($item['name'] . ' ' . $item['category'] . ' ' . $item['description']);
    $matchKeyword = $keyword === '' || strpos($haystack, strtolower($keyword)) !== false;
    return $matchCategory && $matchKeyword;
}));

// Urutkan berdasarkan harga awal produk (jumlah 1), termasuk harga bertingkat.
if ($sortPrice !== '') {
    usort($filteredProducts, function ($a, $b) use ($sortPrice) {
        $priceA = product_unit_price($a, 1);
        $priceB = product_unit_price($b, 1);
        if ($priceA == $priceB) return strcasecmp((string)$a['name'], (string)$b['name']);
        return $sortPrice === 'asc' ? ($priceA <=> $priceB) : ($priceB <=> $priceA);
    });
}

// Pagination server-side agar selalu tampil dan tidak bergantung pada JavaScript.
// Desktop: 40 produk (sekitar 10 baris x 4 kolom).
// Mobile/tablet: 30 produk (sekitar 15 baris x 2 kolom).

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobileDevice = (bool)preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i', $userAgent);
$perPage = $isMobileDevice ? 30 : 40;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalProducts = count($filteredProducts);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$paginatedProducts = array_slice($filteredProducts, $offset, $perPage);

function product_page_url(int $page, string $sort, string $category = '', string $keyword = ''): string {
    $params = [];
    if ($category !== '') $params['kategori'] = $category;
    if ($keyword !== '') $params['q'] = $keyword;
    if ($sort !== '') $params['harga'] = $sort;
    if ($page > 1) $params['page'] = $page;
    $query = http_build_query($params);
    return page_url('produk.php' . ($query !== '' ? '?' . $query : ''));
}

function product_filter_url(string $sort, string $category = '', string $keyword = ''): string {
    $params = [];
    if ($category !== '') $params['kategori'] = $category;
    if ($keyword !== '') $params['q'] = $keyword;
    if ($sort !== '') $params['harga'] = $sort;
    $query = http_build_query($params);
    return page_url('produk.php' . ($query !== '' ? '?' . $query : ''));
}

include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="catalog-page product-catalog">
    <div class="container">
        <div class="catalog-breadcrumb product-catalog-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Produk</span>
        </div>
    </div>
    <section class="catalog-section" id="daftar-produk">
        <div class="container">
            <form class="catalog-toolbar product-toolbar" method="get" action="<?php echo page_url('produk.php'); ?>">
                <div class="catalog-search">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Cari nama produk, kategori, atau kebutuhan..."><button type="submit" class="search-submit">Cari</button>
                </div>
                <div class="catalog-sort">
                    <label for="kategori">Filter Kategori</label>
                    <div class="catalog-sort-row">
                        <select id="kategori" name="kategori" onchange="this.form.submit()">
                            <option value="">Semua kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="price-filter-trigger<?php echo $sortPrice !== '' ? ' active' : ''; ?>" aria-label="Filter harga" aria-expanded="false" title="Filter harga">
                            <i class="bi bi-sliders2-vertical"></i>
                        </button>
                        <div class="price-filter-menu" hidden>
                            <a href="<?php echo htmlspecialchars(product_filter_url('asc', $category, $keyword)); ?>"><i class="bi bi-sort-up"></i> Termurah → Termahal</a>
                            <a href="<?php echo htmlspecialchars(product_filter_url('desc', $category, $keyword)); ?>"><i class="bi bi-sort-down"></i> Termahal → Termurah</a>
                            <?php if ($sortPrice !== ''): ?>
                                <a class="price-filter-reset" href="<?php echo htmlspecialchars(product_filter_url('', $category, $keyword)); ?>"><i class="bi bi-x-circle"></i> Hapus filter harga</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" name="harga" value="<?php echo htmlspecialchars($sortPrice); ?>">
                </div>
            </form>

            <div class="category-filter-pills">
                <a class="category-pill <?php echo $category === '' ? 'active' : ''; ?>" href="<?php echo product_filter_url($sortPrice, '', $keyword); ?>">Semua</a>
                <?php foreach ($categories as $cat): ?>
                    <?php $query = product_filter_url($sortPrice, $cat, $keyword); ?>
                    <a class="category-pill <?php echo $category === $cat ? 'active' : ''; ?>" href="<?php echo page_url('produk.php' . $query); ?>"><?php echo htmlspecialchars($cat); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($keyword !== '' || $category !== ''): ?>
                <div class="active-filter"><span>Filter aktif:</span> <?php echo $keyword !== '' ? '“' . htmlspecialchars($keyword) . '”' : ''; ?> <?php echo $category !== '' ? htmlspecialchars($category) : ''; ?> <a href="<?php echo page_url('produk.php'); ?>"><i class="bi bi-x"></i> Reset</a></div>
            <?php endif; ?>

            <?php if (empty($filteredProducts)): ?>
                <div class="empty-products">
                    <i class="bi bi-search"></i>
                    <h3>Produk tidak ditemukan</h3>
                    <p>Coba gunakan kata kunci atau kategori yang berbeda.</p>
                    <a href="<?php echo page_url('produk.php'); ?>" class="catalog-primary-btn">Lihat Semua Produk</a>
                </div>
            <?php else: ?>
                <div class="catalog-grid product-catalog-grid" data-catalog-pagination-grid>
                    <?php foreach ($paginatedProducts as $item): ?>
                        <?php include __DIR__ . '/includes/components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="catalog-pagination" aria-label="Navigasi halaman produk">
                        <a class="pagination-btn pagination-arrow <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page > 1 ? htmlspecialchars(product_page_url($page - 1, $sortPrice, $category, $keyword)) : '#'; ?>" aria-label="Halaman sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <?php
                        $visiblePages = [];
                        if ($totalPages <= 7) {
                            $visiblePages = range(1, $totalPages);
                        } else {
                            $visiblePages = [1];
                            if ($page > 4) $visiblePages[] = 'ellipsis';
                            $startPage = max(2, $page - 1);
                            $endPage = min($totalPages - 1, $page + 1);
                            for ($i = $startPage; $i <= $endPage; $i++) $visiblePages[] = $i;
                            if ($page < $totalPages - 3) $visiblePages[] = 'ellipsis';
                            $visiblePages[] = $totalPages;
                        }
                        foreach ($visiblePages as $visiblePage):
                            if ($visiblePage === 'ellipsis'): ?>
                                <span class="pagination-ellipsis">•••</span>
                            <?php else: ?>
                                <a class="pagination-btn <?php echo $visiblePage === $page ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(product_page_url($visiblePage, $sortPrice, $category, $keyword)); ?>" <?php echo $visiblePage === $page ? 'aria-current="page"' : ''; ?>><?php echo $visiblePage; ?></a>
                            <?php endif;
                        endforeach; ?>
                        <a class="pagination-btn pagination-arrow <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo $page < $totalPages ? htmlspecialchars(product_page_url($page + 1, $sortPrice, $category, $keyword)) : '#'; ?>" aria-label="Halaman berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="product-bottom-cta">
        <div class="container">
            <div class="product-bottom-card">
                <div><span class="small-title">BUTUH PRODUK CUSTOM?</span><h2>Punya desain sendiri? Kami siap mencetaknya.</h2><p>Kirim kebutuhan dan desainmu ke Rosa Indah Printing. Tim kami akan membantu dari awal sampai siap cetak.</p></div>
                <a href="<?php echo page_url('kontak.php'); ?>" class="hero-button"><i class="bi bi-chat-dots-fill"></i> Konsultasi Sekarang</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
