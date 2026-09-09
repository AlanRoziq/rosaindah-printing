<?php
/**
 * Master kategori produk Rosa Indah Printing.
 * Satu produk hanya memiliki satu kategori yang spesifik.
 */

function rosa_product_categories(): array
{
    return [
        'Undangan Nikah',
        'Print Scan Copy',
        'MUG',
        'Banner',
        'Cetak Buku',
        'Lanyard & Id Card',
        'Spanduk',
        'Kartu Nama',
        'Sticker',
        'Undangan Lipat 3',
        'Nota / Buku Tabungan',
        'Kalender',
        'Sablon DTF',
        'Cetak Foto',
        'Cetak Pigora',
        'Gantungan Kunci',
    ];
}

/**
 * Sinkronisasi kategori master + migrasi produk bawaan/produk lama yang
 * masih memakai kategori generik dari versi sebelumnya.
 * Aman dipanggil berulang kali karena update hanya dilakukan untuk kategori
 * lama yang masih dikenali.
 */
function ensure_rosa_product_categories(PDO $pdo): void
{
    $categories = rosa_product_categories();

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $insert = $pdo->prepare('INSERT IGNORE INTO categories (name) VALUES (?)');
    foreach ($categories as $name) {
        $insert->execute([$name]);
    }

    // Migrasi produk bawaan berdasarkan ID yang sudah dikenal.
    $idMap = [
        'undangan-nikah' => 'Undangan Nikah',
        'print-scan-copy' => 'Print Scan Copy',
        'mug-custom' => 'MUG',
        'banner' => 'Banner',
        'buku' => 'Cetak Buku',
        'lanyard-id-card' => 'Lanyard & Id Card',
        'spanduk' => 'Spanduk',
        'kartu-nama' => 'Kartu Nama',
        'sticker' => 'Sticker',
        'undangan-lipat-3' => 'Undangan Lipat 3',
        'nota' => 'Nota / Buku Tabungan',
        'buku-tabungan' => 'Nota / Buku Tabungan',
        'kalender' => 'Kalender',
        'sablon-dtf' => 'Sablon DTF',
        'cetak-foto' => 'Cetak Foto',
        'pigora' => 'Cetak Pigora',
        'gantungan-kunci' => 'Gantungan Kunci',
    ];

    $update = $pdo->prepare('UPDATE products SET category=? WHERE id=?');
    foreach ($idMap as $id => $category) {
        $update->execute([$category, $id]);
    }

    // Migrasi produk lama berdasarkan nama agar data lama tidak lagi memakai
    // kategori generik. Produk yang sudah memakai kategori baru tidak disentuh.
    $legacyRows = $pdo->query("SELECT id, name, category FROM products WHERE category IN ('Undangan','Percetakan','Promosi','Foto & Souvenir','Alat Promosi','Custom')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($legacyRows as $row) {
        $name = strtolower(trim((string)$row['name']));
        $target = null;

        if (str_contains($name, 'lipat 3') || str_contains($name, 'lipat tiga')) {
            $target = 'Undangan Lipat 3';
        } elseif (str_contains($name, 'undangan') && (str_contains($name, 'nikah') || str_contains($name, 'wedding'))) {
            $target = 'Undangan Nikah';
        } elseif (str_contains($name, 'print') && (str_contains($name, 'scan') || str_contains($name, 'copy'))) {
            $target = 'Print Scan Copy';
        } elseif (str_contains($name, 'mug') || str_contains($name, 'tumbler')) {
            $target = 'MUG';
        } elseif (str_contains($name, 'spanduk')) {
            $target = 'Spanduk';
        } elseif (str_contains($name, 'banner')) {
            $target = 'Banner';
        } elseif (str_contains($name, 'buku tabungan') || str_contains($name, 'tabungan') || str_contains($name, 'nota')) {
            $target = 'Nota / Buku Tabungan';
        } elseif (str_contains($name, 'buku')) {
            $target = 'Cetak Buku';
        } elseif (str_contains($name, 'lanyard') || str_contains($name, 'id card') || str_contains($name, 'idcard')) {
            $target = 'Lanyard & Id Card';
        } elseif (str_contains($name, 'kartu nama')) {
            $target = 'Kartu Nama';
        } elseif (str_contains($name, 'sticker') || str_contains($name, 'stiker')) {
            $target = 'Sticker';
        } elseif (str_contains($name, 'kalender')) {
            $target = 'Kalender';
        } elseif (str_contains($name, 'dtf') || str_contains($name, 'sablon')) {
            $target = 'Sablon DTF';
        } elseif (str_contains($name, 'pigora') || str_contains($name, 'figura')) {
            $target = 'Cetak Pigora';
        } elseif (str_contains($name, 'gantungan kunci') || str_contains($name, 'ganci') || str_contains($name, 'keychain')) {
            $target = 'Gantungan Kunci';
        } elseif (str_contains($name, 'foto') || str_contains($name, 'photo')) {
            $target = 'Cetak Foto';
        }

        if ($target !== null) {
            $update->execute([$target, $row['id']]);
        }
    }
}
