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
<style>
/* Sinkronisasi penuh gaya Marketplace Katalog (/produk) */
.catalog-page {
  background: #fff9fb !important;
  min-height: 70vh;
  padding: 18px 0 70px;
}
.catalog-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #8a8790;
  margin-bottom: 14px;
}
.catalog-breadcrumb a {
  color: #e11d64;
  text-decoration: none;
  font-weight: 700;
}
.catalog-breadcrumb i {
  font-size: 10px;
}
.text-rosa {
  color: #e11d64 !important;
}
.marketplace-catalog-header {
  padding-bottom: 8px;
}
.marketplace-catalog-title {
  font-size: 24px;
  font-weight: 800;
  color: #111827;
  letter-spacing: -0.3px;
}
.marketplace-catalog-subtitle {
  font-size: 13.5px;
  color: #6b7280;
  line-height: 1.45;
}
.marketplace-search-form {
  width: 100%;
}
.marketplace-search-box {
  display: flex !important;
  align-items: center !important;
  background: #ffffff !important;
  border: 1.5px solid #f2c7da !important;
  border-radius: 999px !important;
  padding: 4px 5px 4px 16px !important;
  box-shadow: 0 4px 18px rgba(225, 29, 100, 0.08) !important;
  transition: all 0.25s ease !important;
  width: 100% !important;
}
.marketplace-search-box:focus-within {
  border-color: #e11d64 !important;
  box-shadow: 0 6px 22px rgba(225, 29, 100, 0.16) !important;
}
.marketplace-search-icon {
  font-size: 16px !important;
  color: #e11d64 !important;
  margin-right: 10px !important;
  flex-shrink: 0 !important;
}
.marketplace-search-input {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  background: transparent !important;
  font-size: 13.5px !important;
  color: #1f2937 !important;
  flex: 1 !important;
  min-width: 0 !important;
  padding: 6px 0 !important;
}
.marketplace-search-input::placeholder {
  color: #9ca3af !important;
}
.marketplace-clear-btn {
  color: #9ca3af !important;
  text-decoration: none !important;
  font-size: 16px !important;
  padding: 0 8px !important;
  display: inline-flex !important;
  align-items: center !important;
  transition: color 0.2s ease !important;
}
.marketplace-clear-btn:hover {
  color: #e11d64 !important;
}
.marketplace-search-btn {
  background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
  color: #ffffff !important;
  border: none !important;
  border-radius: 999px !important;
  padding: 7px 20px !important;
  font-size: 13px !important;
  font-weight: 700 !important;
  cursor: pointer !important;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
  box-shadow: 0 4px 14px rgba(225, 29, 100, 0.3) !important;
  transition: all 0.2s ease !important;
  width: auto !important;
}
.marketplace-search-btn:hover {
  background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 6px 18px rgba(225, 29, 100, 0.4) !important;
}
.marketplace-filter-wrapper {
  position: relative !important;
  width: 100% !important;
  max-width: 100% !important;
  min-width: 0 !important;
}
.marketplace-category-strip {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  overflow-x: auto !important;
  overflow-y: hidden !important;
  white-space: nowrap !important;
  padding: 6px 4px 12px !important;
  -webkit-overflow-scrolling: touch !important;
  scrollbar-width: none !important;
  scroll-behavior: smooth !important;
  touch-action: pan-x !important;
  cursor: grab !important;
  user-select: none !important;
  -webkit-user-select: none !important;
  width: 100% !important;
  min-width: 0 !important;
}
.marketplace-category-strip.is-dragging {
  cursor: grabbing !important;
  scroll-behavior: auto !important;
}
.marketplace-category-strip::-webkit-scrollbar {
  display: none !important;
  width: 0 !important;
  height: 0 !important;
}
.category-scroll-btn {
  position: absolute !important;
  top: 40% !important;
  transform: translateY(-50%) !important;
  width: 34px !important;
  height: 34px !important;
  border-radius: 50% !important;
  background: #ffffff !important;
  border: 1.5px solid #f2c7da !important;
  color: #e11d64 !important;
  display: none;
  align-items: center !important;
  justify-content: center !important;
  z-index: 20 !important;
  box-shadow: 0 4px 14px rgba(225, 29, 100, 0.22) !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
  font-size: 15px !important;
  line-height: 1 !important;
  padding: 0 !important;
}
.category-scroll-btn.prev { left: -8px !important; }
.category-scroll-btn.next { right: -8px !important; }
.category-scroll-btn:hover {
  background: #e11d64 !important;
  color: #ffffff !important;
  transform: translateY(-50%) scale(1.08) !important;
  border-color: #e11d64 !important;
  box-shadow: 0 6px 18px rgba(225, 29, 100, 0.35) !important;
}
.market-chip {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 7px 15px !important;
  border-radius: 999px !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  text-decoration: none !important;
  white-space: nowrap !important;
  flex-shrink: 0 !important;
  background: #ffffff !important;
  color: #374151 !important;
  border: 1px solid #f0d5e2 !important;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
  transition: all 0.2s ease !important;
  user-select: none !important;
  width: auto !important;
}
.market-chip i {
  font-size: 13px !important;
  color: #e11d64 !important;
  transition: color 0.2s ease !important;
}
.market-chip .chip-count {
  display: inline-block !important;
  padding: 1px 7px !important;
  border-radius: 999px !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  background: #fdf2f7 !important;
  color: #e11d64 !important;
  margin-left: 2px !important;
}
.market-chip:hover {
  background: #fff5f9 !important;
  border-color: #e11d64 !important;
  color: #e11d64 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(225, 29, 100, 0.12) !important;
}
.market-chip.active {
  background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
  color: #ffffff !important;
  border: 1px solid transparent !important;
  box-shadow: 0 6px 18px -2px rgba(225, 29, 100, 0.4) !important;
  font-weight: 700 !important;
  transform: translateY(-1px) !important;
}
.market-chip.active i { color: #ffffff !important; }
.market-chip.active .chip-count {
  background: rgba(255, 255, 255, 0.28) !important;
  color: #ffffff !important;
}
.marketplace-active-filter-bar {
  padding: 6px 12px !important;
  background: #fff8fb !important;
  border-radius: 10px !important;
  border: 1px dashed #f5c7dc !important;
  font-size: 12px !important;
}
.active-tag {
  display: inline-flex !important;
  align-items: center !important;
  padding: 2px 8px !important;
  background: #ffe4ed !important;
  color: #be124f !important;
  border-radius: 999px !important;
  font-weight: 700 !important;
  font-size: 11px !important;
  margin: 0 3px !important;
}
.btn-reset-filter {
  color: #e11d64 !important;
  text-decoration: none !important;
  font-size: 11.5px !important;
  font-weight: 600 !important;
}
.btn-filter-popup {
  display: inline-flex !important;
  align-items: center !important;
  gap: 7px !important;
  padding: 6px 14px !important;
  border-radius: 999px !important;
  background: #ffffff !important;
  border: 1.5px solid #f2c7da !important;
  color: #be124f !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 2px 8px rgba(225, 29, 100, 0.08) !important;
  width: auto !important;
}
.btn-filter-popup:hover {
  background: #fff5f9 !important;
  border-color: #e11d64 !important;
  color: #e11d64 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 14px rgba(225, 29, 100, 0.16) !important;
}
.btn-filter-popup i.bi-sliders2-vertical {
  font-size: 14px !important;
  color: #e11d64 !important;
}
.btn-filter-popup .icon-chevron {
  font-size: 10px !important;
  opacity: 0.7 !important;
}
.filter-modal-overlay {
  position: fixed !important;
  inset: 0 !important;
  z-index: 99999 !important;
  display: none !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 16px !important;
}
.filter-modal-overlay.active { display: flex !important; }
.filter-modal-backdrop {
  position: absolute !important;
  inset: 0 !important;
  background: rgba(17, 24, 39, 0.65) !important;
  backdrop-filter: blur(4px) !important;
}
.filter-modal-sheet {
  position: relative !important;
  background: #ffffff !important;
  border-radius: 20px !important;
  width: 100% !important;
  max-width: 500px !important;
  max-height: 88vh !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: hidden !important;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35) !important;
  z-index: 10 !important;
}
.filter-modal-handle {
  width: 44px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 999px;
  margin: 10px auto 2px;
}
.filter-modal-header {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 16px 20px 14px !important;
  border-bottom: 1px solid #f3f4f6 !important;
  background: #ffffff !important;
}
.filter-header-icon {
  width: 38px !important;
  height: 38px !important;
  border-radius: 12px !important;
  background: #ffe4ed !important;
  color: #e11d64 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 18px !important;
  flex-shrink: 0 !important;
}
.filter-modal-title {
  font-size: 16.5px !important;
  font-weight: 800 !important;
  color: #111827 !important;
}
.filter-modal-subtitle {
  font-size: 12px !important;
  color: #6b7280 !important;
}
.filter-modal-close {
  width: 32px !important;
  height: 32px !important;
  border-radius: 50% !important;
  border: none !important;
  background: #f3f4f6 !important;
  color: #4b5563 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 13px !important;
  cursor: pointer !important;
}
.filter-modal-body {
  padding: 16px 20px !important;
  overflow-y: auto !important;
  max-height: calc(88vh - 145px) !important;
}
.filter-options-list {
  display: flex !important;
  flex-direction: column !important;
  gap: 8px !important;
}
.filter-option-card {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 11px 14px !important;
  border-radius: 14px !important;
  border: 1.5px solid #edf0f5 !important;
  background: #ffffff !important;
  cursor: pointer !important;
  margin: 0 !important;
}
.filter-option-card.selected {
  border-color: #e11d64 !important;
  background: #fff5f9 !important;
  box-shadow: 0 4px 14px rgba(225, 29, 100, 0.12) !important;
}
.filter-option-left {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  min-width: 0 !important;
}
.filter-option-icon {
  width: 34px !important;
  height: 34px !important;
  border-radius: 10px !important;
  background: #f9fafb !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 16px !important;
  flex-shrink: 0 !important;
  border: 1px solid #f3f4f6 !important;
}
.filter-option-card.selected .filter-option-icon {
  background: #ffe4ed !important;
  border-color: #fbcfe8 !important;
}
.filter-option-info {
  display: flex !important;
  flex-direction: column !important;
  min-width: 0 !important;
}
.filter-option-title {
  font-size: 13.5px !important;
  font-weight: 700 !important;
  color: #1f2937 !important;
  line-height: 1.3 !important;
}
.filter-option-card.selected .filter-option-title { color: #be124f !important; }
.filter-option-desc {
  font-size: 11.5px !important;
  color: #6b7280 !important;
  line-height: 1.3 !important;
}
.filter-option-radio {
  margin-left: 10px !important;
  flex-shrink: 0 !important;
  display: flex !important;
  align-items: center !important;
}
.filter-option-radio input { display: none !important; }
.custom-radio-circle {
  width: 22px !important;
  height: 22px !important;
  border-radius: 50% !important;
  border: 2px solid #d1d5db !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  color: transparent !important;
  font-size: 13px !important;
  background: #ffffff !important;
}
.filter-option-card.selected .custom-radio-circle {
  background: #e11d64 !important;
  border-color: #e11d64 !important;
  color: #ffffff !important;
}
.filter-modal-footer {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: 12px 20px 16px !important;
  border-top: 1px solid #f3f4f6 !important;
  background: #ffffff !important;
  gap: 10px !important;
}
.btn-filter-reset {
  background: transparent !important;
  border: 1.5px solid #e5e7eb !important;
  color: #4b5563 !important;
  padding: 9px 18px !important;
  border-radius: 999px !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
}
.btn-filter-apply {
  background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
  color: #ffffff !important;
  border: none !important;
  padding: 10px 24px !important;
  border-radius: 999px !important;
  font-size: 13px !important;
  font-weight: 700 !important;
  cursor: pointer !important;
  box-shadow: 0 8px 20px -2px rgba(225, 29, 100, 0.35) !important;
}
@media (max-width: 767.98px) {
  .marketplace-catalog-title { font-size: 18px !important; }
  .marketplace-catalog-subtitle { font-size: 11.5px !important; }
  .marketplace-search-box { padding: 3px 4px 3px 12px !important; }
  .marketplace-search-icon { font-size: 14px !important; margin-right: 6px !important; }
  .marketplace-search-input { font-size: 12px !important; padding: 5px 0 !important; }
  .marketplace-search-btn { padding: 5px 14px !important; font-size: 11.5px !important; }
  .market-chip { padding: 5px 11px !important; font-size: 11px !important; gap: 4px !important; }
  .market-chip i { font-size: 11.5px !important; }
  .market-chip .chip-count { font-size: 9px !important; padding: 0 5px !important; }
  .marketplace-category-strip { padding-bottom: 6px !important; }
  .category-scroll-btn { width: 28px !important; height: 28px !important; font-size: 12px !important; }
  .category-scroll-btn.prev { left: -4px !important; }
  .category-scroll-btn.next { right: -4px !important; }
  .btn-filter-popup { font-size: 11.5px !important; padding: 5px 12px !important; }
  .filter-modal-overlay { align-items: flex-end !important; padding: 0 !important; }
  .filter-modal-sheet { max-width: 100% !important; border-radius: 24px 24px 0 0 !important; max-height: 82vh !important; }
  .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 10px !important;
  }
}
.topbar-promo {
  background: linear-gradient(90deg, #88133d 0%, #be124f 35%, #e11d64 70%, #f43f7e 100%) !important;
  color: #ffffff !important;
  font-size: 13px !important;
  padding: 8px 0 !important;
  position: relative !important;
  z-index: 50 !important;
  box-shadow: 0 2px 10px rgba(136, 19, 61, 0.25) !important;
  font-family: 'Poppins', sans-serif !important;
}
.topbar-promo .container {
  max-width: 1220px !important;
  padding-left: 20px !important;
  padding-right: 20px !important;
}
.topbar-content {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}
.topbar-promo-text {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-weight: 500 !important;
  font-size: 12.5px !important;
  color: #ffffff !important;
}
.topbar-badge {
  display: inline-flex !important;
  align-items: center !important;
  padding: 2px 10px !important;
  border-radius: 9999px !important;
  font-size: 10.5px !important;
  font-weight: 800 !important;
  background: #ffffff !important;
  color: #be124f !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}
.promo-code {
  text-decoration: underline !important;
  color: #ffe4ed !important;
  cursor: pointer !important;
}
.topbar-info {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  color: #ffe4ed !important;
  font-size: 12px !important;
}
.topbar-separator {
  opacity: 0.6 !important;
}
.topbar-wa-link {
  color: #ffffff !important;
  text-decoration: none !important;
  font-weight: 600 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  background: rgba(255, 255, 255, 0.15) !important;
  padding: 2px 10px !important;
  border-radius: 20px !important;
  width: auto !important;
  height: auto !important;
}
.custom-navbar {
  min-height: 68px !important;
  height: auto !important;
  padding: 8px 0 !important;
  background: #ffffff !important;
  box-shadow: 0 4px 20px rgba(225, 29, 100, 0.05) !important;
  border-bottom: 1px solid #f8dce8 !important;
  font-family: 'Poppins', sans-serif !important;
}
.custom-navbar > .container {
  max-width: 1220px !important;
  padding-left: 20px !important;
  padding-right: 20px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
}
.custom-navbar .navbar-brand {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-decoration: none !important;
  flex-shrink: 0 !important;
}
.custom-navbar .logo-box {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}
.custom-navbar .logo-box img {
  width: 38px !important;
  height: 38px !important;
  flex: 0 0 38px !important;
  object-fit: contain !important;
}
.custom-navbar .brand-text h2 {
  font-size: 14.5px !important;
  font-weight: 800 !important;
  color: #e11d64 !important;
  letter-spacing: 0.4px !important;
  margin: 0 !important;
  line-height: 1.08 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text h3 {
  font-size: 8.5px !important;
  font-weight: 700 !important;
  letter-spacing: 2.2px !important;
  color: #e11d64 !important;
  margin: 1.5px 0 0 !important;
  line-height: 1.08 !important;
  white-space: nowrap !important;
}
.custom-navbar .brand-text p {
  font-size: 6.5px !important;
  font-weight: 500 !important;
  color: #6b7280 !important;
  letter-spacing: 0.5px !important;
  margin: 1.5px 0 0 !important;
  line-height: 1.05 !important;
  white-space: nowrap !important;
}
@media (min-width: 992px) {
  .custom-navbar .navbar-nav {
    gap: 16px !important;
    margin: 0 auto !important;
    display: flex !important;
    align-items: center !important;
  }
  .custom-navbar .nav-link {
    font-size: 13.5px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    padding: 8px 6px !important;
    margin: 0 !important;
    white-space: nowrap !important;
  }
}
@media (min-width: 768px) {
  nav.custom-navbar .right-menu a.cart-nav-btn,
  .custom-navbar .cart-nav-btn,
  .custom-navbar .right-menu .cart-nav-btn {
    display: inline-flex !important;
  }
}
@media (max-width: 767.98px) {
  .custom-navbar {
    min-height: 50px !important;
    padding: 6px 0 !important;
  }
  .custom-navbar > .container {
    padding-left: 12px !important;
    padding-right: 12px !important;
    gap: 6px !important;
    flex-wrap: nowrap !important;
  }
  .custom-navbar .logo-box {
    gap: 6px !important;
  }
  .custom-navbar .logo-box img {
    width: 30px !important;
    height: 30px !important;
    flex: 0 0 30px !important;
  }
  .custom-navbar .brand-text h2 {
    font-size: 11.5px !important;
    letter-spacing: 0.3px !important;
  }
  .custom-navbar .brand-text h3 {
    font-size: 6.2px !important;
    letter-spacing: 1.6px !important;
  }
  .custom-navbar .brand-text p {
    font-size: 4.8px !important;
    letter-spacing: 0.3px !important;
  }
  nav.custom-navbar .right-menu a.cart-nav-btn,
  .custom-navbar .cart-nav-btn,
  .custom-navbar .right-menu .cart-nav-btn {
    display: none !important;
  }
}
</style>

<header class="main-header">
    <?php
    $topbarFile = __DIR__ . '/includes/topbar.php';
    $topbarRaw = is_file($topbarFile) ? (string)@file_get_contents($topbarFile) : '';
    if (strpos($topbarRaw, 'topbar-promo') !== false && strpos($topbarRaw, 'ROSAHEMAT10') !== false) {
        include $topbarFile;
    } else {
        ?>
        <div class="topbar-promo">
            <div class="container">
                <div class="topbar-content">
                    <div class="topbar-promo-text">
                        <span class="topbar-badge">Promo Spesial</span>
                        <span>Diskon <strong>20%</strong> Cetak Kemasan, Undangan &amp; Stiker! Kode: <strong class="promo-code">ROSAHEMAT10</strong></span>
                    </div>
                    <div class="topbar-info">
                        <span><i class="bi bi-clock"></i> 08.00 - 21.00 WIB</span>
                        <span class="topbar-separator">•</span>
                        <span><i class="bi bi-truck"></i> Pengiriman Seluruh Indonesia</span>
                        <span class="topbar-separator">•</span>
                        <a href="https://wa.me/<?php echo defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371'; ?>" target="_blank" rel="noopener" class="topbar-wa-link">
                            <i class="bi bi-whatsapp"></i> 0823-3103-5371
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    $navbarFile = __DIR__ . '/includes/navbar.php';
    $navbarRaw = is_file($navbarFile) ? (string)@file_get_contents($navbarFile) : '';
    if (strpos($navbarRaw, 'Portofolio') !== false) {
        include $navbarFile;
    } else {
        require_once __DIR__ . '/includes/product-categories.php';
        require_once __DIR__ . '/includes/cart.php';
        require_once __DIR__ . '/config/customer-auth.php';
        $customerPdo = db();
        if ($customerPdo) {
            try { customer_auth_init($customerPdo); } catch (Throwable $e) {}
        }
        ?>
        <nav class="navbar navbar-expand-lg custom-navbar">
            <div class="container">
                <a class="navbar-brand" href="<?php echo page_url('index.php'); ?>">
                    <div class="logo-box">
                        <img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing">
                        <div class="brand-text">
                            <h2>ROSA INDAH</h2>
                            <h3>-PRINTING-</h3>
                            <p>CETAK IDE JADI KENYATAAN</p>
                        </div>
                    </div>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Buka menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="menu">
                    <ul class="navbar-nav align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo page_url('index.php'); ?>">Home</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="<?php echo page_url('produk.php'); ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Produk
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo page_url('produk.php'); ?>">Semua Produk</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php foreach (rosa_product_categories() as $menuCategory): ?>
                                    <li><a class="dropdown-item" href="<?php echo page_url('produk.php?kategori=' . urlencode($menuCategory)); ?>"><?php echo htmlspecialchars($menuCategory); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('portfolio.php'); ?>"><i class="bi bi-images"></i> Portofolio</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('promo.php'); ?>"><i class="bi bi-ticket-perforated"></i> Promo Member</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('tentang.php'); ?>">Tentang Kami</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('cara-pemesanan.php'); ?>">Cara Pemesanan</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('kontak.php'); ?>">Kontak</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo page_url('lacak-pesanan.php'); ?>"><i class="bi bi-geo-alt"></i> Lacak Pesanan</a></li>
                    </ul>
                </div>

                <div class="right-menu">
                    <a href="<?php echo page_url('produk.php'); ?>" class="search-btn" aria-label="Cari produk" title="Cari produk">
                        <i class="bi bi-search"></i>
                    </a>
                    <a href="<?php echo page_url('cart.php'); ?>" class="cart-nav-btn d-none d-md-inline-flex" aria-label="Keranjang belanja" title="Keranjang Belanja">
                        <i class="bi bi-bag"></i>
                        <?php if (cart_count() > 0): ?><span class="cart-nav-count"><?php echo cart_count(); ?></span><?php endif; ?>
                    </a>
                    <?php if (customer_logged_in()): ?>
                        <?php
                        require_once __DIR__ . '/includes/notifications.php';
                        $navNotifCount = 0;
                        if ($customerPdo) {
                            try { $navNotifCount = member_notification_unread_count($customerPdo, (int)customer_id()); } catch (Throwable $e) {}
                        }
                        $cUser = customer_user();
                        $cFirstName = explode(' ', trim((string)($cUser['name'] ?? 'Member')))[0];
                        ?>
                        <a href="<?php echo page_url('notifikasi-member.php'); ?>" class="notification-nav-btn" aria-label="Notifikasi Member" title="Notifikasi Member">
                            <i class="bi bi-bell"></i><?php if ($navNotifCount > 0): ?><span class="notification-nav-count"><?php echo min(99, $navNotifCount); ?></span><?php endif; ?>
                        </a>
                        <a href="<?php echo page_url('akun.php'); ?>" class="login-btn" title="Akun Saya">
                            <i class="bi bi-person-circle"></i>
                            <span><?php echo htmlspecialchars($cFirstName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo page_url('login.php'); ?>" class="login-btn" title="Login Member">
                            <i class="bi bi-person"></i>
                            <span class="login-label-text">Login Member</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <?php
    }
    ?>
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

    // Wheel event to horizontal scroll
    strip.addEventListener('wheel', function(e) {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            strip.scrollLeft += e.deltaY;
            updateScrollArrows();
        }
    }, { passive: false });

    // Drag-to-scroll for mouse
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

    // Prevent navigation if it was a drag
    strip.querySelectorAll('.market-chip').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (hasDragged) {
                e.preventDefault();
                hasDragged = false;
            }
        });
    });

    // Auto center active chip
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

    // Filter Popup Modal Logic
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

    // Option cards selection
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

<!-- ==============================================
     POPUP MODAL FILTER & URUTKAN PRODUK
=============================================== -->
<div class="filter-modal-overlay" id="filterModalOverlay" style="display: none;">
    <div class="filter-modal-backdrop" id="filterModalBackdrop"></div>
    <div class="filter-modal-sheet" id="filterModalSheet">
        <!-- Sheet Handle for Mobile -->
        <div class="filter-modal-handle d-md-none"></div>

        <!-- Modal Header -->
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

        <!-- Modal Body / Filter Options -->
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

        <!-- Modal Footer -->
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
