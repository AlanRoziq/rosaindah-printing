<?php
/**
 * Sumber katalog produk.
 * Jika database tersedia, produk aktif diambil dari MySQL.
 * Jika database belum di-import/tersambung, katalog lama tetap tampil sebagai fallback.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/product-categories.php';

$legacyProducts = [
    ['id'=>'banner','name'=>'Banner & Spanduk','category'=>'Banner','image'=>'products/spanduk.png','price'=>25000,'unit'=>'meter','description'=>'Banner dan spanduk custom dengan hasil cetak tajam dan warna cerah untuk kebutuhan promosi maupun acara.','features'=>['Ukuran bebas','Full color','Bahan pilihan','Siap pasang']],
    ['id'=>'undangan-nikah','name'=>'Undangan Nikah','category'=>'Undangan Nikah','image'=>'products/undnikah.jpeg','price'=>1500,'unit'=>'pcs','description'=>'Undangan pernikahan elegan yang dapat disesuaikan dengan konsep dan kebutuhan acara Anda.','features'=>['Desain custom','Pilihan kertas','Cetak full color','Bisa request finishing']],
    ['id'=>'undangan-lipat-3','name'=>'Undangan Lipat 3','category'=>'Undangan Lipat 3','image'=>'products/undf4.png','price'=>1500,'unit'=>'pcs','description'=>'Undangan lipat tiga untuk berbagai acara seperti walimah, aqiqah, tahlil, dan acara keluarga.','features'=>['Desain custom','Format lipat 3','Kertas berkualitas','Cetak tajam']],
    ['id'=>'kartu-nama','name'=>'Kartu Nama','category'=>'Kartu Nama','image'=>'products/kartunama.png','price'=>25000,'unit'=>'box','description'=>'Kartu nama profesional untuk kebutuhan bisnis, usaha, organisasi, maupun personal branding.','features'=>['Desain custom','Cetak dua sisi','Pilihan finishing','Cocok untuk bisnis']],
    ['id'=>'sticker','name'=>'Sticker Custom','category'=>'Sticker','image'=>'products/sticker.jpeg','price'=>10000,'unit'=>'lembar','description'=>'Sticker custom untuk branding produk, kemasan, promosi, label, dan kebutuhan lainnya.','features'=>['Cutting custom','Bahan pilihan','Warna tajam','Cocok untuk kemasan']],
    ['id'=>'nota','name'=>'Nota','category'=>'Nota / Buku Tabungan','image'=>'products/nota.jpeg','price'=>40000,'unit'=>'buku','description'=>'Nota usaha yang praktis dan profesional untuk pencatatan transaksi toko maupun bisnis.','features'=>['Custom nama usaha','Nomor urut','Pilihan rangkap','Layout sesuai kebutuhan']],
    ['id'=>'buku','name'=>'Cetak Buku','category'=>'Cetak Buku','image'=>'products/buku.png','price'=>30000,'unit'=>'pcs','description'=>'Layanan cetak buku custom untuk kebutuhan pribadi, sekolah, komunitas, dan bisnis.','features'=>['Ukuran custom','Cover custom','Pilihan jilid','Cetak berkualitas']],
    ['id'=>'kalender','name'=>'Kalender','category'=>'Kalender','image'=>'products/kalender.png','price'=>25000,'unit'=>'pcs','description'=>'Kalender meja atau dinding dengan desain custom untuk kebutuhan pribadi maupun promosi.','features'=>['Desain custom','Meja & dinding','Cetak full color','Cocok untuk souvenir']],
    ['id'=>'sablon-dtf','name'=>'Sablon DTF','category'=>'Sablon DTF','image'=>'products/sablon.png','price'=>75000,'unit'=>'pcs','description'=>'Sablon DTF untuk kaos dan media tekstil dengan desain custom sesuai keinginan Anda.','features'=>['Desain custom','Warna tajam','Detail bagus','Cocok untuk kaos']],
    ['id'=>'mug-custom','name'=>'Mug Custom','category'=>'MUG','image'=>'products/mug.png','price'=>35000,'unit'=>'pcs','description'=>'Mug custom untuk hadiah, souvenir, branding, acara keluarga, maupun kebutuhan usaha.','features'=>['Desain custom','Cocok untuk souvenir','Cetak full color','Pesanan satuan tersedia']],
    ['id'=>'lanyard-id-card','name'=>'Lanyard & ID Card','category'=>'Lanyard & Id Card','image'=>'products/lanyard.png','price'=>15000,'unit'=>'pcs','description'=>'Lanyard dan ID card custom untuk sekolah, kantor, event, komunitas, dan organisasi.','features'=>['Desain custom','Pilihan ukuran','Cocok untuk event','Bisa satuan/partai']],
    ['id'=>'cetak-foto','name'=>'Cetak Foto','category'=>'Cetak Foto','image'=>'products/foto.jpeg','price'=>1000,'unit'=>'lembar','description'=>'Cetak foto dengan warna tajam untuk mengabadikan momen berharga Anda.','features'=>['Berbagai ukuran','Warna tajam','Kertas foto berkualitas','Cocok untuk dokumentasi']],
    ['id'=>'pigora','name'=>'Cetak Pigora','category'=>'Cetak Pigora','image'=>'products/pigora.jpeg','price'=>35000,'unit'=>'pcs','description'=>'Cetak foto dengan pigora untuk dekorasi rumah, hadiah, kenang-kenangan, atau souvenir.','features'=>['Ukuran custom','Pilihan pigora','Cocok untuk hadiah','Finishing rapi']],
    ['id'=>'gantungan-kunci','name'=>'Gantungan Kunci','category'=>'Gantungan Kunci','image'=>'products/ganci.png','price'=>10000,'unit'=>'pcs','description'=>'Gantungan kunci custom untuk souvenir, merchandise, komunitas, maupun hadiah.','features'=>['Desain custom','Cocok untuk souvenir','Bisa partai','Bentuk menarik']],
    ['id'=>'print-scan-copy','name'=>'Print, Scan & Copy','category'=>'Print Scan Copy','image'=>'products/psc.jpeg','price'=>500,'unit'=>'lembar','description'=>'Layanan print, scan, dan copy cepat untuk kebutuhan dokumen sehari-hari.','features'=>['Proses cepat','Dokumen warna & hitam putih','Scan dokumen','Copy dokumen']],
];


function ensure_product_price_tiers_table(PDO $pdo): void {
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

function product_unit_price(array $product, int $quantity = 1): float {
    $quantity = max(1, $quantity);
    $price = (float)($product['price'] ?? 0);
    foreach (($product['pricing'] ?? []) as $tier) {
        $min = (int)($tier['min_qty'] ?? 1);
        $max = $tier['max_qty'] ?? null;
        if ($quantity >= $min && ($max === null || $quantity <= (int)$max)) {
            return (float)$tier['price'];
        }
    }
    return $price;
}

function product_image_url(string $image): string {
    $image = ltrim(str_replace('\\', '/', trim($image)), '/');

    // Produk yang di-upload dari Admin disimpan di /uploads/produk.
    // Jangan diarahkan ke /assets/images karena foldernya berbeda.
    if (str_starts_with($image, 'uploads/')) {
        return page_url($image);
    }

    // Produk bawaan/template lama tetap berada di /assets/images.
    return asset_url('images/' . $image);
}

function load_products_from_db(): ?array {
    $pdo = db();
    if (!$pdo) return null;
    try {
        ensure_product_price_tiers_table($pdo);
        $stmt = $pdo->query("SELECT id, name, category, image, price, unit, description, features FROM products WHERE status='active' ORDER BY is_featured DESC, created_at DESC, name ASC");
        $rows = $stmt->fetchAll();
        $tiers = [];
        if ($rows) {
            $ids = array_column($rows, 'id');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $tierStmt = $pdo->prepare("SELECT product_id, min_qty, max_qty, price FROM product_price_tiers WHERE product_id IN ($ph) ORDER BY product_id, min_qty ASC");
            $tierStmt->execute($ids);
            foreach ($tierStmt->fetchAll() as $t) {
                $tiers[$t['product_id']][] = ['min_qty'=>(int)$t['min_qty'], 'max_qty'=>$t['max_qty'] === null ? null : (int)$t['max_qty'], 'price'=>(float)$t['price']];
            }
        }
        foreach ($rows as &$row) {
            $row['price'] = (float)$row['price'];
            $row['features'] = json_decode($row['features'] ?? '[]', true) ?: [];
            $row['pricing'] = $tiers[$row['id']] ?? [];
        }
        return $rows;
    } catch (Throwable $e) {
        return null;
    }
}


$pdoForCategories = db();
if ($pdoForCategories) {
    try { ensure_rosa_product_categories($pdoForCategories); } catch (Throwable $e) { /* katalog tetap berjalan */ }
}

$products = load_products_from_db() ?? $legacyProducts;

