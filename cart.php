<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';

cart_init();
$notice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!cart_verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Sesi keranjang tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? '';
        $productId = trim($_POST['product_id'] ?? '');
        if ($action === 'add') {
            $exists = false;
            foreach ($products as $item) if ($item['id'] === $productId) { $exists = true; break; }
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

$cartRows = [];
$total = 0;
foreach (cart_items() as $productId => $cartRow) {
    foreach ($products as $item) {
        if ($item['id'] !== $productId) continue;
        $qty = max(1, (int)$cartRow['quantity']);
        $unitPrice = product_unit_price($item, $qty);
        $subtotal = $unitPrice * $qty;
        $item['calculated_price'] = $unitPrice;
        $item['quantity'] = $qty;
        $item['subtotal'] = $subtotal;
        $cartRows[] = $item;
        $total += $subtotal;
        break;
    }
}

include __DIR__ . '/includes/header.php';
?>
<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="cart-page">
<div class="container">
    <div class="catalog-breadcrumb detail-breadcrumb">
        <a href="<?php echo page_url('index.php'); ?>">Home</a><i class="bi bi-chevron-right"></i><span>Keranjang</span>
    </div>

    <section class="checkout-heading cart-heading">
        <span class="small-title">KERANJANG PESANAN</span>
        <h1>Siapkan <span>Pesanan Anda</span></h1>
        <p>Periksa produk dan jumlah yang ingin dipesan sebelum melanjutkan ke checkout.</p>
    </section>

    <?php if ($error): ?><div class="cart-alert error"><i class="bi bi-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (isset($_GET['added'])): ?><div class="cart-alert success"><i class="bi bi-check-circle"></i>Produk berhasil ditambahkan ke keranjang.</div><?php endif; ?>

    <?php if (!$cartRows): ?>
        <section class="checkout-empty">
            <i class="bi bi-bag-x"></i><h1>Keranjang masih kosong</h1>
            <p>Pilih produk yang Anda butuhkan, lalu tambahkan ke keranjang.</p>
            <a class="catalog-primary-btn" href="<?php echo page_url('produk.php'); ?>"><i class="bi bi-grid"></i> Lihat Produk</a>
        </section>
    <?php else: ?>
    <div class="cart-grid">
        <section class="cart-main-card">
            <div class="cart-card-header"><div><span>DAFTAR PESANAN</span><h2><?php echo count($cartRows); ?> Produk</h2></div><form method="post" onsubmit="return confirm('Kosongkan semua isi keranjang?');"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>"><input type="hidden" name="action" value="clear"><button class="cart-clear-btn" type="submit"><i class="bi bi-trash3"></i> Kosongkan</button></form></div>
            <div class="cart-list">
            <?php foreach ($cartRows as $item): ?>
                <article class="cart-item">
                    <a href="<?php echo page_url('detail-produk.php?id=' . urlencode($item['id'])); ?>" class="cart-item-image"><img src="<?php echo product_image_url($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></a>
                    <div class="cart-item-info"><span class="detail-category"><?php echo htmlspecialchars($item['category']); ?></span><h3><?php echo htmlspecialchars($item['name']); ?></h3><small>Rp<?php echo number_format($item['calculated_price'],0,',','.'); ?> / <?php echo htmlspecialchars($item['unit']); ?></small></div>
                    <form method="post" class="cart-qty-form">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                        <div class="quantity-control"><button type="button" data-qty-minus><i class="bi bi-dash"></i></button><input name="quantity" type="number" min="1" max="9999" value="<?php echo $item['quantity']; ?>"><button type="button" data-qty-plus><i class="bi bi-plus"></i></button></div>
                        <button type="submit" class="cart-update-btn">Update</button>
                    </form>
                    <div class="cart-item-subtotal"><small>Subtotal</small><strong>Rp<?php echo number_format($item['subtotal'],0,',','.'); ?></strong><button type="submit" form="remove-<?php echo htmlspecialchars($item['id']); ?>" class="cart-remove-btn" aria-label="Hapus produk"><i class="bi bi-x-circle"></i></button></div>
                    <form id="remove-<?php echo htmlspecialchars($item['id']); ?>" method="post"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id']); ?>"></form>
                </article>
            <?php endforeach; ?>
            </div>
        </section>

        <aside class="cart-summary-card">
            <span class="small-title">RINGKASAN</span><h2>Total Pesanan</h2>
            <div class="cart-summary-line"><span>Total produk</span><strong><?php echo cart_count(); ?> item</strong></div>
            <div class="cart-summary-total"><span>Estimasi total</span><strong>Rp<?php echo number_format($total,0,',','.'); ?></strong></div>
            <p class="cart-summary-note"><i class="bi bi-info-circle"></i> Harga masih berupa estimasi harga mulai. Harga final akan dikonfirmasi Admin berdasarkan spesifikasi pesanan.</p>
            <a class="checkout-wa-btn cart-checkout-btn" href="<?php echo page_url('checkout.php'); ?>"><i class="bi bi-arrow-right-circle"></i> Lanjut ke Checkout</a>
            <a class="summary-back" href="<?php echo page_url('produk.php'); ?>"><i class="bi bi-arrow-left"></i> Tambah produk lainnya</a>
        </aside>
    </div>
    <?php endif; ?>
</div>
</main>
<script>
document.querySelectorAll('.cart-qty-form').forEach(form => {
 const input=form.querySelector('input[name="quantity"]');
 form.querySelector('[data-qty-minus]')?.addEventListener('click',()=>{input.value=Math.max(1,Number(input.value||1)-1);});
 form.querySelector('[data-qty-plus]')?.addEventListener('click',()=>{input.value=Math.min(9999,Number(input.value||1)+1);});
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
