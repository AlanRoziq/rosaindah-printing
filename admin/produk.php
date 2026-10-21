<?php
/**
 * Manajemen Produk — Rosa Indah Printing
 *
 * Fitur:
 * - Tambah / Edit produk
 * - Hapus produk
 * - Aktif / Nonaktif
 * - Produk Unggulan / Nonunggulan
 * - Upload gambar aman
 * - CSRF protection
 * - Validasi ID dan duplikasi
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/product-categories.php';

$pdo = db();
if ($pdo) { try { ensure_rosa_product_categories($pdo); } catch (Throwable $e) {} }
if (!$pdo) {
    $pageTitle = 'Produk';
    require_once __DIR__ . '/_header.php';
    echo '<div class="admin-alert error">Database belum terhubung. Import file <b>database/rosaindahprinting.sql</b> di phpMyAdmin.</div>';
    require __DIR__ . '/_footer.php';
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_csrf'];

function produk_csrf_or_fail(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'] ?? '', $token)) {
        throw new RuntimeException('Sesi keamanan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
    }
}

function produk_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function produk_image_path(string $relative): ?string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    if ($relative === '' || !str_starts_with($relative, 'uploads/produk/')) {
        return null;
    }

    $base = realpath(__DIR__ . '/../uploads/produk');
    if ($base === false) {
        return null;
    }

    $candidate = realpath(__DIR__ . '/../' . $relative);
    if ($candidate === false) {
        return null;
    }

    $base = rtrim(str_replace('\\', '/', $base), '/');
    $candidate = str_replace('\\', '/', $candidate);

    if ($candidate === $base || str_starts_with($candidate, $base . '/')) {
        return $candidate;
    }

    return null;
}

function ensure_admin_price_tiers_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_price_tiers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id VARCHAR(80) NOT NULL,
        min_qty INT UNSIGNED NOT NULL DEFAULT 1,
        max_qty INT UNSIGNED NULL,
        price DECIMAL(14,2) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_price_tier_product_qty (product_id, min_qty)
    ) ENGINE=InnoDB");
}

function parse_price_tiers(string $raw): array {
    $tiers = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (!preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)|\+)??\s*[=:]\s*([0-9.,]+)\s*$/', $line, $m)) {
            throw new InvalidArgumentException('Format harga bertingkat salah. Gunakan contoh: 1-49=5000, 50-99=4000, 100+=3500.');
        }
        $min=(int)$m[1]; $max=null;
        if (isset($m[2]) && $m[2] !== '') $max=(int)$m[2];
        $price=(float)str_replace(',', '', str_replace('.', '', $m[3]));
        if ($min < 1 || $price < 0 || ($max !== null && $max < $min)) throw new InvalidArgumentException('Rentang atau harga bertingkat tidak valid.');
        $tiers[]=['min_qty'=>$min,'max_qty'=>$max,'price'=>$price];
    }
    usort($tiers, fn($a,$b)=>$a['min_qty']<=>$b['min_qty']);
    $prevMax=0; foreach($tiers as $t){ if($t['min_qty'] <= $prevMax) throw new InvalidArgumentException('Rentang harga bertingkat tidak boleh tumpang tindih.'); $prevMax=$t['max_qty'] ?? PHP_INT_MAX; }
    return $tiers;
}

function produk_delete_image_if_owned(string $relative): void
{
    $path = produk_image_path($relative);
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

$action = $_GET['action'] ?? 'list';
$id = trim((string)($_GET['id'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $uploadedNewImage = null;

    try {
        produk_csrf_or_fail();

        if ($mode === 'save') {
            $name = trim((string)($_POST['name'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            $price = (float)($_POST['price'] ?? 0);
            $unit = trim((string)($_POST['unit'] ?? 'pcs')) ?: 'pcs';
            $description = trim((string)($_POST['description'] ?? ''));
            $featuresRaw = (string)($_POST['features'] ?? '');
            $features = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $featuresRaw) ?: []),
                static fn($item) => $item !== ''
            ));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            $featured = !empty($_POST['is_featured']) ? 1 : 0;
            $pricingTiers = parse_price_tiers((string)($_POST['price_tiers'] ?? ''));
            $oldId = trim((string)($_POST['old_id'] ?? ''));
            $requestedId = trim((string)($_POST['id'] ?? ''));

            if ($name === '') {
                throw new InvalidArgumentException('Nama produk wajib diisi.');
            }
            if ($category === '') {
                throw new InvalidArgumentException('Kategori produk wajib dipilih.');
            }
            if (!in_array($category, rosa_product_categories(), true)) {
                throw new InvalidArgumentException('Kategori produk tidak valid. Silakan pilih kategori yang tersedia.');
            }
            if ($price < 0) {
                throw new InvalidArgumentException('Harga tidak boleh negatif.');
            }
            if (mb_strlen($name) > 180) {
                throw new InvalidArgumentException('Nama produk maksimal 180 karakter.');
            }
            if (mb_strlen($category) > 100) {
                throw new InvalidArgumentException('Kategori maksimal 100 karakter.');
            }
            if (mb_strlen($unit) > 50) {
                throw new InvalidArgumentException('Satuan maksimal 50 karakter.');
            }

            $newId = $requestedId !== '' ? produk_slug($requestedId) : produk_slug($name);
            if ($newId === '') {
                throw new InvalidArgumentException('ID produk tidak valid. Gunakan huruf kecil, angka, dan tanda strip.');
            }
            if (strlen($newId) > 80) {
                throw new InvalidArgumentException('ID produk maksimal 80 karakter.');
            }

            // Cegah perubahan ID ke ID milik produk lain.
            if ($oldId !== '' && $newId !== $oldId) {
                $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ?');
                $check->execute([$newId]);
                if ((int)$check->fetchColumn() > 0) {
                    throw new InvalidArgumentException('ID produk tersebut sudah digunakan oleh produk lain.');
                }
            }

            $image = trim((string)($_POST['current_image'] ?? ''));
            $oldImage = $image;

            if (!empty($_FILES['image']['name']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Upload gambar gagal. Silakan pilih gambar lain.');
                }
                if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
                    throw new RuntimeException('File gambar tidak valid.');
                }
                if ((int)$_FILES['image']['size'] > 5 * 1024 * 1024) {
                    throw new InvalidArgumentException('Ukuran gambar maksimal 5 MB.');
                }

                $tmp = $_FILES['image']['tmp_name'];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                $mimeMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                if (!isset($mimeMap[$mime])) {
                    throw new InvalidArgumentException('Format gambar harus JPG, PNG, atau WEBP.');
                }

                $dir = __DIR__ . '/../uploads/produk';
                if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new RuntimeException('Folder upload produk tidak dapat dibuat.');
                }

                $filename = $newId . '-' . bin2hex(random_bytes(6)) . '.' . $mimeMap[$mime];
                $destination = $dir . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($tmp, $destination)) {
                    throw new RuntimeException('Gambar gagal disimpan ke folder upload.');
                }

                $image = 'uploads/produk/' . $filename;
                $uploadedNewImage = $image;
            }

            if ($image === '') {
                throw new InvalidArgumentException('Foto produk wajib dipilih.');
            }

            $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($featuresJson === false) {
                throw new RuntimeException('Data keunggulan produk tidak dapat diproses.');
            }

            ensure_admin_price_tiers_table($pdo);
            $pdo->beginTransaction();
            try {
                if ($oldId !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE products
                         SET id=?, name=?, category=?, image=?, price=?, unit=?, description=?, features=?, status=?, is_featured=?
                         WHERE id=?'
                    );
                    $stmt->execute([
                        $newId, $name, $category, $image, $price, $unit,
                        $description, $featuresJson, $status, $featured, $oldId
                    ]);
                    if ($stmt->rowCount() === 0) {
                        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE id=?');
                        $check->execute([$oldId]);
                        if ((int)$check->fetchColumn() === 0) {
                            throw new RuntimeException('Produk yang ingin diedit tidak ditemukan.');
                        }
                    }
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO products (id,name,category,image,price,unit,description,features,status,is_featured)
                         VALUES (?,?,?,?,?,?,?,?,?,?)'
                    );
                    $stmt->execute([
                        $newId, $name, $category, $image, $price, $unit,
                        $description, $featuresJson, $status, $featured
                    ]);
                }
                $tierDelete = $pdo->prepare('DELETE FROM product_price_tiers WHERE product_id=?');
                $tierDelete->execute([$newId]);
                if ($pricingTiers) {
                    $tierInsert = $pdo->prepare('INSERT INTO product_price_tiers (product_id,min_qty,max_qty,price) VALUES (?,?,?,?)');
                    foreach ($pricingTiers as $tier) $tierInsert->execute([$newId,$tier['min_qty'],$tier['max_qty'],$tier['price']]);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            // Hapus gambar lama hanya setelah database berhasil diperbarui.
            if ($uploadedNewImage !== null && $oldImage !== '' && $oldImage !== $uploadedNewImage) {
                produk_delete_image_if_owned($oldImage);
            }

            admin_flash('success', 'Produk berhasil disimpan dan otomatis masuk ke katalog.');
            header('Location: ' . page_url('admin/produk.php'));
            exit;
        }

        if ($mode === 'delete') {
            $deleteId = trim((string)($_POST['id'] ?? ''));
            if ($deleteId === '') {
                throw new InvalidArgumentException('Produk yang akan dihapus tidak valid.');
            }

            $stmt = $pdo->prepare('SELECT image, name FROM products WHERE id=? LIMIT 1');
            $stmt->execute([$deleteId]);
            $productToDelete = $stmt->fetch();
            if (!$productToDelete) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            try {
                $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
                $stmt->execute([$deleteId]);
            } catch (PDOException $e) {
                // Biasanya terjadi jika produk sudah dipakai pada order_items.
                if ((int)$e->errorInfo[1] === 1451) {
                    throw new RuntimeException('Produk tidak bisa dihapus karena sudah digunakan dalam data pesanan. Nonaktifkan produk saja agar riwayat pesanan tetap aman.');
                }
                throw $e;
            }

            produk_delete_image_if_owned((string)$productToDelete['image']);
            admin_flash('success', 'Produk "' . $productToDelete['name'] . '" berhasil dihapus.');
            header('Location: ' . page_url('admin/produk.php'));
            exit;
        }

        if ($mode === 'toggle') {
            $toggleId = trim((string)($_POST['id'] ?? ''));
            if ($toggleId === '') {
                throw new InvalidArgumentException('Produk tidak valid.');
            }
            $stmt = $pdo->prepare("UPDATE products SET status=IF(status='active','inactive','active') WHERE id=?");
            $stmt->execute([$toggleId]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }
            admin_flash('success', 'Status produk berhasil diperbarui.');
            header('Location: ' . page_url('admin/produk.php'));
            exit;
        }

        if ($mode === 'featured') {
            $featuredId = trim((string)($_POST['id'] ?? ''));
            if ($featuredId === '') {
                throw new InvalidArgumentException('Produk tidak valid.');
            }
            $stmt = $pdo->prepare('UPDATE products SET is_featured=IF(is_featured=1,0,1) WHERE id=?');
            $stmt->execute([$featuredId]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }
            admin_flash('success', 'Status produk unggulan berhasil diperbarui.');
            header('Location: ' . page_url('admin/produk.php'));
            exit;
        }

        throw new RuntimeException('Aksi tidak dikenal.');
    } catch (Throwable $e) {
        if ($uploadedNewImage !== null) {
            produk_delete_image_if_owned($uploadedNewImage);
        }

        $redirect = 'admin/produk.php';
        if ($mode === 'save') {
            $postedOldId = trim((string)($_POST['old_id'] ?? ''));
            if ($postedOldId !== '') {
                $redirect .= '?action=edit&id=' . urlencode($postedOldId);
            } else {
                $redirect .= '?action=create';
            }
        }
        admin_flash('error', $e->getMessage());
        header('Location: ' . page_url($redirect));
        exit;
    }
}

// Header baru dipanggil setelah semua proses POST selesai,
// sehingga redirect() di atas tidak lagi terkena "headers already sent".
$pageTitle = 'Produk';
require_once __DIR__ . '/_header.php';

if ($action === 'create' || $action === 'edit') {
    $product = [
        'id' => '',
        'name' => '',
        'category' => '',
        'image' => '',
        'price' => 0,
        'unit' => 'pcs',
        'pricing' => [],
        'description' => '',
        'features' => [],
        'status' => 'active',
        'is_featured' => 0,
    ];

    if ($action === 'edit') {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) {
            admin_flash('error', 'Produk tidak ditemukan.');
            header('Location: ' . page_url('admin/produk.php'));
            exit;
        }
        $product['features'] = json_decode($product['features'] ?? '[]', true) ?: [];
        ensure_admin_price_tiers_table($pdo);
        $tierStmt = $pdo->prepare('SELECT min_qty,max_qty,price FROM product_price_tiers WHERE product_id=? ORDER BY min_qty');
        $tierStmt->execute([$product['id']]);
        $product['pricing'] = $tierStmt->fetchAll();
    }

    $cats = array_map(static fn($name) => ['name' => $name], rosa_product_categories());
    $imageUrl = $product['image'] ? page_url($product['image']) : '';
    ?>
    <div class="admin-title">
        <div>
            <h1><?php echo $action === 'edit' ? 'Edit Produk' : 'Tambah Produk'; ?></h1>
            <p style="color:#777">Kelola informasi produk yang tampil di katalog Rosa Indah Printing.</p>
        </div>
        <a class="admin-btn secondary" href="<?php echo page_url('admin/produk.php'); ?>">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="admin-card">
        <form class="admin-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="mode" value="save">
            <input type="hidden" name="old_id" value="<?php echo htmlspecialchars($product['id']); ?>">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image']); ?>">

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Nama Produk *</label>
                    <input name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required maxlength="180">
                </div>
                <div class="admin-field">
                    <label>ID Produk</label>
                    <input name="id" value="<?php echo htmlspecialchars($product['id']); ?>" placeholder="contoh: undangan-nikah" maxlength="80">
                    <small style="color:#8b8088">Huruf kecil, angka, dan tanda strip. Kosongkan saat tambah untuk dibuat otomatis.</small>
                </div>
            </div>

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Kategori *</label>
                    <select name="category" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $product['category'] === $c['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Harga Mulai</label>
                    <input type="number" min="0" step="1" name="price" value="<?php echo (float)$product['price']; ?>">
                </div>
                <div class="admin-field">
                    <label>Harga Bertingkat <small style="font-weight:400">(opsional)</small></label>
                    <textarea name="price_tiers" rows="5" placeholder="1-49=5000\n50-99=4000\n100+=3500"><?php
                        $tierLines=[]; foreach (($product['pricing'] ?? []) as $tier) { $range=(int)$tier['min_qty'] . ($tier['max_qty'] === null ? '+' : '-' . (int)$tier['max_qty']); $tierLines[]=$range . '=' . rtrim(rtrim(number_format((float)$tier['price'],2,'.',''), '0'), '.'); }
                        echo htmlspecialchars(implode("\n", $tierLines));
                    ?></textarea>
                    <small style="display:block;color:#8b8088;margin-top:7px">Contoh: 1-49=5000, 50-99=4000, 100+=3500. Harga otomatis mengikuti jumlah saat checkout.</small>
                </div>
            </div>

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Satuan</label>
                    <input name="unit" value="<?php echo htmlspecialchars($product['unit']); ?>" placeholder="pcs / lembar / meter" maxlength="50">
                </div>
                <div class="admin-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="admin-field">
                <label>Deskripsi</label>
                <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="admin-field">
                <label>Keunggulan Produk <small style="font-weight:400">(satu poin per baris)</small></label>
                <textarea name="features" placeholder="Desain custom&#10;Cetak full color&#10;Bahan berkualitas"><?php echo htmlspecialchars(implode("\n", $product['features'])); ?></textarea>
            </div>

            <div class="admin-grid-2">
                <div class="admin-field">
                    <label>Foto Produk <?php echo $action === 'create' ? '*' : ''; ?></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <small style="display:block;color:#8b8088;margin-top:7px">JPG, PNG, WEBP — maksimal 5 MB.</small>
                    <?php if ($product['image']): ?>
                        <div style="margin-top:12px;display:flex;align-items:center;gap:12px">
                            <img class="admin-product-img" style="width:90px;height:90px" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <span style="font-size:12px;color:#8b8088">Foto saat ini. Pilih foto baru jika ingin menggantinya.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="admin-field">
                    <label>&nbsp;</label>
                    <label class="admin-check">
                        <input type="checkbox" name="is_featured" value="1" <?php echo !empty($product['is_featured']) ? 'checked' : ''; ?>>
                        Jadikan produk unggulan
                    </label>
                    <p style="font-size:12px;color:#8a8088">Produk aktif tampil di katalog. Produk unggulan dapat diprioritaskan pada bagian katalog unggulan website.</p>
                </div>
            </div>

            <div>
                <button class="admin-btn" type="submit">
                    <i class="bi bi-save"></i> <?php echo $action === 'edit' ? 'Simpan Perubahan' : 'Simpan Produk'; ?>
                </button>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/_footer.php';
    exit;
}

$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = $_GET['status'] ?? 'all';
$featuredFilter = $_GET['featured'] ?? 'all';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR id LIKE ? OR category LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}
if ($featuredFilter === 'yes') {
    $where[] = 'is_featured = 1';
} elseif ($featuredFilter === 'no') {
    $where[] = 'is_featured = 0';
}

$sql = 'SELECT * FROM products';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY is_featured DESC, created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn();
$featuredProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_featured=1')->fetchColumn();
?>

<div class="admin-title">
    <div>
        <h1>Produk</h1>
        <p style="color:#777">Tambah dan kelola semua produk yang tampil di katalog.</p>
    </div>
    <a class="admin-btn" href="<?php echo page_url('admin/produk.php?action=create'); ?>">
        <i class="bi bi-plus-lg"></i> Tambah Produk
    </a>
</div>

<div class="admin-stats" style="grid-template-columns:repeat(3,1fr)">
    <div class="admin-stat"><i class="bi bi-box-seam"></i><strong><?php echo $totalProducts; ?></strong><span>Total Produk</span></div>
    <div class="admin-stat"><i class="bi bi-check-circle"></i><strong><?php echo $activeProducts; ?></strong><span>Produk Aktif</span></div>
    <div class="admin-stat"><i class="bi bi-star-fill"></i><strong><?php echo $featuredProducts; ?></strong><span>Produk Unggulan</span></div>
</div>

<div class="admin-card" style="margin-bottom:18px">
    <form method="get" style="display:grid;grid-template-columns:minmax(220px,1fr) 180px 180px auto;gap:10px;align-items:end">
        <div class="admin-field">
            <label>Cari Produk</label>
            <input name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nama, ID, atau kategori...">
        </div>
        <div class="admin-field">
            <label>Status</label>
            <select name="status">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
            </select>
        </div>
        <div class="admin-field">
            <label>Unggulan</label>
            <select name="featured">
                <option value="all" <?php echo $featuredFilter === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="yes" <?php echo $featuredFilter === 'yes' ? 'selected' : ''; ?>>Unggulan</option>
                <option value="no" <?php echo $featuredFilter === 'no' ? 'selected' : ''; ?>>Bukan Unggulan</option>
            </select>
        </div>
        <button class="admin-btn" type="submit"><i class="bi bi-search"></i> Cari</button>
    </form>
</div>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:15px">
        <div style="font-weight:800;color:#18243a">Daftar Produk <span style="color:#9a8e96;font-weight:500">(<?php echo count($rows); ?> ditampilkan)</span></div>
        <?php if ($search !== '' || $statusFilter !== 'all' || $featuredFilter !== 'all'): ?>
            <a href="<?php echo page_url('admin/produk.php'); ?>" style="color:#d64a8a;text-decoration:none;font-size:13px;font-weight:700">Reset Filter</a>
        <?php endif; ?>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Unggulan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6" style="text-align:center;padding:35px;color:#8b8088">Produk tidak ditemukan.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;gap:11px;align-items:center">
                            <img class="admin-product-img" src="<?php echo htmlspecialchars(page_url($p['image'])); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" onerror="this.style.display='none'">
                            <div>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <div style="font-size:11px;color:#968c93"><?php echo htmlspecialchars($p['id']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($p['category']); ?></td>
                    <td>Rp<?php echo number_format((float)$p['price'], 0, ',', '.'); ?> / <?php echo htmlspecialchars($p['unit']); ?></td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border-radius:999px;background:<?php echo $p['status'] === 'active' ? '#eaf8ef' : '#f4f1f3'; ?>;color:<?php echo $p['status'] === 'active' ? '#287a47' : '#817982'; ?>;font-size:12px;font-weight:700">
                            <i class="bi <?php echo $p['status'] === 'active' ? 'bi-check-circle-fill' : 'bi-pause-circle-fill'; ?>"></i>
                            <?php echo $p['status'] === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="mode" value="featured">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                            <button type="submit" title="<?php echo $p['is_featured'] ? 'Jadikan bukan unggulan' : 'Jadikan unggulan'; ?>" style="border:0;background:<?php echo $p['is_featured'] ? '#fff4cf' : '#f7f4f5'; ?>;color:<?php echo $p['is_featured'] ? '#b47b00' : '#9a9097'; ?>;padding:7px 10px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer">
                                <i class="bi <?php echo $p['is_featured'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                <?php echo $p['is_featured'] ? 'Unggulan' : 'Jadikan Unggulan'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="admin-actions">
                            <a href="<?php echo page_url('admin/produk.php?action=edit&id=' . urlencode($p['id'])); ?>"><i class="bi bi-pencil"></i> Edit</a>

                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="mode" value="toggle">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                                <button type="submit" style="border:0;background:#fff1f6;color:#d64a8a;padding:7px 10px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer">
                                    <i class="bi bi-power"></i> <?php echo $p['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                </button>
                            </form>

                            <form method="post" style="display:inline" onsubmit="return confirm(<?php echo htmlspecialchars(json_encode('Hapus produk "' . $p['name'] . '"? Tindakan ini tidak dapat dibatalkan.', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>)">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="mode" value="delete">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                                <button type="submit" style="border:0;background:#fff0f0;color:#b44141;padding:7px 10px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
