<?php
require_once __DIR__ . '/config/config.php';
if (is_file(__DIR__ . '/config/auth.php')) {
    require_once __DIR__ . '/config/auth.php';
}
if (is_file(__DIR__ . '/includes/order-tracking.php')) {
    require_once __DIR__ . '/includes/order-tracking.php';
}

$pageTitle = 'Lacak Status Pesanan';
$pdo = function_exists('db') ? db() : null;

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$phone = trim((string)($_GET['phone'] ?? ''));
$isNew = !empty($_GET['new']);
$uploaded = !empty($_GET['uploaded']);
$error = '';
$order = null;

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

if (!function_exists('order_normalize_phone')) {
    function order_normalize_phone(string $value): string {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') return '';
        if (strpos($digits, '0') === 0) return '62' . substr($digits, 1);
        if (strpos($digits, '8') === 0) return '62' . $digits;
        return $digits;
    }
}

// Handle pencarian pesanan (Database + Demo Order fallback)
if ($code !== '') {
    // Cek di database terlebih dahulu apabila PDO tersedia
    if ($pdo) {
        try {
            if (function_exists('order_tracking_ensure_tables')) {
                order_tracking_ensure_tables($pdo);
            }
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
            $stmt->execute([$code]);
            $dbOrder = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dbOrder) {
                $givenPhone = order_normalize_phone($phone);
                $savedPhone = order_normalize_phone((string)($dbOrder['customer_phone'] ?? ''));
                if ($phone !== '' && $givenPhone !== '' && $savedPhone !== '' && $givenPhone !== $savedPhone) {
                    $error = 'Nomor WhatsApp tidak cocok dengan data pesanan.';
                } else {
                    // Ambil daftar item pesanan
                    $stmtItems = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
                    $stmtItems->execute([(int)$dbOrder['id']]);
                    $dbItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    // Ambil riwayat status pesanan
                    $stmtHist = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC, id DESC');
                    $stmtHist->execute([(int)$dbOrder['id']]);
                    $dbHistoryRaw = $stmtHist->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    $mappedHistory = [];
                    foreach ($dbHistoryRaw as $h) {
                        $mappedHistory[] = [
                            'status' => strtoupper((string)($h['status'] ?? 'INFO')),
                            'note' => trim((string)($h['title'] ?? '') . (!empty($h['message']) ? ' — ' . $h['message'] : '')),
                            'created_at' => !empty($h['created_at']) ? date('d M Y, H:i', strtotime((string)$h['created_at'])) : '',
                        ];
                    }

                    $order = [
                        'order_code' => (string)$dbOrder['order_code'],
                        'customer_name' => (string)($dbOrder['customer_name'] ?? 'Pelanggan'),
                        'customer_phone' => (string)($dbOrder['customer_phone'] ?? $phone),
                        'status' => strtolower((string)($dbOrder['status'] ?? 'pending')),
                        'payment_status' => strtolower((string)($dbOrder['payment_status'] ?? 'unpaid')),
                        'payment_method' => strtolower((string)($dbOrder['payment_method'] ?? 'transfer')),
                        'discount_amount' => (float)($dbOrder['discount_amount'] ?? 0),
                        'voucher_code' => (string)($dbOrder['voucher_code'] ?? ''),
                        'total_estimate' => (float)($dbOrder['total_estimate'] ?? 0),
                        'customer_note' => (string)($dbOrder['customer_note'] ?? ''),
                        'created_at' => !empty($dbOrder['created_at']) ? date('d M Y, H:i', strtotime((string)$dbOrder['created_at'])) : date('d M Y, H:i'),
                        'items' => array_map(function ($it) {
                            $qty = max(1, (int)($it['quantity'] ?? 1));
                            $sub = (float)($it['subtotal'] ?? 0);
                            $unitPrice = isset($it['unit_price']) && (float)$it['unit_price'] > 0 ? (float)$it['unit_price'] : ($sub / $qty);
                            return [
                                'product_name' => (string)($it['product_name'] ?? 'Produk Cetak'),
                                'quantity' => $qty,
                                'unit' => (string)($it['unit'] ?? 'pcs'),
                                'unit_price' => $unitPrice,
                                'subtotal' => $sub,
                            ];
                        }, $dbItems),
                        'history' => $mappedHistory,
                    ];
                }
            }
        } catch (Throwable $e) {
            // Lanjut ke pengecekan demo order
        }
    }

    // Fallback order demo (RIP-20260320-1045) agar sama persis dengan /lacak-pesanan
    if (!$order && $error === '') {
        if ($code === 'RIP-20260320-1045') {
            $givenPhone = order_normalize_phone($phone);
            $demoPhone = order_normalize_phone('081234567890');
            if ($phone !== '' && $givenPhone !== $demoPhone) {
                $error = 'Nomor WhatsApp tidak cocok dengan data pesanan.';
            } else {
                $order = [
                    'order_code' => 'RIP-20260320-1045',
                    'customer_name' => 'Budi Santoso',
                    'customer_phone' => '081234567890',
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'payment_method' => 'transfer',
                    'discount_amount' => 0,
                    'voucher_code' => '',
                    'total_estimate' => 255000,
                    'customer_note' => 'Tolong warna merah dibuat cerah, finishing mata ayam 4 sudut.',
                    'created_at' => '20 Mar 2026, 10:45',
                    'items' => [
                        [
                            'product_name' => 'Banner & Spanduk Outdoor High-Res',
                            'quantity' => 5,
                            'unit' => 'meter',
                            'unit_price' => 25000,
                            'subtotal' => 125000,
                        ],
                        [
                            'product_name' => 'Kartu Nama Art Carton 260gr + Box',
                            'quantity' => 4,
                            'unit' => 'box',
                            'unit_price' => 32500,
                            'subtotal' => 130000,
                        ],
                    ],
                    'history' => [
                        [
                            'status' => 'PROCESSING',
                            'note' => 'File desain telah di-ACC, pesanan sedang masuk antrean mesin cetak.',
                            'created_at' => '20 Mar 2026, 11:30',
                        ],
                        [
                            'status' => 'CONFIRMED',
                            'note' => 'Pembayaran BCA telah diverifikasi lunas oleh kasir.',
                            'created_at' => '20 Mar 2026, 11:00',
                        ],
                        [
                            'status' => 'PENDING',
                            'note' => 'Pesanan berhasil dibuat melalui website Rosa Indah Printing.',
                            'created_at' => '20 Mar 2026, 10:45',
                        ],
                    ],
                ];
            }
        } else {
            $error = 'Nomor pesanan tidak ditemukan.';
        }
    }
}

$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';

include __DIR__ . '/includes/header.php';
?>

<style>
/* Sinkronisasi penuh gaya Header & Halaman Lacak Pesanan (/lacak-pesanan) */
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

/* Tracking Page Core Styles */
.tracking-page {
  background: #fbf8fa !important;
  min-height: 75vh;
  font-family: 'Poppins', sans-serif;
}
.catalog-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #8a8790;
}
.catalog-breadcrumb a {
  color: #e11d64 !important;
  text-decoration: none;
  font-weight: 700;
}
.catalog-breadcrumb i {
  font-size: 10px;
}
.tracking-page-title {
  font-size: 26px;
  font-weight: 800;
  color: #18243a;
  letter-spacing: -0.02em;
}
.tracking-page-subtitle {
  font-size: 13.5px;
  color: #6e656d;
}
.tracking-search-card,
.tracking-result-card {
  border: 1px solid #f2dce7 !important;
  border-radius: 18px !important;
  background: #ffffff !important;
}
.tracking-label {
  font-size: 13px;
  color: #4b5563 !important;
}
.tracking-input {
  border: 1px solid #ebdbe4;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
}
.tracking-input:focus {
  border-color: #e11d64;
  box-shadow: 0 0 0 3px rgba(225, 29, 100, 0.12);
}
.btn-track {
  background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
  border: 0 !important;
  border-radius: 999px !important;
  color: #ffffff !important;
  font-size: 13.5px !important;
  font-weight: 700 !important;
  padding: 11px 28px !important;
  box-shadow: 0 10px 24px -4px rgba(225, 29, 100, 0.35) !important;
  transition: all 0.25s ease !important;
  width: 100%;
  max-width: 100%;
}
@media (min-width: 768px) {
  .btn-track {
    width: auto;
  }
}
.btn-track:hover {
  background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
  transform: translateY(-1px);
  color: #ffffff !important;
}
.tracking-demo-hint code {
  color: #e11d64 !important;
  background: #fff0f6;
  padding: 2px 6px;
  border-radius: 6px;
  font-weight: 600;
}
.order-success-banner {
  background: #ecfdf5 !important;
  border: 1px solid #a7f3d0 !important;
}
.success-banner-icon {
  font-size: 28px;
  color: #059669;
}
.order-code-pill {
  display: inline-block;
  background: #ffffff;
  border: 1px solid #10b981;
  color: #047857;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 14px;
}
.tracking-order-code {
  font-size: 22px;
  letter-spacing: -0.01em;
}
.tracking-status-badge {
  font-size: 12px;
  font-weight: 700;
}
.tracking-pay-badge {
  font-size: 11px;
}
.tracking-step-circle {
  width: 42px;
  height: 42px;
  font-size: 17px;
  transition: all 0.2s ease;
}
.tracking-step-label {
  font-size: 11px;
  line-height: 1.2;
  margin-top: 4px;
}
.tracking-item-name {
  font-size: 13.5px;
}
.tracking-item-subtotal {
  font-size: 13.5px;
}
.tracking-total-price {
  font-size: 18px;
  color: #e11d64 !important;
}
.bank-acc-no {
  font-size: 15px;
  color: #18243a;
}

@media (max-width: 767.98px) {
  .tracking-page {
    padding-top: 14px !important;
    padding-bottom: 85px !important;
  }
  .tracking-page-title {
    font-size: 19px !important;
    line-height: 1.25 !important;
  }
  .tracking-page-subtitle {
    font-size: 11.5px !important;
    line-height: 1.4 !important;
  }
  .tracking-search-card,
  .tracking-result-card {
    padding: 16px !important;
    border-radius: 14px !important;
  }
  .tracking-order-code {
    font-size: 17px !important;
  }
  .tracking-step-circle {
    width: 34px !important;
    height: 34px !important;
    font-size: 13px !important;
  }
  .tracking-step-label {
    font-size: 9px !important;
    line-height: 1.15 !important;
  }
  .tracking-item-name,
  .tracking-item-subtotal {
    font-size: 12.5px !important;
  }
  .tracking-total-price {
    font-size: 15px !important;
  }
  .tracking-order-header {
    flex-direction: column !important;
    align-items: flex-start !important;
  }
  .tracking-badges-group {
    align-items: flex-start !important;
  }
}

/* Mobile Bottom Navigation Bar Styles */
.mobile-bottom-nav {
  display: none !important;
  visibility: hidden !important;
}
@media (max-width: 767.98px) {
  .mobile-bottom-nav {
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1050 !important;
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-top: 1px solid #ffe4ed !important;
    box-shadow: 0 -5px 25px rgba(225, 29, 100, 0.12) !important;
    height: 62px !important;
    display: flex !important;
    visibility: visible !important;
    align-items: center !important;
    justify-content: space-around !important;
    padding: 0 6px !important;
  }
  body {
    padding-bottom: 74px !important;
  }
}
@media (min-width: 768px) {
  .mobile-bottom-nav {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
    height: 0 !important;
    overflow: hidden !important;
  }
  body {
    padding-bottom: 0 !important;
  }
}
.mobile-nav-item {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  text-decoration: none !important;
  color: #6b7280 !important;
  font-size: 10.5px !important;
  font-weight: 600 !important;
  flex: 1 !important;
  text-align: center !important;
  transition: color 0.2s ease !important;
}
.mobile-nav-item i {
  font-size: 19px !important;
  line-height: 1 !important;
  margin-bottom: 2px !important;
}
.mobile-nav-item:hover,
.mobile-nav-item:active,
.mobile-nav-item.active {
  color: #e11d64 !important;
  font-weight: 700 !important;
}
.mobile-nav-center {
  position: relative !important;
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  text-decoration: none !important;
  margin-top: -22px !important;
  flex: 1 !important;
}
.center-bubble {
  width: 52px !important;
  height: 52px !important;
  border-radius: 50% !important;
  background: linear-gradient(135deg, #e11d64 0%, #f43f7e 60%, #fb71a0 100%) !important;
  color: #ffffff !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 22px !important;
  border: 4px solid #ffffff !important;
  box-shadow: 0 10px 25px -3px rgba(225, 29, 100, 0.45) !important;
  position: relative !important;
}
.center-badge {
  position: absolute !important;
  top: -3px !important;
  right: -3px !important;
  background: #ffffff !important;
  color: #e11d64 !important;
  font-size: 10px !important;
  font-weight: 800 !important;
  border-radius: 50% !important;
  width: 19px !important;
  height: 19px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 2px solid #e11d64 !important;
}
.center-label {
  font-size: 10px !important;
  font-weight: 700 !important;
  color: #e11d64 !important;
  margin-top: 2px !important;
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
                        <a href="https://wa.me/<?php echo htmlspecialchars($waNumber); ?>" target="_blank" rel="noopener" class="topbar-wa-link">
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
                        <li class="nav-item"><a class="nav-link active" href="<?php echo page_url('lacak-pesanan.php'); ?>"><i class="bi bi-geo-alt"></i> Lacak Pesanan</a></li>
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
                        $cUser = customer_user();
                        $cFirstName = explode(' ', trim((string)($cUser['name'] ?? 'Member')))[0];
                        ?>
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

<main class="tracking-page py-3 py-md-4">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3 mb-md-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Lacak Pesanan</span>
        </div>

        <div class="tracking-header text-center mx-auto mb-3 mb-md-4" style="max-width: 640px;">
            <h1 class="tracking-page-title">Pelacakan Pesanan Real-Time</h1>
            <p class="tracking-page-subtitle text-muted">Pantau posisi pengerjaan pesanan percetakan Anda secara langsung menggunakan nomor pesanan dan WhatsApp.</p>
        </div>

        <?php if ($isNew && $code !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show order-success-banner mx-auto mb-4 p-3 p-md-4 rounded-4 shadow-sm" role="alert" style="max-width: 760px;">
                <div class="d-flex align-items-start gap-3">
                    <span class="success-banner-icon flex-shrink-0">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                    <div class="flex-grow-1">
                        <h2 class="alert-heading h5 fw-bold mb-1">Pesanan Berhasil Dibuat!</h2>
                        <p class="mb-2 text-dark">Terima kasih atas pesanan Anda. Nomor pesanan resmi Anda adalah:</p>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <span class="order-code-pill fw-bold tabular-nums"><?php echo htmlspecialchars($code); ?></span>
                            <button type="button" class="btn btn-sm btn-outline-success btn-copy-code" data-code="<?php echo htmlspecialchars($code); ?>">
                                <i class="bi bi-clipboard me-1"></i> Salin Nomor
                            </button>
                            <a href="<?php echo page_url('invoice.php?code=' . urlencode($code) . ($phone !== '' ? '&phone=' . urlencode($phone) : '')); ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-receipt me-1"></i> Lihat &amp; Cetak Nota
                            </a>
                        </div>
                        <small class="text-secondary d-block">Simpan nomor ini untuk melacak perkembangan desain, cetak, dan konfirmasi pembayaran.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($uploaded): ?>
            <div class="alert alert-success mx-auto mb-4 p-3 rounded-3" role="alert" style="max-width: 720px;">
                <i class="bi bi-check-circle-fill me-2"></i> Bukti pembayaran berhasil dikirim dan sedang diverifikasi oleh Admin.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger mx-auto mb-4 p-3 rounded-3" role="alert" style="max-width: 720px;">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Search Order Form Card -->
        <div class="tracking-search-card card p-3 p-md-4 rounded-4 shadow-sm border-0 mx-auto mb-4 mb-md-5" style="max-width: 720px;">
            <form action="<?php echo page_url('lacak-pesanan.php'); ?>" method="GET" class="row g-2 g-md-3">
                <div class="col-md-6">
                    <label for="code" class="form-label tracking-label fw-bold small text-secondary">Nomor Pesanan (Order Code):</label>
                    <input type="text" class="form-control text-uppercase tracking-input" id="code" name="code" placeholder="Contoh: RIP-20260320-1045" value="<?php echo htmlspecialchars($code); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label tracking-label fw-bold small text-secondary">Nomor WhatsApp Pemesan:</label>
                    <input type="text" class="form-control tracking-input" id="phone" name="phone" placeholder="Contoh: 081234567890" value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <div class="col-12 text-center mt-3">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold btn-track">
                        <i class="bi bi-search me-1"></i> Lacak Status Sekarang
                    </button>
                    <div class="small text-muted mt-2 tracking-demo-hint">
                        Contoh order demo: <code class="user-select-all">RIP-20260320-1045</code> (WA: <code>081234567890</code>)
                    </div>
                </div>
            </form>
        </div>

        <?php if ($order): ?>
            <?php
            $steps = [
                ['id' => 'pending', 'label' => 'Baru Dibuat', 'icon' => 'bi-clipboard-check'],
                ['id' => 'confirmed', 'label' => 'Dikonfirmasi', 'icon' => 'bi-patch-check'],
                ['id' => 'processing', 'label' => 'Proses Cetak', 'icon' => 'bi-printer'],
                ['id' => 'printed', 'label' => 'Finishing', 'icon' => 'bi-box-seam'],
                ['id' => 'completed', 'label' => 'Selesai', 'icon' => 'bi-bag-check'],
            ];
            $statusOrder = ['pending', 'confirmed', 'processing', 'printed', 'completed'];
            $currentIdx = array_search($order['status'], $statusOrder, true);
            if ($currentIdx === false) {
                $currentIdx = -1;
            }
            ?>

            <div class="tracking-result-card card p-3 p-md-4 rounded-4 shadow-sm border-0 mx-auto" style="max-width: 860px;">
                <!-- Order Header Info -->
                <div class="tracking-order-header d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
                    <div>
                        <span class="text-muted small d-block">Nomor Pesanan Resmi:</span>
                        <h2 class="tracking-order-code fw-bold text-dark mb-0 tabular-nums"><?php echo htmlspecialchars($order['order_code']); ?></h2>
                        <small class="text-secondary"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($order['created_at']); ?> WIB</small>
                    </div>
                    <div class="tracking-badges-group d-flex flex-column align-items-sm-end gap-1">
                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'primary'); ?> tracking-status-badge px-3 py-2">
                            Status: <?php echo htmlspecialchars(strtoupper($order['status'])); ?>
                        </span>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'waiting' ? 'warning text-dark' : 'danger'); ?> tracking-pay-badge">
                            <?php echo $order['payment_status'] === 'paid' ? 'LUNAS' : ($order['payment_status'] === 'waiting' ? 'MENUNGGU VERIFIKASI' : 'BELUM BAYAR'); ?>
                        </span>
                    </div>
                </div>

                <!-- Timeline Steps (Mobile Optimized) -->
                <div class="tracking-progress-wrapper my-3 my-md-4 py-2">
                    <div class="tracking-progress-track position-relative">
                        <div class="progress position-absolute top-50 start-0 w-100" style="height: 3px; z-index: 1; transform: translateY(-50%);">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo max(0, (int)$currentIdx) * 25; ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                            <?php foreach ($steps as $idx => $st):
                                $isDone = ($currentIdx >= $idx);
                                $isCurrent = ($currentIdx === $idx);
                            ?>
                                <div class="tracking-step-item text-center">
                                    <div class="tracking-step-circle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 text-white <?php echo $isDone ? 'bg-success' : 'bg-light border text-muted'; ?>">
                                        <i class="bi <?php echo htmlspecialchars($st['icon']); ?>"></i>
                                    </div>
                                    <span class="tracking-step-label d-block fw-semibold <?php echo $isCurrent ? 'text-primary fw-bold' : ($isDone ? 'text-dark' : 'text-muted'); ?>">
                                        <?php echo htmlspecialchars($st['label']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Details & Payment Sections -->
                <div class="row g-3 g-md-4 mt-1">
                    <!-- Products Ordered -->
                    <div class="col-lg-7">
                        <div class="tracking-box p-3 p-md-4 rounded-3 bg-light border">
                            <h3 class="tracking-box-title h6 fw-bold mb-3 d-flex align-items-center">
                                <i class="bi bi-basket text-primary me-2"></i> Daftar Produk Pesanan
                            </h3>
                            <div class="tracking-items-list mb-3">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="tracking-item-row d-flex justify-content-between align-items-start py-2 border-bottom">
                                        <div class="pe-2">
                                            <span class="tracking-item-name fw-bold text-dark d-block"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            <small class="text-muted"><?php echo (int)$item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?> × <?php echo format_rupiah($item['unit_price']); ?></small>
                                        </div>
                                        <div class="tracking-item-subtotal fw-semibold text-danger tabular-nums text-nowrap">
                                            <?php echo format_rupiah($item['subtotal']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom text-success small">
                                        <div>Diskon Promo (<?php echo htmlspecialchars($order['voucher_code']); ?>)</div>
                                        <div class="fw-bold tabular-nums">- <?php echo format_rupiah($order['discount_amount']); ?></div>
                                    </div>
                                <?php endif; ?>

                                <div class="tracking-total-row d-flex justify-content-between align-items-baseline pt-3 fw-bold">
                                    <span class="text-dark">Total Estimasi:</span>
                                    <span class="tracking-total-price tabular-nums"><?php echo format_rupiah($order['total_estimate']); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($order['customer_note'])): ?>
                                <div class="tracking-note-box p-2 p-md-3 bg-white border rounded-3 mt-2">
                                    <span class="text-muted small fw-bold d-block mb-1">Catatan Pemesan:</span>
                                    <p class="small text-secondary mb-0"><?php echo htmlspecialchars($order['customer_note']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment Information & Actions -->
                    <div class="col-lg-5">
                        <div class="tracking-box p-3 p-md-4 rounded-3 bg-white border">
                            <h3 class="tracking-box-title h6 fw-bold mb-3 d-flex align-items-center">
                                <i class="bi bi-wallet2 text-primary me-2"></i> Informasi Pembayaran
                            </h3>

                            <div class="tracking-bank-card p-3 rounded-3 bg-light border mb-3">
                                <small class="text-muted d-block mb-1">Metode: <b class="text-uppercase text-dark"><?php echo htmlspecialchars($order['payment_method']); ?></b></small>

                                <?php if ($order['payment_method'] === 'transfer'): ?>
                                    <div class="bank-details mt-2 pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-dark">Bank BCA Pasuruan</span>
                                            <span class="badge bg-primary-subtle text-primary border">BCA</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                            <code class="bank-acc-no fs-6 fw-bold user-select-all">1234-5678-90</code>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-bank py-0 px-2" data-acc="1234567890">
                                                <i class="bi bi-clipboard"></i> Salin
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">a.n. Rosa Indah Printing</small>
                                    </div>
                                <?php elseif ($order['payment_method'] === 'qris'): ?>
                                    <div class="qris-details mt-2 pt-2 border-top text-center">
                                        <small class="text-muted d-block mb-1">Scan QRIS saat konfirmasi via WhatsApp</small>
                                        <span class="badge bg-success-subtle text-success border">QRIS Siap Dipindai</span>
                                    </div>
                                <?php else: ?>
                                    <div class="cash-details mt-2 pt-2 border-top">
                                        <small class="text-muted d-block">Bayar langsung saat pengambilan pesanan di workshop.</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Upload Proof Form -->
                            <div class="tracking-upload-box p-3 rounded-3 border bg-light mb-3">
                                <h4 class="h6 fw-bold mb-2">Konfirmasi Bukti Pembayaran</h4>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <div class="text-success small fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i> Pembayaran telah diverifikasi lunas oleh kasir/admin.
                                    </div>
                                <?php else: ?>
                                    <p class="small text-muted mb-2">Upload bukti transfer atau struk resi agar pesanan segera masuk tahap pengerjaan.</p>
                                    <form action="<?php echo page_url('pembayaran.php'); ?>" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order['order_code']); ?>">
                                        <input type="hidden" name="customer_phone" value="<?php echo htmlspecialchars($order['customer_phone']); ?>">
                                        <div class="mb-2">
                                            <input type="file" class="form-control form-control-sm" name="proof" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-upload me-1"></i> Kirim Bukti Bayar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons: WA & Invoice -->
                            <div class="tracking-action-buttons d-flex flex-column gap-2">
                                <a href="https://wa.me/<?php echo htmlspecialchars($waNumber); ?>?text=Halo%20Rosa%20Indah%20Printing,%20konfirmasi%20pesanan%20<?php echo urlencode($order['order_code']); ?>"
                                   target="_blank" rel="noopener" class="btn btn-success btn-sm w-100 py-2 fw-semibold">
                                    <i class="bi bi-whatsapp me-1"></i> Konfirmasi via WhatsApp
                                </a>
                                <a href="<?php echo page_url('invoice.php?code=' . urlencode($order['order_code']) . '&phone=' . urlencode($order['customer_phone'])); ?>"
                                   target="_blank" class="btn btn-outline-secondary btn-sm w-100 py-2">
                                    <i class="bi bi-printer me-1"></i> Cetak / Simpan Nota Resmi (PDF)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Log -->
                <?php if (!empty($order['history'])): ?>
                    <hr class="my-4">
                    <div class="tracking-history-section">
                        <h3 class="h6 fw-bold mb-3 d-flex align-items-center">
                            <i class="bi bi-clock-history text-primary me-2"></i> Riwayat Aktivitas Pesanan
                        </h3>
                        <ul class="list-group list-group-flush rounded-3 border">
                            <?php foreach ($order['history'] as $h): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 small">
                                    <div>
                                        <span class="badge bg-secondary-subtle text-secondary border me-2"><?php echo htmlspecialchars($h['status']); ?></span>
                                        <span class="text-dark"><?php echo htmlspecialchars($h['note']); ?></span>
                                    </div>
                                    <small class="text-muted tabular-nums"><?php echo htmlspecialchars($h['created_at']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy Order Code button
    const copyBtns = document.querySelectorAll('.btn-copy-code');
    copyBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (code && navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin!';
                    this.classList.replace('btn-outline-success', 'btn-success');
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.classList.replace('btn-success', 'btn-outline-success');
                    }, 2000);
                });
            }
        });
    });

    // Copy Bank Account button
    const copyBankBtns = document.querySelectorAll('.btn-copy-bank');
    copyBankBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const acc = this.getAttribute('data-acc');
            if (acc && navigator.clipboard) {
                navigator.clipboard.writeText(acc).then(() => {
                    const original = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(() => {
                        this.innerHTML = original;
                    }, 2000);
                });
            }
        });
    });
});
</script>

<?php
$footerFile = __DIR__ . '/includes/footer.php';
$footerRaw = is_file($footerFile) ? (string)@file_get_contents($footerFile) : '';
if (strpos($footerRaw, 'mobile-bottom-nav') === false) {
    $fallbackCartCount = function_exists('cart_count') ? (int)cart_count() : 0;
    ?>
    <nav class="mobile-bottom-nav d-md-none" aria-label="Navigasi Menu Mobile">
        <a href="<?php echo page_url('index.php'); ?>" class="mobile-nav-item">
            <i class="bi bi-house-door-fill"></i>
            <span>Beranda</span>
        </a>
        <a href="<?php echo page_url('produk.php'); ?>" class="mobile-nav-item">
            <i class="bi bi-grid-fill"></i>
            <span>Produk</span>
        </a>
        <a href="<?php echo page_url('cart.php'); ?>" class="mobile-nav-center" aria-label="Keranjang">
            <div class="center-bubble">
                <i class="bi bi-cart3"></i>
                <?php if ($fallbackCartCount > 0): ?>
                    <span class="center-badge"><?php echo $fallbackCartCount; ?></span>
                <?php endif; ?>
            </div>
            <span class="center-label">Keranjang</span>
        </a>
        <a href="<?php echo page_url('lacak-pesanan.php'); ?>" class="mobile-nav-item active">
            <i class="bi bi-search"></i>
            <span>Lacak</span>
        </a>
        <a href="<?php echo page_url('akun.php'); ?>" class="mobile-nav-item">
            <i class="bi bi-person-fill"></i>
            <span>Profil</span>
        </a>
    </nav>
    <?php
}
include $footerFile;
?>
