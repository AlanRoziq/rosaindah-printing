<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Data Produk Percetakan Rosa Indah Printing
$daftar_produk = [
    [
        'id' => 1,
        'nama' => 'Spanduk / Banner Outdoor Flexi',
        'kategori' => 'banner',
        'kategori_label' => 'Spanduk & Banner',
        'harga' => 18000,
        'satuan' => 'm²',
        'harga_coret' => 25000,
        'rating' => 4.9,
        'terjual' => '2.4k',
        'badge' => 'Terlaris',
        'gambar' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Cetak banner & spanduk outdoor tahan cuaca, warna tajam, bahan Flexi standar hingga tebal (Korchin/Korea). Cocok untuk promosi toko, event, dan warung.',
        'pilihan_bahan' => ['Flexi Standar 280gr', 'Flexi Medium 340gr', 'Flexi Korchin 440gr (High Res)'],
        'finishing' => ['Mata Ayam (Plong Sudut)', 'Lipat Lem Saja', 'Lebihan Putih', 'Slongsong Bambu']
    ],
    [
        'id' => 2,
        'nama' => 'Stiker Vinyl Custom (Kiss Cut / Die Cut)',
        'kategori' => 'stiker',
        'kategori_label' => 'Stiker & Label',
        'harga' => 12000,
        'satuan' => 'lembar A3+',
        'harga_coret' => 15000,
        'rating' => 4.9,
        'terjual' => '5.8k',
        'badge' => 'Diskon 20%',
        'gambar' => 'https://images.unsplash.com/photo-1572375992501-4b0892d50c69?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Stiker label kemasan botol, toples, makanan, & merchandise. Tahan air, anti sobek, sudah dipotong sesuai pola (Kiss cut tinggal lepas).',
        'pilihan_bahan' => ['Vinyl Glossy Anti Air', 'Vinyl Doff / Matte', 'Stiker Transparan', 'Chromo Ekonomis'],
        'finishing' => ['Kiss Cut (Potong Setengah)', 'Die Cut (Potong Putus)', 'Laminasi Dingin Glossy', 'Laminasi Doff']
    ],
    [
        'id' => 3,
        'nama' => 'Brosur & Flyer Promosi Full Color',
        'kategori' => 'brosur',
        'kategori_label' => 'Brosur & Flyer',
        'harga' => 175000,
        'satuan' => 'rim (500 lbr)',
        'harga_coret' => 220000,
        'rating' => 4.8,
        'terjual' => '1.1k',
        'badge' => 'Promo Rim',
        'gambar' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Cetak brosur full colour 1 sisi atau 2 sisi. Menggunakan kertas Art Paper berkualitas tinggi, cetakan tajam tidak luntur.',
        'pilihan_bahan' => ['Art Paper 120gr', 'Art Paper 150gr', 'HVS 80gr (Hemat)'],
        'finishing' => ['Tanpa Lipat', 'Lipat 2 (Bifold)', 'Lipat 3 (Trifold)']
    ],
    [
        'id' => 4,
        'nama' => 'Kartu Nama Premium (1 Box isi 100)',
        'kategori' => 'kartu_nama',
        'kategori_label' => 'Kartu Nama',
        'harga' => 35000,
        'satuan' => 'box',
        'harga_coret' => 45000,
        'rating' => 5.0,
        'terjual' => '3.2k',
        'badge' => 'Premium',
        'gambar' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Kartu nama profesional dengan bahan Art Carton tebal 260gr lengkap dengan laminasi doff/glossy dan gratis box mika eksklusif.',
        'pilihan_bahan' => ['Art Carton 260gr', 'Art Carton 310gr (Ekstra Tebal)', 'BW Linen Bertekstur'],
        'finishing' => ['Laminasi Doff 2 Sisi', 'Laminasi Glossy 2 Sisi', 'Sudut Bulat (Rounded)', 'Tanpa Laminasi']
    ],
    [
        'id' => 5,
        'nama' => 'X-Banner & Y-Banner Portable (1 Set)',
        'kategori' => 'banner',
        'kategori_label' => 'Spanduk & Banner',
        'harga' => 65000,
        'satuan' => 'set',
        'harga_coret' => 85000,
        'rating' => 4.8,
        'terjual' => '940',
        'badge' => 'Siap Pasang',
        'gambar' => 'https://images.unsplash.com/photo-1542744094-3a31f272c490?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Stand banner promosi ukuran 60x160 cm, sudah termasuk rangka tiang fiber kokoh dan cetak bahan albatros/fleksi beresolusi tinggi.',
        'pilihan_bahan' => ['Bahan Albatros + Laminasi Doff (Indoor Halus)', 'Bahan Flexi 340gr (Outdoor)'],
        'finishing' => ['Rangka X-Banner Standar', 'Rangka Y-Banner Besi', 'Roll Up Banner Aluminium']
    ],
    [
        'id' => 6,
        'nama' => 'Kaos Custom DTF Sablon Satuan & Lusinan',
        'kategori' => 'kaos',
        'kategori_label' => 'Kaos & Sablon',
        'harga' => 55000,
        'satuan' => 'pcs',
        'harga_coret' => 70000,
        'rating' => 4.9,
        'terjual' => '1.7k',
        'badge' => 'Tanpa Min. Order',
        'gambar' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Sablon kaos katun combed 30s premium dengan teknologi cetak DTF full color. Lembut, warna cerah, awet dicuci berkali-kali.',
        'pilihan_bahan' => ['Cotton Combed 30s Premium', 'Cotton Combed 24s (Lebih Tebal)'],
        'finishing' => ['Ukuran Desain A4', 'Ukuran Desain A3', 'Sablon Depan Saja', 'Sablon Depan + Belakang']
    ],
    [
        'id' => 7,
        'nama' => 'Mug Keramik Custom Cetak Sublim',
        'kategori' => 'merchandise',
        'kategori_label' => 'Merchandise',
        'harga' => 20000,
        'satuan' => 'pcs',
        'harga_coret' => 28000,
        'rating' => 4.7,
        'terjual' => '810',
        'badge' => 'Souvenir',
        'gambar' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Mug keramik putih SNI berkualitas tinggi. Cocok untuk hadiah ulang tahun, souvenir kantor, pernikahan, dan promosi bisnis.',
        'pilihan_bahan' => ['Mug SNI Putih Polos', 'Mug Bunglon (Berubah Warna saat Panas)'],
        'finishing' => ['Kemasan Box Putih', 'Box + Pita Souvenir']
    ],
    [
        'id' => 8,
        'nama' => 'Nota / Surat Jalan / Kwitansi NCR (2 Ply)',
        'kategori' => 'stationery',
        'kategori_label' => 'Buku & Nota',
        'harga' => 135000,
        'satuan' => 'paket (10 buku)',
        'harga_coret' => 160000,
        'rating' => 4.9,
        'terjual' => '2.1k',
        'badge' => 'Usaha',
        'gambar' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=600&auto=format&fit=crop&q=80',
        'deskripsi' => 'Cetak nota rangkap 2 atau 3 otomatis tembus tanpa karbon. Sudah termasuk porporasi (garis putus) dan nomor urut (numerator).',
        'pilihan_bahan' => ['NCR 2 Rangkap (Putih - Merah/Kuning)', 'NCR 3 Rangkap (Putih - Merah - Kuning)'],
        'finishing' => ['Nomor Urut (Numerator)', 'Jilid Lem Porporasi']
    ]
];

// Hitung total item keranjang
$total_keranjang = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_keranjang += (int)($item['qty'] ?? 1);
}
?>
<?php include 'includes/header.php'; ?>

<!-- Integrasi Tailwind & Ikon untuk Katalog Produk Pink Rosa Indah -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    /* Pastikan ikon keranjang di header atas disembunyikan pada tampilan mobile */
    @media (max-width: 767px) {
        .main-header a[href*="keranjang"],
        .main-header .cart-btn,
        .main-header .header-cart {
            display: none !important;
        }
    }
</style>

<!-- HEADER UTAMA SAMA PERSIS DENGAN INDEX.PHP -->
<header class="main-header">

    <?php include 'includes/topbar.php'; ?>

    <?php include 'includes/navbar.php'; ?>

</header>

<!-- BANNER JUDUL HALAMAN & FILTER KATEGORI PINK -->
<section class="bg-gradient-to-r from-pink-600 via-rose-500 to-pink-500 text-white py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-block bg-white/20 backdrop-blur-sm text-white text-xs font-bold px-3 py-1 rounded-full mb-2 uppercase tracking-wider">
                    Rosa Indah Digital Printing
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Katalog Produk & Layanan Cetak</h1>
                <p class="text-pink-100 text-xs sm:text-sm mt-1">Pilih produk percetakan berkualitas tinggi, tentukan spesifikasi, dan pesan langsung</p>
            </div>

            <!-- Kolom Pencarian Cepat -->
            <div class="w-full md:w-80">
                <div class="relative">
                    <input 
                        type="text" 
                        id="search-input"
                        placeholder="Cari spanduk, stiker, brosur, nota..." 
                        class="w-full pl-10 pr-4 py-2.5 text-sm text-slate-800 bg-white rounded-full shadow-md focus:ring-2 focus:ring-pink-300 outline-none"
                        onkeyup="filterProdukLive()"
                    >
                    <i data-lucide="search" class="w-4 h-4 text-pink-500 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BAR FILTER KATEGORI PINK -->
<div class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-pink-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
            <button onclick="setKategori('semua')" class="filter-btn active whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-bold bg-pink-600 text-white shadow-sm shadow-pink-200 transition" data-category="semua">
                Semua Produk
            </button>
            <button onclick="setKategori('banner')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="banner">
                Spanduk & Banner
            </button>
            <button onclick="setKategori('stiker')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="stiker">
                Stiker & Label
            </button>
            <button onclick="setKategori('brosur')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="brosur">
                Brosur & Flyer
            </button>
            <button onclick="setKategori('kartu_nama')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="kartu_nama">
                Kartu Nama
            </button>
            <button onclick="setKategori('kaos')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="kaos">
                Kaos & Sablon
            </button>
            <button onclick="setKategori('merchandise')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="merchandise">
                Merchandise
            </button>
            <button onclick="setKategori('stationery')" class="filter-btn whitespace-nowrap px-4 py-1.5 rounded-full text-xs font-semibold bg-pink-50 hover:bg-pink-100 text-pink-700 transition" data-category="stationery">
                Buku & Nota
            </button>
        </div>

        <!-- Urutkan & Keranjang Desktop (Disembunyikan di HP: hidden md:flex) -->
        <div class="hidden md:flex items-center gap-3 flex-shrink-0">
            <select id="sort-select" onchange="sortProduk()" class="text-xs font-semibold bg-pink-50 border border-pink-200 rounded-full px-3.5 py-1.5 text-pink-700 outline-none focus:border-pink-500 cursor-pointer">
                <option value="terpopuler">Paling Populer</option>
                <option value="harga_terendah">Harga Terendah</option>
                <option value="harga_tertinggi">Harga Tertinggi</option>
            </select>

            <a href="keranjang.php" class="hidden md:flex relative p-2 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-600 transition items-center justify-center" title="Keranjang Belanja">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span id="cart-counter-header" class="absolute -top-1 -right-1 bg-pink-600 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white shadow">
                    <?= $total_keranjang ?>
                </span>
            </a>
        </div>
    </div>
</div>

<!-- DAFTAR KARTU PRODUK BERTEMA PINK -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 md:pb-12">
    <!-- Sortir di Mobile -->
    <div class="flex md:hidden items-center justify-between mb-4">
        <span class="text-xs font-semibold text-slate-500">Menampilkan Katalog Produk</span>
        <select id="sort-select-mobile" onchange="document.getElementById('sort-select').value=this.value; sortProduk();" class="text-xs font-semibold bg-white border border-pink-200 rounded-lg px-3 py-1.5 text-pink-700 outline-none">
            <option value="terpopuler">Paling Populer</option>
            <option value="harga_terendah">Harga Terendah</option>
            <option value="harga_tertinggi">Harga Tertinggi</option>
        </select>
    </div>

    <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
        <?php foreach ($daftar_produk as $item): ?>
            <div class="product-card group bg-white rounded-2xl border border-pink-100 hover:border-pink-400 overflow-hidden shadow-sm hover:shadow-lg hover:shadow-pink-100/60 transition-all duration-300 flex flex-col justify-between"
                 data-id="<?= $item['id'] ?>"
                 data-kategori="<?= $item['kategori'] ?>"
                 data-nama="<?= strtolower($item['nama']) ?>"
                 data-harga="<?= $item['harga'] ?>">
                
                <div>
                    <!-- Gambar Produk -->
                    <div class="relative aspect-square w-full overflow-hidden bg-pink-50/40 cursor-pointer" onclick="openDetailModal(<?= $item['id'] ?>)">
                        <img 
                            src="<?= htmlspecialchars($item['gambar']) ?>" 
                            alt="<?= htmlspecialchars($item['nama']) ?>" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        >
                        <!-- Badge Pink -->
                        <span class="absolute top-2.5 left-2.5 bg-gradient-to-r from-pink-600 to-rose-500 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full shadow-sm">
                            <?= htmlspecialchars($item['badge']) ?>
                        </span>

                        <!-- Rating -->
                        <div class="absolute bottom-2 right-2 bg-black/65 backdrop-blur-sm text-white text-[11px] font-medium px-2 py-0.5 rounded-md flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400"></i>
                            <span><?= $item['rating'] ?></span>
                        </div>
                    </div>

                    <!-- Detail Card -->
                    <div class="p-3.5 sm:p-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-pink-600">
                            <?= htmlspecialchars($item['kategori_label']) ?>
                        </span>
                        
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm line-clamp-2 mt-1 leading-snug group-hover:text-pink-600 transition cursor-pointer" onclick="openDetailModal(<?= $item['id'] ?>)">
                            <?= htmlspecialchars($item['nama']) ?>
                        </h3>

                        <div class="mt-2.5 flex items-baseline gap-1.5 flex-wrap">
                            <span class="text-sm sm:text-base font-extrabold text-pink-600">
                                Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                            </span>
                            <span class="text-[11px] text-slate-400">/ <?= htmlspecialchars($item['satuan']) ?></span>
                        </div>

                        <?php if ($item['harga_coret']): ?>
                            <div class="text-[10px] text-slate-400 line-through">
                                Rp <?= number_format($item['harga_coret'], 0, ',', '.') ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-[11px] text-slate-500 mt-2 flex items-center gap-1">
                            <i data-lucide="check-circle" class="w-3 h-3 text-pink-500"></i>
                            <span>Terjual <?= htmlspecialchars($item['terjual']) ?>+</span>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Card Pink -->
                <div class="p-3.5 sm:p-4 pt-0">
                    <button 
                        onclick="openDetailModal(<?= $item['id'] ?>)"
                        class="w-full py-2.5 px-3 bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-700 hover:to-rose-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all shadow-sm shadow-pink-200 active:scale-95"
                    >
                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                        <span>Pesan Sekarang</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- State Kosong Jika Tidak Ditemukan -->
    <div id="empty-state" class="hidden text-center py-16">
        <div class="w-16 h-16 bg-pink-50 rounded-full flex items-center justify-center mx-auto mb-3 text-pink-500">
            <i data-lucide="search-x" class="w-8 h-8"></i>
        </div>
        <h3 class="font-bold text-slate-700">Produk Tidak Ditemukan</h3>
        <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci lain atau pilih kategori 'Semua Produk'</p>
    </div>
</main>

<!-- MODAL POPUP SPESIFIKASI & ORDER (TEMA PINK) -->
<div id="detail-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-pink-100">
        <!-- Header Gambar Modal -->
        <div class="relative bg-pink-50 aspect-video w-full">
            <img id="modal-img" src="" alt="Detail Produk" class="w-full h-full object-cover">
            <button onclick="closeModal()" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/50 text-white hover:bg-pink-600 flex items-center justify-center transition">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
            <div class="absolute bottom-3 left-3">
                <span id="modal-badge" class="bg-pink-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow"></span>
            </div>
        </div>

        <!-- Isi Modal -->
        <div class="p-5 sm:p-6 space-y-4 max-h-[60vh] overflow-y-auto">
            <div>
                <span id="modal-kategori" class="text-[11px] font-bold text-pink-600 uppercase tracking-wider"></span>
                <h2 id="modal-nama" class="text-lg sm:text-xl font-extrabold text-slate-900 mt-0.5"></h2>
                <p id="modal-deskripsi" class="text-xs text-slate-500 mt-1.5 leading-relaxed"></p>
            </div>

            <!-- Pilihan Bahan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Pilihan Bahan</label>
                <div id="modal-bahan-container" class="grid grid-cols-1 gap-2"></div>
            </div>

            <!-- Pilihan Finishing -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1.5">Finishing / Opsi</label>
                <div id="modal-finishing-container" class="grid grid-cols-1 gap-2"></div>
            </div>

            <!-- Jumlah / Kuantitas -->
            <div class="flex items-center justify-between p-3 bg-pink-50/60 rounded-2xl border border-pink-100">
                <div>
                    <span class="text-xs font-bold text-slate-700">Jumlah Pesanan</span>
                    <p class="text-[10px] text-slate-400" id="modal-satuan-label">Per satuan</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="updateQty(-1)" class="w-8 h-8 rounded-lg bg-white border border-pink-200 font-bold text-pink-600 flex items-center justify-center active:scale-95 shadow-sm">-</button>
                    <span id="modal-qty" class="text-sm font-extrabold text-slate-900 w-6 text-center">1</span>
                    <button onclick="updateQty(1)" class="w-8 h-8 rounded-lg bg-pink-600 text-white font-bold flex items-center justify-center active:scale-95 shadow-sm">+</button>
                </div>
            </div>

            <!-- Total Estimasi -->
            <div class="flex items-center justify-between pt-2 border-t border-pink-100">
                <span class="text-xs text-slate-500 font-medium">Total Estimasi:</span>
                <span id="modal-total-harga" class="text-lg font-black text-pink-600">Rp 0</span>
            </div>
        </div>

        <!-- Tombol Bawah Modal -->
        <div class="p-4 sm:p-5 bg-pink-50/40 border-t border-pink-100 flex gap-2.5">
            <button onclick="tambahKeKeranjangModal()" class="flex-1 py-3 bg-pink-600 hover:bg-pink-700 text-white text-xs sm:text-sm font-bold rounded-2xl flex items-center justify-center gap-2 transition active:scale-95 shadow-sm shadow-pink-200">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>+ Keranjang</span>
            </button>
            <button onclick="pesanWhatsAppModal()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold rounded-2xl flex items-center justify-center gap-2 transition active:scale-95 shadow-sm">
                <i data-lucide="message-circle" class="w-4 h-4"></i>
                <span>Pesan via WA</span>
            </button>
        </div>
    </div>
</div>

<!-- BOTTOM NAVIGATION KHUSUS MOBILE (Keranjang Tampil di Sini untuk HP) -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-lg border-t border-pink-100 px-3 py-1.5 shadow-[0_-4px_20px_rgba(236,72,153,0.08)]">
    <div class="flex items-center justify-around">
        <a href="index.php" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-400 hover:text-pink-600 transition">
            <i data-lucide="home" class="w-5 h-5"></i>
            <span class="text-[10px] font-medium">Beranda</span>
        </a>

        <a href="produk.php" class="flex flex-col items-center gap-1 py-1 px-3 text-pink-600 font-bold">
            <div class="relative">
                <i data-lucide="grid" class="w-5 h-5"></i>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-pink-600 rounded-full"></span>
            </div>
            <span class="text-[10px]">Produk</span>
        </a>

        <a href="keranjang.php" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-500 hover:text-pink-600 transition relative">
            <div class="relative">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span id="cart-counter-bottom" class="absolute -top-1.5 -right-2.5 bg-pink-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                    <?= $total_keranjang ?>
                </span>
            </div>
            <span class="text-[10px] font-medium">Keranjang</span>
        </a>

        <a href="pesanan.php" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-400 hover:text-pink-600 transition">
            <i data-lucide="clipboard-list" class="w-5 h-5"></i>
            <span class="text-[10px] font-medium">Pesanan</span>
        </a>

        <a href="login.php" class="flex flex-col items-center gap-1 py-1 px-3 text-slate-400 hover:text-pink-600 transition">
            <i data-lucide="user" class="w-5 h-5"></i>
            <span class="text-[10px] font-medium">Akun</span>
        </a>
    </div>
</nav>

<?php include 'includes/cta.php'; ?>

<?php include 'includes/footer.php'; ?>

<script>
    const produkData = <?= json_encode($daftar_produk) ?>;
    let selectedProduct = null;
    let currentQty = 1;
    let selectedBahan = '';
    let selectedFinishing = '';

    lucide.createIcons();

    function setKategori(kat) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            if (btn.dataset.category === kat) {
                btn.classList.add('bg-pink-600', 'text-white', 'shadow-sm', 'shadow-pink-200');
                btn.classList.remove('bg-pink-50', 'text-pink-700');
            } else {
                btn.classList.remove('bg-pink-600', 'text-white', 'shadow-sm', 'shadow-pink-200');
                btn.classList.add('bg-pink-50', 'text-pink-700');
            }
        });

        const cards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        cards.forEach(card => {
            if (kat === 'semua' || card.dataset.kategori === kat) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('empty-state').classList.toggle('hidden', visibleCount > 0);
    }

    function filterProdukLive() {
        const query = document.getElementById('search-input').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const nama = card.dataset.nama;
            if (nama.includes(query)) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('empty-state').classList.toggle('hidden', visibleCount > 0);
    }

    function sortProduk() {
        const container = document.getElementById('product-grid');
        const cards = Array.from(document.querySelectorAll('.product-card'));
        const sortBy = document.getElementById('sort-select').value;

        cards.sort((a, b) => {
            if (sortBy === 'harga_terendah') {
                return parseInt(a.dataset.harga) - parseInt(b.dataset.harga);
            } else if (sortBy === 'harga_tertinggi') {
                return parseInt(b.dataset.harga) - parseInt(a.dataset.harga);
            }
            return parseInt(a.dataset.id) - parseInt(b.dataset.id);
        });

        cards.forEach(card => container.appendChild(card));
    }

    function openDetailModal(id) {
        selectedProduct = produkData.find(p => p.id === id);
        if (!selectedProduct) return;

        currentQty = 1;
        selectedBahan = selectedProduct.pilihan_bahan[0];
        selectedFinishing = selectedProduct.finishing[0];

        document.getElementById('modal-img').src = selectedProduct.gambar;
        document.getElementById('modal-badge').innerText = selectedProduct.badge;
        document.getElementById('modal-kategori').innerText = selectedProduct.kategori_label;
        document.getElementById('modal-nama').innerText = selectedProduct.nama;
        document.getElementById('modal-deskripsi').innerText = selectedProduct.deskripsi;
        document.getElementById('modal-satuan-label').innerText = 'Per ' + selectedProduct.satuan;
        document.getElementById('modal-qty').innerText = currentQty;

        const bahanContainer = document.getElementById('modal-bahan-container');
        bahanContainer.innerHTML = selectedProduct.pilihan_bahan.map((b, i) => `
            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-pink-100 hover:border-pink-500 cursor-pointer text-xs font-medium bg-white">
                <input type="radio" name="pilihan_bahan" value="${b}" ${i === 0 ? 'checked' : ''} onchange="selectedBahan='${b}'; updateModalTotal();" class="accent-pink-600">
                <span>${b}</span>
            </label>
        `).join('');

        const finishingContainer = document.getElementById('modal-finishing-container');
        finishingContainer.innerHTML = selectedProduct.finishing.map((f, i) => `
            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-pink-100 hover:border-pink-500 cursor-pointer text-xs font-medium bg-white">
                <input type="radio" name="pilihan_finishing" value="${f}" ${i === 0 ? 'checked' : ''} onchange="selectedFinishing='${f}';" class="accent-pink-600">
                <span>${f}</span>
            </label>
        `).join('');

        updateModalTotal();

        const modal = document.getElementById('detail-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        lucide.createIcons();
    }

    function updateQty(delta) {
        currentQty = Math.max(1, currentQty + delta);
        document.getElementById('modal-qty').innerText = currentQty;
        updateModalTotal();
    }

    function updateModalTotal() {
        if (!selectedProduct) return;
        const total = selectedProduct.harga * currentQty;
        document.getElementById('modal-total-harga').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }

    function closeModal() {
        const modal = document.getElementById('detail-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function tambahKeKeranjangModal() {
        if (!selectedProduct) return;

        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        cart.push({
            id: selectedProduct.id,
            nama: selectedProduct.nama,
            harga: selectedProduct.harga,
            qty: currentQty,
            bahan: selectedBahan,
            finishing: selectedFinishing,
            satuan: selectedProduct.satuan
        });
        localStorage.setItem('cart', JSON.stringify(cart));

        const totalItems = cart.reduce((acc, curr) => acc + curr.qty, 0);
        const headerBadge = document.getElementById('cart-counter-header');
        const bottomBadge = document.getElementById('cart-counter-bottom');
        if (headerBadge) headerBadge.innerText = totalItems;
        if (bottomBadge) bottomBadge.innerText = totalItems;

        closeModal();
        alert(`Berhasil menambahkan ${currentQty} ${selectedProduct.nama} ke keranjang!`);
    }

    function pesanWhatsAppModal() {
        if (!selectedProduct) return;
        const noWA = "6281234567890";
        const total = (selectedProduct.harga * currentQty).toLocaleString('id-ID');
        const pesan = `Halo Rosa Indah Printing, saya mau pesan:%0A%0A` +
                      `*Produk:* ${selectedProduct.nama}%0A` +
                      `*Bahan:* ${selectedBahan}%0A` +
                      `*Finishing:* ${selectedFinishing}%0A` +
                      `*Jumlah:* ${currentQty} ${selectedProduct.satuan}%0A` +
                      `*Estimasi Total:* Rp ${total}%0A%0A` +
                      `Mohon konfirmasi ketersediaan dan cara kirim filenya. Terima kasih!`;
        
        window.location.href = `https://wa.me/${noWA}?text=${pesan}`;
    }

    window.onclick = function(event) {
        const modal = document.getElementById('detail-modal');
        if (event.target === modal) {
            closeModal();
        }
    };
</script>
