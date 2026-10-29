<?php
require_once __DIR__ . '/config/config.php';

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$vouchers = [
    [
        'id' => 1,
        'code' => 'ROSAHEMAT10',
        'name' => 'Diskon Spesial Cetak 10%',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'min_order' => 100000,
        'max_discount' => 50000,
        'is_active' => 1,
    ],
    [
        'id' => 2,
        'code' => 'MEMBERBARU',
        'name' => 'Potongan Member Baru Rp15.000',
        'discount_type' => 'fixed',
        'discount_value' => 15000,
        'min_order' => 75000,
        'max_discount' => 15000,
        'is_active' => 1,
    ],
];

$pageTitle = 'Promo & Diskon Member';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="py-5">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Promo Member</span>
        </div>

        <div class="section-title text-center mb-5">
            <span>Penawaran Khusus</span>
            <h2>Voucher & Promo Percetakan</h2>
            <p class="text-muted">Gunakan kode promo berikut saat checkout untuk mendapatkan potongan harga spesial!</p>
        </div>

        <div class="row g-4 max-w-900 mx-auto">
            <?php foreach ($vouchers as $v): ?>
                <div class="col-md-6">
                    <div class="card p-4 rounded-4 shadow-sm border-2 border-primary border-dashed h-100 position-relative bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-danger px-3 py-2 fs-6">
                                <?php echo $v['discount_type'] === 'percent' ? 'Diskon ' . (int)$v['discount_value'] . '%' : 'Potongan ' . format_rupiah($v['discount_value']); ?>
                            </span>
                            <span class="badge bg-light text-secondary border">Promo Aktif</span>
                        </div>
                        <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($v['name']); ?></h4>
                        <p class="text-muted small mb-3">
                            Minimal belanja <?php echo format_rupiah($v['min_order']); ?><?php echo !empty($v['max_discount']) ? ' (Maksimal potongan ' . format_rupiah($v['max_discount']) . ')' : ''; ?>.
                        </p>
                        <div class="mt-auto p-2 bg-light rounded-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Kode Voucher:</span>
                            <code class="fs-5 fw-bold text-primary"><?php echo htmlspecialchars($v['code']); ?></code>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo page_url('produk.php'); ?>" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-cart-check me-1"></i> Mulai Belanja & Gunakan Promo
            </a>
        </div>
    </div>
</main>

<style>
.max-w-900 { max-width: 900px; }
.border-dashed { border-style: dashed !important; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
