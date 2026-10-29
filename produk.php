<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/product-categories.php';

$selectedCategory = trim($_GET['kategori'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');
$currentSort = trim($_GET['sort'] ?? 'rekomendasi');
if ($currentSort === '') {
    $currentSort = 'rekomendasi';
}

$allProducts = is_array($products ?? null) ? $products : [];
$totalCount = count($allProducts);
$categories = rosa_product_categories();

// Filter produk berdasarkan kategori & pencarian
$filteredProducts = array_values(array_filter($allProducts, function ($p) use ($selectedCategory, $searchQuery) {
    if ($selectedCategory !== '') {
        if (strtolower(trim((string)($p['category'] ?? ''))) !== strtolower($selectedCategory)) {
            return false;
        }
    }
    if ($searchQuery !== '') {
        $q = strtolower($searchQuery);
        $name = strtolower((string)($p['name'] ?? ''));
        $desc = strtolower((string)($p['description'] ?? ''));
        if (strpos($name, $q) === false && strpos($desc, $q) === false) {
            return false;
        }
    }
    return true;
}));

// Urutkan produk sesuai pilihan sort
if ($currentSort === 'terbaru') {
    $filteredProducts = array_reverse($filteredProducts);
} elseif ($currentSort === 'terlama') {
    // Urutan asli katalog
} elseif ($currentSort === 'harga_termurah') {
    usort($filteredProducts, function ($a, $b) {
        return ((float)($a['price'] ?? 0)) <=> ((float)($b['price'] ?? 0));
    });
} elseif ($currentSort === 'harga_termahal') {
    usort($filteredProducts, function ($a, $b) {
        return ((float)($b['price'] ?? 0)) <=> ((float)($a['price'] ?? 0));
    });
} elseif ($currentSort === 'terlaris') {
    $bestSellers = ['banner', 'sticker', 'kartu-nama', 'undangan-nikah', 'sablon-dtf', 'mug-custom', 'mug', 'kalender'];
    usort($filteredProducts, function ($a, $b) use ($bestSellers) {
        $aIndex = array_search($a['id'] ?? '', $bestSellers, true);
        $bIndex = array_search($b['id'] ?? '', $bestSellers, true);
        if ($aIndex !== false && $bIndex !== false) {
            return $aIndex <=> $bIndex;
        }
        if ($aIndex !== false) return -1;
        if ($bIndex !== false) return 1;
        return 0;
    });
} else {
    // rekomendasi / default
    $featuredIds = ['banner', 'undangan-nikah', 'kartu-nama'];
    usort($filteredProducts, function ($a, $b) use ($featuredIds) {
        $aFeat = !empty($a['is_featured']) || in_array($a['id'] ?? '', $featuredIds, true) ? 1 : 0;
        $bFeat = !empty($b['is_featured']) || in_array($b['id'] ?? '', $featuredIds, true) ? 1 : 0;
        return $bFeat <=> $aFeat;
    });
}

$sortLabels = [
    'rekomendasi' => 'Rekomendasi / Best Seller',
    'terlaris' => 'Terlaris',
    'terbaru' => 'Produk Terbaru → Terlama',
    'terlama' => 'Terlama → Terbaru',
    'harga_termurah' => 'Harga Termurah → Termahal',
    'harga_termahal' => 'Termahal → Termurah',
];
$activeSortText = $sortLabels[$currentSort] ?? 'Rekomendasi / Best Seller';

$catIcons = [
    'Undangan Nikah' => 'bi-envelope-heart',
    'Print Scan Copy' => 'bi-printer',
    'MUG' => 'bi-cup-hot',
    'Banner' => 'bi-flag',
    'Cetak Buku' => 'bi-book',
    'Lanyard & Id Card' => 'bi-person-badge',
    'Spanduk' => 'bi-badge-ad',
    'Kartu Nama' => 'bi-person-vcard',
    'Sticker' => 'bi-tag',
    'Undangan Lipat 3' => 'bi-envelope-paper',
    'Nota / Buku Tabungan' => 'bi-receipt',
    'Kalender' => 'bi-calendar3',
    'Sablon DTF' => 'bi-palette',
    'Cetak Foto' => 'bi-image',
    'Cetak Pigora' => 'bi-aspect-ratio',
    'Gantungan Kunci' => 'bi-key',
];

$sortParam = ($currentSort && $currentSort !== 'rekomendasi') ? '&sort=' . urlencode($currentSort) : '';
$sortParamAlone = ($currentSort && $currentSort !== 'rekomendasi') ? '?sort=' . urlencode($currentSort) : '';

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="catalog-page">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Produk</span>
            <?php if ($selectedCategory !== ''): ?>
                <i class="bi bi-chevron-right"></i>
                <span class="active"><?php echo htmlspecialchars($selectedCategory); ?></span>
            <?php endif; ?>
        </div>

        <!-- Header & Search (Marketplace Style) -->
        <div class="marketplace-catalog-header mb-3">
            <div class="row align-items-center g-3">
                <div class="col-lg-6 col-12">
                    <h1 class="marketplace-catalog-title mb-1">Katalog Produk Cetak</h1>
                    <p class="marketplace-catalog-subtitle mb-0">Solusi cetak cepat beresolusi tinggi dengan harga transparan langsung dari percetakan.</p>
                </div>

                <div class="col-lg-6 col-12">
                    <!-- Marketplace Search Bar -->
                    <form action="<?php echo page_url('produk.php'); ?>" method="GET" class="marketplace-search-form">
                        <?php if ($selectedCategory !== ''): ?>
                            <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                        <?php endif; ?>
                        <?php if ($currentSort && $currentSort !== 'rekomendasi'): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($currentSort); ?>">
                        <?php endif; ?>
                        <div class="marketplace-search-box">
                            <i class="bi bi-search marketplace-search-icon"></i>
                            <input type="text" name="q" class="marketplace-search-input" placeholder="Cari nama produk, spanduk, mug..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <?php if ($searchQuery !== ''): ?>
                                <?php
                                $clearUrl = 'produk.php';
                                if ($selectedCategory !== '') {
                                    $clearUrl .= '?kategori=' . urlencode($selectedCategory);
                                    if ($currentSort && $currentSort !== 'rekomendasi') {
                                        $clearUrl .= '&sort=' . urlencode($currentSort);
                                    }
                                } elseif ($currentSort && $currentSort !== 'rekomendasi') {
                                    $clearUrl .= '?sort=' . urlencode($currentSort);
                                }
                                ?>
                                <a href="<?php echo page_url($clearUrl); ?>" class="marketplace-clear-btn" title="Hapus pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </a>
                            <?php endif; ?>
                            <button class="marketplace-search-btn" type="submit">Cari</button>
                        </div>
                    </form>

                    <!-- Tombol Filter di Bawah Tombol Cari Produk -->
                    <div class="marketplace-search-subbar d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2">
                        <div class="filter-count-label text-muted small">
                            <i class="bi bi-box-seam text-rosa me-1"></i>
                            <span>Menampilkan <strong><?php echo count($filteredProducts); ?></strong> Produk</span>
                        </div>
                        <button type="button" class="btn-filter-popup" id="openFilterModalBtn">
                            <i class="bi bi-sliders2-vertical"></i>
                            <span class="btn-filter-text">Filter: <strong><?php echo htmlspecialchars($activeSortText); ?></strong></span>
                            <i class="bi bi-chevron-down ms-1 icon-chevron"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marketplace Category Scroll Strip (Shopee / Tokopedia Style) -->
        <div class="marketplace-filter-wrapper mb-4">
            <button type="button" class="category-scroll-btn prev" id="catScrollPrev" aria-label="Geser Kategori Kiri">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="marketplace-category-strip" id="catStrip" role="tablist" aria-label="Kategori Produk">
                <a href="<?php echo page_url('produk.php' . ($searchQuery !== '' ? '?q=' . urlencode($searchQuery) . $sortParam : $sortParamAlone)); ?>" 
                   class="market-chip <?php echo $selectedCategory === '' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i>
                    <span>Semua Kategori</span>
                    <span class="chip-count"><?php echo $totalCount; ?></span>
                </a>
                <?php foreach ($categories as $cat): 
                    $iconClass = $catIcons[$cat] ?? 'bi-box-seam';
                ?>
                    <a href="<?php echo page_url('produk.php?kategori=' . urlencode($cat) . ($searchQuery !== '' ? '&q=' . urlencode($searchQuery) : '') . $sortParam); ?>" 
                       class="market-chip <?php echo $selectedCategory === $cat ? 'active' : ''; ?>">
                        <i class="bi <?php echo htmlspecialchars($iconClass); ?>"></i>
                        <span><?php echo htmlspecialchars($cat); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <button type="button" class="category-scroll-btn next" id="catScrollNext" aria-label="Geser Kategori Kanan">
                <i class="bi bi-chevron-right"></i>
            </button>

            <?php if ($selectedCategory !== '' || $searchQuery !== '' || ($currentSort && $currentSort !== 'rekomendasi')): ?>
                <div class="marketplace-active-filter-bar mt-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="active-filter-text">
                        <span class="text-secondary small">Filter aktif:</span>
                        <?php if ($selectedCategory !== ''): ?>
                            <span class="active-tag"><i class="bi bi-tag-fill me-1"></i><?php echo htmlspecialchars($selectedCategory); ?></span>
                        <?php endif; ?>
                        <?php if ($searchQuery !== ''): ?>
                            <span class="active-tag"><i class="bi bi-search me-1"></i>"<?php echo htmlspecialchars($searchQuery); ?>"</span>
                        <?php endif; ?>
                        <?php if ($currentSort && $currentSort !== 'rekomendasi'): ?>
                            <span class="active-tag"><i class="bi bi-sliders2-vertical me-1"></i><?php echo htmlspecialchars($activeSortText); ?></span>
                        <?php endif; ?>
                        <span class="text-muted small ms-1">(<?php echo count($filteredProducts); ?> produk ditemukan)</span>
                    </div>
                    <a href="<?php echo page_url('produk.php'); ?>" class="btn-reset-filter">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Semua
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Grid -->
        <?php if (empty($filteredProducts)): ?>
            <div class="empty-products p-5 text-center bg-white rounded-3 shadow-sm my-4">
                <i class="bi bi-search display-3 text-muted"></i>
                <h3 class="mt-3">Produk Tidak Ditemukan</h3>
                <p class="text-muted">Tidak ada produk yang sesuai dengan pencarian atau filter kategori yang dipilih.</p>
                <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-primary mt-2">Tampilkan Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($filteredProducts as $item): ?>
                    <article class="catalog-card">
                        <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['id'])); ?>" class="catalog-card-image">
                            <img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
                            <span class="catalog-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                        </a>
                        <div class="catalog-card-body">
                            <div class="catalog-rating"><i class="bi bi-star-fill"></i> 5.0 <span>•</span> Kualitas Terjamin</div>
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="catalog-card-bottom">
                                <div>
                                    <small>Mulai dari</small>
                                    <strong><?php echo format_rupiah($item['price']); ?> <em>/ <?php echo htmlspecialchars($item['unit']); ?></em></strong>
                                </div>
                                <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['id'])); ?>" class="catalog-arrow" aria-label="Detail <?php echo htmlspecialchars($item['name']); ?>">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const strip = document.getElementById('catStrip');
    const prevBtn = document.getElementById('catScrollPrev');
    const nextBtn = document.getElementById('catScrollNext');
    if (!strip) return;

    function updateScrollArrows() {
        if (!prevBtn || !nextBtn) return;
        const maxScroll = strip.scrollWidth - strip.clientWidth;
        if (maxScroll <= 5) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
            return;
        }
        prevBtn.style.display = strip.scrollLeft > 10 ? 'flex' : 'none';
        nextBtn.style.display = strip.scrollLeft < (maxScroll - 10) ? 'flex' : 'none';
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            strip.scrollBy({ left: -220, behavior: 'smooth' });
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            strip.scrollBy({ left: 220, behavior: 'smooth' });
        });
    }

    strip.addEventListener('wheel', function(e) {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            strip.scrollLeft += e.deltaY;
            updateScrollArrows();
        }
    }, { passive: false });

    let isDown = false;
    let startX = 0;
    let startScrollLeft = 0;
    let hasDragged = false;

    strip.addEventListener('mousedown', function(e) {
        isDown = true;
        hasDragged = false;
        strip.classList.add('is-dragging');
        startX = e.pageX - strip.offsetLeft;
        startScrollLeft = strip.scrollLeft;
    });

    window.addEventListener('mouseup', function() {
        if (isDown) {
            isDown = false;
            strip.classList.remove('is-dragging');
        }
    });

    strip.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        const x = e.pageX - strip.offsetLeft;
        const walk = (x - startX) * 1.4;
        if (Math.abs(walk) > 4) {
            hasDragged = true;
        }
        strip.scrollLeft = startScrollLeft - walk;
        updateScrollArrows();
    });

    strip.querySelectorAll('.market-chip').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (hasDragged) {
                e.preventDefault();
                hasDragged = false;
            }
        });
    });

    const activeChip = strip.querySelector('.market-chip.active');
    if (activeChip) {
        setTimeout(function() {
            const stripWidth = strip.clientWidth;
            const chipLeft = activeChip.offsetLeft;
            const chipWidth = activeChip.clientWidth;
            strip.scrollTo({
                left: chipLeft - (stripWidth / 2) + (chipWidth / 2),
                behavior: 'smooth'
            });
            updateScrollArrows();
        }, 120);
    }

    strip.addEventListener('scroll', updateScrollArrows, { passive: true });
    window.addEventListener('resize', updateScrollArrows, { passive: true });
    updateScrollArrows();

    const openModalBtn = document.getElementById('openFilterModalBtn');
    const modalOverlay = document.getElementById('filterModalOverlay');
    const closeModalBtn = document.getElementById('closeFilterModalBtn');
    const modalBackdrop = document.getElementById('filterModalBackdrop');
    const applyFilterBtn = document.getElementById('applyFilterSortBtn');
    const resetFilterBtn = document.getElementById('resetFilterSortBtn');
    const optionCards = document.querySelectorAll('.filter-option-card');

    function openFilterModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.add('active');
        modalOverlay.style.setProperty('display', 'flex', 'important');
        document.body.style.overflow = 'hidden';
    }

    function closeFilterModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('active');
        modalOverlay.style.setProperty('display', 'none', 'important');
        document.body.style.overflow = '';
    }

    if (openModalBtn) {
        openModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openFilterModal();
        });
    }
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeFilterModal();
        });
    }
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', function(e) {
            e.preventDefault();
            closeFilterModal();
        });
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalOverlay && (modalOverlay.classList.contains('active') || modalOverlay.style.display !== 'none')) {
            closeFilterModal();
        }
    });

    optionCards.forEach(function(card) {
        card.addEventListener('click', function() {
            optionCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    function navigateWithSort(newSort) {
        const url = new URL(window.location.href);
        if (newSort && newSort !== 'rekomendasi') {
            url.searchParams.set('sort', newSort);
        } else {
            url.searchParams.delete('sort');
        }
        window.location.href = url.toString();
    }

    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedRadio = document.querySelector('input[name="sort_option"]:checked');
            const sortVal = selectedRadio ? selectedRadio.value : 'rekomendasi';
            closeFilterModal();
            navigateWithSort(sortVal);
        });
    }

    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeFilterModal();
            navigateWithSort('rekomendasi');
        });
    }
});
</script>

<!-- POPUP MODAL FILTER & URUTKAN PRODUK -->
<div class="filter-modal-overlay" id="filterModalOverlay" style="display: none;">
    <div class="filter-modal-backdrop" id="filterModalBackdrop"></div>
    <div class="filter-modal-sheet" id="filterModalSheet">
        <div class="filter-modal-handle d-md-none"></div>

        <div class="filter-modal-header">
            <div class="d-flex align-items-center gap-2">
                <div class="filter-header-icon">
                    <i class="bi bi-sliders2-vertical"></i>
                </div>
                <div>
                    <h5 class="filter-modal-title mb-0">Urutkan &amp; Filter Produk</h5>
                    <p class="filter-modal-subtitle mb-0">Pilih susunan produk sesuai kebutuhan belanja Anda</p>
                </div>
            </div>
            <button type="button" class="filter-modal-close" id="closeFilterModalBtn" aria-label="Tutup Filter">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="filter-modal-body">
            <div class="filter-options-list">
                <?php
                $filterItems = [
                    [
                        'key' => 'terbaru',
                        'title' => 'Produk Terbaru → Terlama',
                        'desc' => 'Urutkan dari produk cetak yang paling baru dirilis',
                        'icon' => 'bi-clock-history',
                        'color' => 'text-primary',
                    ],
                    [
                        'key' => 'terlama',
                        'title' => 'Terlama → Terbaru',
                        'desc' => 'Urutkan dari produk cetak pertama di katalog',
                        'icon' => 'bi-hourglass-split',
                        'color' => 'text-secondary',
                    ],
                    [
                        'key' => 'harga_termurah',
                        'title' => 'Harga Termurah → Termahal',
                        'desc' => 'Temukan pilihan harga paling hemat dan ekonomis',
                        'icon' => 'bi-sort-numeric-down',
                        'color' => 'text-success',
                    ],
                    [
                        'key' => 'harga_termahal',
                        'title' => 'Termahal → Termurah',
                        'desc' => 'Lihat paket cetak terlengkap dan bernilai tinggi',
                        'icon' => 'bi-sort-numeric-down-alt',
                        'color' => 'text-warning',
                    ],
                    [
                        'key' => 'terlaris',
                        'title' => 'Terlaris',
                        'desc' => 'Paling sering dipesan oleh pelanggan & pelaku UMKM',
                        'icon' => 'bi-fire',
                        'color' => 'text-danger',
                    ],
                    [
                        'key' => 'rekomendasi',
                        'title' => 'Rekomendasi / Best Seller',
                        'desc' => 'Pilihan produk unggulan & paling direkomendasikan',
                        'icon' => 'bi-stars',
                        'color' => 'text-rosa',
                    ],
                ];

                foreach ($filterItems as $item):
                    $isSelected = ($currentSort === $item['key']);
                ?>
                    <label class="filter-option-card <?php echo $isSelected ? 'selected' : ''; ?>" for="filter_<?php echo htmlspecialchars($item['key']); ?>">
                        <div class="filter-option-left">
                            <span class="filter-option-icon <?php echo htmlspecialchars($item['color']); ?>">
                                <i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
                            </span>
                            <div class="filter-option-info">
                                <span class="filter-option-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                <span class="filter-option-desc"><?php echo htmlspecialchars($item['desc']); ?></span>
                            </div>
                        </div>
                        <div class="filter-option-radio">
                            <input type="radio" name="sort_option" id="filter_<?php echo htmlspecialchars($item['key']); ?>" value="<?php echo htmlspecialchars($item['key']); ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                            <span class="custom-radio-circle">
                                <i class="bi bi-check"></i>
                            </span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filter-modal-footer">
            <button type="button" class="btn-filter-reset" id="resetFilterSortBtn">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default
            </button>
            <button type="button" class="btn-filter-apply" id="applyFilterSortBtn">
                <i class="bi bi-check2-circle me-1"></i> Terapkan Filter
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
