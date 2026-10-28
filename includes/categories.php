<!-- =========================
     KATEGORI PRODUK
========================== -->
<section class="product-category">
    <div class="container">
        <div class="section-title">
            <div>
                <span>KATEGORI PRODUK</span>
                <h2>Pilihan Kategori Percetakan</h2>
                <p>Temukan produk cetak berkualitas sesuai kebutuhan bisnis, acara, dan promosi Anda.</p>
            </div>
            <a href="<?php echo page_url('produk.php'); ?>" class="lihat-semua">Lihat Semua →</a>
        </div>

        <div class="category-row">
            <?php
            // Nama pada kartu boleh berbeda dari nama kategori database.
            // Nilai `category` di bawah HARUS sama dengan kategori pada tabel products.
            $homeCategories = [
                ['image' => 'undnikah.jpeg', 'name' => 'Undangan Nikah', 'desc' => 'Undangan Katalog/Custom', 'category' => 'Undangan Nikah'],
                ['image' => 'psc.jpeg', 'name' => 'Print Scan Copy', 'desc' => 'Cetak Cepat', 'category' => 'Print Scan Copy'],
                ['image' => 'mug.png', 'name' => 'MUG custom', 'desc' => 'Gelas / Tumbler', 'category' => 'MUG'],
                ['image' => 'banner.png', 'name' => 'Banner', 'desc' => 'Indoor / Outdoor', 'category' => 'Banner'],
                ['image' => 'buku.png', 'name' => 'Cetak Buku', 'desc' => 'Custom Nama & Gambar', 'category' => 'Cetak Buku'],
                ['image' => 'lanyard.png', 'name' => 'Lanyard & Id Card', 'desc' => 'Semua Ukuran', 'category' => 'Lanyard & Id Card'],
                ['image' => 'spanduk.png', 'name' => 'Spanduk', 'desc' => 'Ukuran bebas', 'category' => 'Spanduk'],
                ['image' => 'kartunama.png', 'name' => 'Kartu Nama', 'desc' => 'Identitas bisnis', 'category' => 'Kartu Nama'],
                ['image' => 'sticker.jpeg', 'name' => 'Sticker', 'desc' => 'Custom', 'category' => 'Sticker'],
                ['image' => 'undf4.png', 'name' => 'Undangan Lipat 3', 'desc' => 'Yasin, Tahlil, Walimah, Aqiqah', 'category' => 'Undangan Lipat 3'],
                ['image' => 'nota.jpeg', 'name' => 'Nota / Buku Tabungan', 'desc' => 'Souvenir', 'category' => 'Nota / Buku Tabungan'],
                ['image' => 'kalender.png', 'name' => 'Kalender', 'desc' => 'Meja & Dinding', 'category' => 'Kalender'],
                ['image' => 'sablon.png', 'name' => 'Sablon DTF', 'desc' => 'Sablon Custom', 'category' => 'Sablon DTF'],
                ['image' => 'foto.jpeg', 'name' => 'Cetak Foto', 'desc' => 'Abadikan Momen terbaikmu', 'category' => 'Cetak Foto'],
                ['image' => 'pigora.jpeg', 'name' => 'Cetak Pigora A4', 'desc' => 'Semua Ukuran', 'category' => 'Cetak Pigora'],
                ['image' => 'ganci.png', 'name' => 'Gantungan Kunci', 'desc' => 'Semua Ukuran', 'category' => 'Gantungan Kunci'],
            ];
            ?>

            <?php foreach ($homeCategories as $cat): ?>
                <a
                    class="category-card"
                    href="<?php echo page_url('produk.php?kategori=' . urlencode($cat['category'])); ?>"
                    aria-label="Lihat produk kategori <?php echo htmlspecialchars($cat['category']); ?>">
                    <img src="<?php echo asset_url('images/products/' . $cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" loading="lazy">
                    <h4><?php echo htmlspecialchars($cat['name']); ?></h4>
                    <p><?php echo htmlspecialchars($cat['desc']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>