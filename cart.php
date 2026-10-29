<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';

cart_init();
$notice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Izinkan CSRF opsional jika datang dari form kalkulator atau validasi jika disertakan
    $csrfToken = $_POST['csrf'] ?? '';
    if ($csrfToken !== '' && !cart_verify_csrf($csrfToken)) {
        $error = 'Sesi keranjang tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? '';
        $productId = trim($_POST['product_id'] ?? '');
        if ($action === 'add') {
            $exists = false;
            foreach ($products as $item) {
                if ($item['id'] === $productId) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                cart_add($productId, max(1, (int)($_POST['quantity'] ?? 1)));
                header('Location: ' . page_url('cart.php?added=1'));
                exit;
            }
            $error = 'Produk tidak ditemukan.';
        } elseif ($action === 'update') {
            cart_set($productId, (int)($_POST['quantity'] ?? 1));
            header('Location: ' . page_url('cart.php?updated=1'));
            exit;
        } elseif ($action === 'remove') {
            cart_remove($productId);
            header('Location: ' . page_url('cart.php?removed=1'));
            exit;
        } elseif ($action === 'clear') {
            cart_clear();
            header('Location: ' . page_url('cart.php?cleared=1'));
            exit;
        }
    }
}

if (isset($_GET['added'])) {
    $notice = 'Produk berhasil ditambahkan ke keranjang!';
} elseif (isset($_GET['updated'])) {
    $notice = 'Jumlah produk berhasil diperbarui.';
} elseif (isset($_GET['removed'])) {
    $notice = 'Produk dihapus dari keranjang.';
} elseif (isset($_GET['cleared'])) {
    $notice = 'Keranjang belanja telah dikosongkan.';
}

$cartRows = [];
$total = 0;
$totalItemsCount = 0;
foreach (cart_items() as $productId => $cartRow) {
    foreach ($products as $item) {
        if ($item['id'] !== $productId) continue;
        $qty = max(1, (int)$cartRow['quantity']);
        $unitPrice = product_unit_price($item, $qty);
        $subtotal = $unitPrice * $qty;
        $cartRows[] = [
            'product'   => $item,
            'quantity'  => $qty,
            'unitPrice' => $unitPrice,
            'subtotal'  => $subtotal,
        ];
        $total += $subtotal;
        $totalItemsCount += $qty;
        break;
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$pageTitle = 'Keranjang Belanja';
include __DIR__ . '/includes/header.php';
?>

<style>
/* Sinkronisasi Desain Keranjang Belanja (Desktop & Mobile) */
.cart-page {
    background: #fbf8fa !important;
    min-height: 75vh;
}
.cart-page-title {
    font-size: 26px;
    font-weight: 800;
    color: #18243a;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}
.cart-page-subtitle {
    font-size: 13.5px;
    color: #6e656d;
    margin-bottom: 0;
}
.cart-items-wrapper,
.cart-summary-card {
    border: 1px solid #f2dce7 !important;
    border-radius: 18px !important;
}
.cart-product-thumb {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #f0e3ea;
    background: #fff8fb;
}
.cart-product-name {
    font-size: 14px;
    color: #18243a;
    transition: color 0.15s ease;
}
.cart-product-name:hover {
    color: #e11d64 !important;
}
.cart-product-cat {
    font-size: 11.5px;
    color: #8c828b;
    display: block;
}
.cart-unit-cell {
    font-size: 13.5px;
    font-variant-numeric: tabular-nums;
}
.cart-subtotal-cell {
    font-size: 15px;
    color: #e11d64;
    font-variant-numeric: tabular-nums;
}
.cart-stepper-desktop {
    width: 110px;
}
.cart-stepper-desktop .qty-input {
    max-width: 50px;
    padding: 4px 6px;
    font-weight: 600;
    font-size: 13px;
    border-color: #ebdbe4;
}
.cart-stepper-desktop .btn {
    border-color: #ebdbe4;
    color: #555;
    padding: 4px 10px;
    font-weight: 700;
}
.cart-stepper-desktop .btn:hover {
    background: #fff0f6;
    color: #e11d64;
    border-color: #f2c9db;
}
.cart-mobile-card {
    transition: background 0.15s ease;
}
.cart-mobile-card:last-child {
    border-bottom: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
.cart-mobile-thumb {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #ebdbe4;
    background: #fff8fb;
}
.cart-mobile-title {
    font-size: 13px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.btn-trash-mobile {
    border: 0;
    background: transparent;
    color: #c0392b;
    padding: 4px 6px;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.15s ease;
}
.btn-trash-mobile:hover,
.btn-trash-mobile:active {
    background: #fee2e2;
    color: #991b1b;
}
.cart-mobile-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.cart-mobile-cat {
    font-size: 11px;
}
.cart-mobile-price {
    font-size: 12px;
    font-variant-numeric: tabular-nums;
}
.cart-stepper-mobile {
    display: inline-flex;
    align-items: center;
    border: 1px solid #ebdbe4;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
}
.cart-stepper-mobile .btn-step-minus,
.cart-stepper-mobile .btn-step-plus {
    border: 0;
    background: #fff0f6;
    color: #e11d64;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease;
}
.cart-stepper-mobile .btn-step-minus:active,
.cart-stepper-mobile .btn-step-plus:active {
    background: #fbcfe8;
}
.cart-stepper-mobile .qty-input {
    width: 38px;
    height: 32px;
    border: 0;
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    color: #18243a;
    background: #ffffff;
    padding: 0;
    -moz-appearance: textfield;
}
.cart-stepper-mobile .qty-input::-webkit-outer-spin-button,
.cart-stepper-mobile .qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.cart-mobile-subtotal-label {
    font-size: 10px;
}
.cart-mobile-subtotal-val {
    font-size: 14px;
    color: #e11d64;
    font-variant-numeric: tabular-nums;
}
.cart-total-price {
    font-size: 20px;
    color: #e11d64;
}
.cart-summary-badge {
    background: #f0fdf4;
    border: 1px solid #dcfce7;
}
.empty-cart-card {
    border: 1px dashed #f2c9db !important;
    background: #ffffff !important;
    border-radius: 18px !important;
}
.empty-cart-icon {
    font-size: 48px;
    color: #e11d64;
    line-height: 1;
}
.empty-cart-title {
    font-size: 20px;
    font-weight: 800;
    color: #18243a;
    margin-bottom: 8px;
}
.empty-cart-text {
    font-size: 13.5px;
    color: #6e656d !important;
}
.empty-cart-card .btn-primary,
.cart-summary-card .btn-checkout {
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 30px !important;
    box-shadow: 0 15px 35px -5px rgba(244, 63, 126, 0.35) !important;
    transition: all 0.25s ease !important;
}
.empty-cart-card .btn-primary:hover,
.cart-summary-card .btn-checkout:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 20px 40px -5px rgba(244, 63, 126, 0.45) !important;
    color: #ffffff !important;
}
@media (max-width: 767.98px) {
    .cart-page {
        padding-top: 14px !important;
        padding-bottom: 85px !important;
    }
    .cart-page-title {
        font-size: 18px !important;
        line-height: 1.25 !important;
        margin-bottom: 4px !important;
    }
    .cart-page-subtitle {
        font-size: 11px !important;
        line-height: 1.4 !important;
        margin-bottom: 0 !important;
    }
    .cart-header {
        margin-bottom: 14px !important;
    }
    .cart-items-wrapper,
    .cart-summary-card {
        padding: 14px !important;
        border-radius: 14px !important;
        border-color: #f2dce7 !important;
    }
    .empty-cart-card .btn-primary {
        width: 100% !important;
        text-align: center !important;
        padding: 10px 17px !important;
        font-size: 11px !important;
        border-radius: 30px !important;
    }
    .cart-mobile-thumb {
        width: 62px !important;
        height: 62px !important;
        border-radius: 10px !important;
    }
    .cart-mobile-title {
        font-size: 12.5px !important;
        line-height: 1.3 !important;
    }
    .cart-mobile-cat {
        font-size: 10.5px !important;
    }
    .cart-mobile-price {
        font-size: 11.5px !important;
    }
    .cart-stepper-mobile .btn-step-minus,
    .cart-stepper-mobile .btn-step-plus {
        width: 32px !important;
        height: 32px !important;
        font-size: 14px !important;
    }
    .cart-stepper-mobile .qty-input {
        width: 36px !important;
        height: 32px !important;
        font-size: 12px !important;
    }
    .cart-mobile-subtotal-val {
        font-size: 13.5px !important;
    }
    .cart-summary-title {
        font-size: 14px !important;
    }
    .cart-total-price {
        font-size: 17px !important;
    }
    .btn-checkout {
        font-size: 13px !important;
        padding: 10px 14px !important;
        border-radius: 10px !important;
    }
}
@media (max-width: 575.98px) {
    .cart-page-title {
        font-size: 17.5px !important;
    }
    .cart-mobile-thumb {
        width: 56px !important;
        height: 56px !important;
    }
    .cart-mobile-title {
        font-size: 12px !important;
    }
    .cart-summary-card {
        position: static !important;
        top: auto !important;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="cart-page py-3 py-md-4">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3 mb-md-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Keranjang Belanja</span>
        </div>

        <div class="cart-header mb-3 mb-md-4">
            <h1 class="cart-page-title">Keranjang Belanja Anda</h1>
            <p class="cart-page-subtitle">Periksa kembali rincian produk cetak sebelum melanjutkan proses checkout.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show cart-alert mb-3 mb-md-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="alert alert-success alert-dismissible fade show cart-alert mb-3 mb-md-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($notice); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($cartRows)): ?>
            <div class="empty-cart-card text-center p-4 p-md-5 bg-white rounded-4 shadow-sm my-3 my-md-4">
                <div class="empty-cart-icon mb-3">
                    <i class="bi bi-bag-x"></i>
                </div>
                <h3 class="empty-cart-title">Keranjang Belanja Masih Kosong</h3>
                <p class="empty-cart-text text-muted mb-4">Anda belum menambahkan produk cetak ke dalam keranjang belanja.</p>
                <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-primary px-4 py-2 fw-semibold">
                    <i class="bi bi-grid me-1"></i> Mulai Belanja Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="row g-3 g-md-4">
                <!-- Cart Items Section -->
                <div class="col-lg-8">
                    <div class="cart-items-wrapper bg-white p-3 p-md-4 rounded-4 shadow-sm">
                        <!-- Desktop Table (Screen >= 768px) -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table align-middle cart-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="min-width: 220px;">Produk</th>
                                        <th scope="col">Harga Satuan</th>
                                        <th scope="col" style="width: 160px;">Jumlah</th>
                                        <th scope="col">Subtotal</th>
                                        <th scope="col" class="text-end" style="width: 70px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartRows as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?php echo product_image_url($item['product']['image']); ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" class="cart-product-thumb rounded border" onerror="this.src='<?php echo asset_url('images/placeholder.jpg'); ?>'">
                                                    <div class="cart-product-info">
                                                        <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['product']['id'])); ?>" class="cart-product-name text-decoration-none text-dark fw-bold d-block"><?php echo htmlspecialchars($item['product']['name']); ?></a>
                                                        <span class="cart-product-cat text-muted"><?php echo htmlspecialchars($item['product']['category']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-secondary cart-unit-cell">
                                                <?php echo format_rupiah($item['unitPrice']); ?> <small class="text-muted">/ <?php echo htmlspecialchars($item['product']['unit']); ?></small>
                                            </td>
                                            <td>
                                                <form action="<?php echo page_url('cart.php'); ?>" method="POST" class="cart-qty-form d-flex align-items-center gap-1">
                                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product']['id']); ?>">
                                                    <div class="cart-stepper-desktop input-group input-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary btn-step-minus" title="Kurangi">-</button>
                                                        <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="100000" class="form-control text-center qty-input">
                                                        <button type="button" class="btn btn-outline-secondary btn-step-plus" title="Tambah">+</button>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary ms-1" title="Perbarui"><i class="bi bi-arrow-repeat"></i></button>
                                                </form>
                                            </td>
                                            <td class="fw-bold cart-subtotal-cell"><?php echo format_rupiah($item['subtotal']); ?></td>
                                            <td class="text-end">
                                                <form action="<?php echo page_url('cart.php'); ?>" method="POST" onsubmit="return confirm('Hapus produk ini dari keranjang?');">
                                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product']['id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-delete-item" title="Hapus dari keranjang"><i class="bi bi-trash3"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List (Screen < 768px) -->
                        <div class="cart-mobile-list d-md-none">
                            <?php foreach ($cartRows as $item): ?>
                                <div class="cart-mobile-card mb-3 pb-3 border-bottom">
                                    <div class="d-flex gap-3 align-items-start">
                                        <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['product']['id'])); ?>" class="cart-mobile-thumb-link flex-shrink-0">
                                            <img src="<?php echo product_image_url($item['product']['image']); ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" class="cart-mobile-thumb rounded-3 border" onerror="this.src='<?php echo asset_url('images/placeholder.jpg'); ?>'">
                                        </a>
                                        <div class="cart-mobile-info flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['product']['id'])); ?>" class="cart-mobile-title text-decoration-none text-dark fw-bold">
                                                    <?php echo htmlspecialchars($item['product']['name']); ?>
                                                </a>
                                                <form action="<?php echo page_url('cart.php'); ?>" method="POST" onsubmit="return confirm('Hapus produk ini?');" class="flex-shrink-0">
                                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product']['id']); ?>">
                                                    <button type="submit" class="btn-trash-mobile" title="Hapus" aria-label="Hapus item">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="cart-mobile-meta mt-1">
                                                <span class="cart-mobile-cat text-muted"><?php echo htmlspecialchars($item['product']['category']); ?></span>
                                                <span class="cart-mobile-price text-secondary">
                                                    <?php echo format_rupiah($item['unitPrice']); ?> <small>/ <?php echo htmlspecialchars($item['product']['unit']); ?></small>
                                                </span>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                                                <!-- Quantity Stepper -->
                                                <form action="<?php echo page_url('cart.php'); ?>" method="POST" class="cart-qty-form m-0">
                                                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product']['id']); ?>">
                                                    <div class="cart-stepper-mobile">
                                                        <button type="button" class="btn-step-minus" aria-label="Kurangi jumlah">-</button>
                                                        <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="100000" class="qty-input" aria-label="Jumlah item">
                                                        <button type="button" class="btn-step-plus" aria-label="Tambah jumlah">+</button>
                                                    </div>
                                                </form>

                                                <!-- Subtotal -->
                                                <div class="cart-mobile-subtotal text-end">
                                                    <span class="cart-mobile-subtotal-label d-block text-muted">Subtotal</span>
                                                    <span class="cart-mobile-subtotal-val fw-bold"><?php echo format_rupiah($item['subtotal']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Bottom Cart Actions -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 pt-md-3">
                            <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-outline-secondary btn-sm px-3 py-2">
                                <i class="bi bi-arrow-left me-1"></i> Lanjut Belanja
                            </a>
                            <form action="<?php echo page_url('cart.php'); ?>" method="POST" onsubmit="return confirm('Kosongkan semua item di keranjang?');">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm px-3 py-2">
                                    <i class="bi bi-trash me-1"></i> Kosongkan Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Summary (Ringkasan Belanja) -->
                <div class="col-lg-4">
                    <div class="cart-summary-card bg-white p-3 p-md-4 rounded-4 shadow-sm position-sticky" style="top: 85px;">
                        <h4 class="cart-summary-title fw-bold mb-3">Ringkasan Belanja</h4>

                        <div class="cart-summary-row d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Barang</span>
                            <span class="fw-semibold tabular-nums"><?php echo (int)$totalItemsCount; ?> item</span>
                        </div>
                        <div class="cart-summary-row d-flex justify-content-between mb-3">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold tabular-nums"><?php echo format_rupiah($total); ?></span>
                        </div>

                        <hr class="my-3">

                        <div class="cart-summary-total d-flex justify-content-between align-items-baseline mb-3">
                            <span class="fw-bold">Estimasi Total</span>
                            <span class="cart-total-price fw-bold tabular-nums"><?php echo format_rupiah($total); ?></span>
                        </div>

                        <a href="<?php echo page_url('checkout.php'); ?>" class="btn btn-primary btn-checkout w-100 py-2 py-md-3 fw-bold mb-2">
                            Lanjut ke Checkout <i class="bi bi-arrow-right ms-1"></i>
                        </a>

                        <div class="cart-summary-badge text-center p-2 rounded-3 mt-2">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            <span class="text-muted small">Transaksi aman, spesifikasi file dapat dicek via WA.</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Quantity Stepper Handler for both Mobile & Desktop
    document.addEventListener('DOMContentLoaded', function() {
        const handleStepper = function(wrapper) {
            const minusBtn = wrapper.querySelector('.btn-step-minus');
            const plusBtn = wrapper.querySelector('.btn-step-plus');
            const input = wrapper.querySelector('.qty-input');
            const form = wrapper.closest('form');

            if (!minusBtn || !plusBtn || !input || !form) return;

            minusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                let val = parseInt(input.value, 10) || 1;
                if (val > 1) {
                    input.value = val - 1;
                    form.submit();
                }
            });

            plusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                let val = parseInt(input.value, 10) || 1;
                input.value = val + 1;
                form.submit();
            });

            input.addEventListener('change', function() {
                let val = parseInt(input.value, 10);
                if (isNaN(val) || val < 1) input.value = 1;
                form.submit();
            });
        };

        document.querySelectorAll('.cart-stepper-desktop, .cart-stepper-mobile').forEach(handleStepper);
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
