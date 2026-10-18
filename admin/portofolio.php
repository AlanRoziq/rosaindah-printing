<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/portfolio-data.php';
require_admin();

$pdo = db();
if (!$pdo) {
    die('Database tidak tersedia.');
}
ensure_portfolio_table($pdo);

$csrfToken = app_csrf_token();

function portfolio_upload_image(array $file, string $directory): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Gambar portofolio wajib diupload.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gambar gagal.');
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar maksimal 8 MB.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('File upload tidak valid.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || empty($info['mime'])) {
        throw new RuntimeException('File harus berupa gambar yang valid.');
    }
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = strtolower((string)$info['mime']);
    if (!isset($mimeMap[$mime])) {
        throw new RuntimeException('Format gambar hanya JPG, PNG, atau WEBP.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload portofolio tidak dapat dibuat.');
    }
    $filename = 'portfolio-' . date('YmdHis') . '-' . bin2hex(random_bytes(5)) . '.' . $mimeMap[$mime];
    $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gambar gagal disimpan.');
    }
    return 'uploads/portofolio/' . $filename;
}

function portfolio_delete_file(string $image): void {
    $image = ltrim(str_replace('\\','/',$image), '/');
    if (!str_starts_with($image, 'uploads/portofolio/')) return;
    $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image);
    if (is_file($full)) @unlink($full);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!app_verify_csrf($token)) {
        admin_flash('error', 'Sesi keamanan tidak valid. Silakan ulangi.');
        header('Location: ' . page_url('admin/portofolio.php'));
        exit;
    }

    $mode = (string)($_POST['mode'] ?? '');
    try {
        if ($mode === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image,product_name FROM portfolios WHERE id=? LIMIT 1');
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if (!$item) throw new RuntimeException('Portofolio tidak ditemukan.');
            $pdo->prepare('DELETE FROM portfolios WHERE id=?')->execute([$id]);
            portfolio_delete_file((string)$item['image']);
            admin_flash('success', 'Portofolio "' . $item['product_name'] . '" berhasil dihapus.');
        } elseif ($mode === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $productName = trim((string)($_POST['product_name'] ?? ''));
            $price = (float)str_replace(',', '', str_replace('.', '', (string)($_POST['price'] ?? '0')));
            $quantity = (int)($_POST['quantity'] ?? 0);
            $customerName = trim((string)($_POST['customer_name'] ?? ''));
            $currentImage = trim((string)($_POST['current_image'] ?? ''));

            if ($productName === '') throw new RuntimeException('Nama produk portofolio wajib diisi.');
            if (mb_strlen($productName) > 180) throw new RuntimeException('Nama produk terlalu panjang.');
            if ($price < 0) throw new RuntimeException('Harga tidak valid.');
            if ($quantity < 1) throw new RuntimeException('Jumlah pesanan minimal 1 pcs.');
            if ($customerName === '') throw new RuntimeException('Nama pelanggan wajib diisi.');
            if (mb_strlen($customerName) > 120) throw new RuntimeException('Nama pelanggan terlalu panjang.');

            $newImage = $currentImage;
            if (!empty($_FILES['image']['name'])) {
                $newImage = portfolio_upload_image($_FILES['image'], dirname(__DIR__) . '/uploads/portofolio');
            }
            if ($newImage === '') throw new RuntimeException('Gambar portofolio wajib diupload.');

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT image FROM portfolios WHERE id=? LIMIT 1');
                $stmt->execute([$id]);
                $old = $stmt->fetch();
                if (!$old) throw new RuntimeException('Portofolio tidak ditemukan.');
                $pdo->prepare('UPDATE portfolios SET image=?,product_name=?,price=?,quantity=?,customer_name=? WHERE id=?')
                    ->execute([$newImage,$productName,$price,$quantity,$customerName,$id]);
                if ($newImage !== ($old['image'] ?? '')) portfolio_delete_file((string)$old['image']);
                admin_flash('success', 'Portofolio berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO portfolios (image,product_name,price,quantity,customer_name) VALUES (?,?,?,?,?)')
                    ->execute([$newImage,$productName,$price,$quantity,$customerName]);
                admin_flash('success', 'Portofolio berhasil ditambahkan.');
            }
        }
    } catch (Throwable $e) {
        admin_flash('error', $e->getMessage());
    }

    header('Location: ' . page_url('admin/portofolio.php'));
    exit;
}

$action = (string)($_GET['action'] ?? '');
$editId = (int)($_GET['id'] ?? 0);
$editItem = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $pdo->prepare('SELECT id,image,product_name,price,quantity,customer_name FROM portfolios WHERE id=? LIMIT 1');
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch() ?: null;
}

$items = load_portfolios($pdo, null);
$pageTitle = 'Portofolio';
include __DIR__ . '/_header.php';
?>

<div class="admin-title">
    <div>
        <h1>Portofolio</h1>
        <p style="color:#7d747c">Kelola hasil produksi yang ingin ditampilkan di website.</p>
    </div>
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <a class="admin-btn secondary" href="<?php echo page_url('admin/portofolio.php'); ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
    <?php else: ?>
        <a class="admin-btn" href="<?php echo page_url('admin/portofolio.php?action=create'); ?>"><i class="bi bi-plus-lg"></i> Tambah Portofolio</a>
    <?php endif; ?>
</div>

<?php if ($flash): ?>
    <div class="admin-alert <?php echo $flash['type'] === 'error' ? 'error' : ''; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="admin-card">
        <form class="admin-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="mode" value="save">
            <input type="hidden" name="id" value="<?php echo (int)($editItem['id'] ?? 0); ?>">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editItem['image'] ?? ''); ?>">

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Gambar Produk Portofolio *</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?php echo $editItem ? '' : 'required'; ?>>
                    <small style="display:block;color:#8b8088;margin-top:7px">JPG, PNG, atau WEBP. Maksimal 8 MB.</small>
                    <?php if ($editItem): ?>
                        <img src="<?php echo htmlspecialchars(portfolio_image_url($editItem['image'])); ?>" alt="" style="display:block;width:110px;height:110px;object-fit:cover;border-radius:12px;margin-top:10px">
                    <?php endif; ?>
                </div>
                <div class="admin-field">
                    <label>Nama Produk *</label>
                    <input name="product_name" value="<?php echo htmlspecialchars($editItem['product_name'] ?? ''); ?>" required maxlength="180" placeholder="Contoh: Banner Indoor">
                </div>
            </div>

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Harga *</label>
                    <input type="number" name="price" min="0" step="1" value="<?php echo (float)($editItem['price'] ?? 0); ?>" required placeholder="Contoh: 150000">
                </div>
                <div class="admin-field">
                    <label>Jumlah Pesanan (pcs) *</label>
                    <input type="number" name="quantity" min="1" step="1" value="<?php echo (int)($editItem['quantity'] ?? 1); ?>" required placeholder="Contoh: 100">
                </div>
            </div>

            <div class="admin-field">
                <label>Nama Pelanggan *</label>
                <input name="customer_name" value="<?php echo htmlspecialchars($editItem['customer_name'] ?? ''); ?>" required maxlength="120" placeholder="Contoh: Bapak Ahmad">
            </div>

            <div style="display:flex;gap:9px;flex-wrap:wrap">
                <button class="admin-btn" type="submit"><i class="bi bi-check-lg"></i> <?php echo $editItem ? 'Simpan Perubahan' : 'Simpan Portofolio'; ?></button>
                <a class="admin-btn secondary" href="<?php echo page_url('admin/portofolio.php'); ?>">Batal</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="admin-card">
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead><tr><th>Gambar</th><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Pelanggan</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;color:#8b8088">Belum ada portofolio.</td></tr>
                <?php else: foreach ($items as $item): ?>
                    <tr>
                        <td><img class="admin-product-img" src="<?php echo htmlspecialchars(portfolio_image_url($item['image'])); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>"></td>
                        <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                        <td>Rp<?php echo number_format((float)$item['price'], 0, ',', '.'); ?></td>
                        <td><?php echo number_format((int)$item['quantity'], 0, ',', '.'); ?> pcs</td>
                        <td><?php echo htmlspecialchars($item['customer_name']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                        <td>
                            <div class="admin-actions">
                                <a href="<?php echo page_url('admin/portofolio.php?action=edit&id=' . (int)$item['id']); ?>"><i class="bi bi-pencil"></i> Edit</a>
                                <form method="post" style="display:inline" onsubmit="return confirm(<?php echo htmlspecialchars(json_encode('Hapus portofolio "' . $item['product_name'] . '"? Tindakan ini tidak dapat dibatalkan.', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>)">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="mode" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                    <button type="submit" class="admin-actions a" style="border:0;padding:7px 10px;border-radius:9px;background:#fff0f0;color:#c44;font-size:12px;font-weight:700;cursor:pointer"><i class="bi bi-trash"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
