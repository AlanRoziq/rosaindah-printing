<?php
session_start();

// Inisialisasi Keranjang Belanja dalam Session jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Data Produk Percetakan ROSA INDAH PRINTING
$products = [
    [
        'id' => 1,
        'name' => 'Cetak Brosur & Flyer',
        'category' => 'Promosi',
        'price' => 125000,
        'unit' => '/ rim (500 lbr)',
        'image' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Cetak flyer & brosur full color kertas Art Paper 120/150 gsm berkualitas tinggi, hasil tajam dan tidak luntur.',
        'options' => ['Art Paper 120gr', 'Art Paper 150gr', 'Matte Paper 150gr']
    ],
    [
        'id' => 2,
        'name' => 'Spanduk Banner Flexi',
        'category' => 'Outdoor',
        'price' => 20000,
        'unit' => '/ meter',
        'image' => 'https://images.unsplash.com/photo-1542744094-3a31f272c490?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Spanduk Flexi 280gr / 340gr / Korcin 440gr. Sudah termasuk finishing mata ayam / selongsong.',
        'options' => ['Flexi Standar 280gr', 'Flexi Tebal 340gr', 'Flexi Korea 440gr']
    ],
    [
        'id' => 3,
        'name' => 'Kartu Nama Eksklusif',
        'category' => 'Stationery',
        'price' => 35000,
        'unit' => '/ box (100 pcs)',
        'image' => 'https://images.unsplash.com/photo-1589330694653-dad6d3240a2b?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Kertas Art Carton 260gr full color 2 sisi. Dilengkapi laminasi Doff atau Glossy yang elegan.',
        'options' => ['Tanpa Laminasi', 'Laminasi Doff', 'Laminasi Glossy']
    ],
    [
        'id' => 4,
        'name' => 'Stiker & Label Kemasan (A3+)',
        'category' => 'Label',
        'price' => 12000,
        'unit' => '/ lembar A3+',
        'image' => 'https://images.unsplash.com/photo-1572375992501-4b0892d50c69?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Stiker Bontax / Vinyl anti air, sudah termasuk cutting pola (kiss cut / die cut). Siap tempel.',
        'options' => ['Stiker Kromo/Bontax', 'Stiker Vinyl Putih', 'Stiker Vinyl Transparan']
    ],
    [
        'id' => 5,
        'name' => 'Nota & Kwitansi NCR',
        'category' => 'Kantor',
        'price' => 85000,
        'unit' => '/ buku (min. 10 buku)',
        'image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Cetak nota rangkap 2, 3, atau 4 ply tembus tanpa karbon. Dilengkapi nomorator dan perforasi rapi.',
        'options' => ['2 Rangkap (Ply)', '3 Rangkap (Ply)', '4 Rangkap (Ply)']
    ],
    [
        'id' => 6,
        'name' => 'Sablon Kaos & Poloshirt',
        'category' => 'Merchandise',
        'price' => 65000,
        'unit' => '/ pcs (Cotton Combed 30s)',
        'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&auto=format&fit=crop&q=80',
        'desc' => 'Kaos bahan Cotton Combed 30s adem dengan sablon DTF cetak detail tinggi full colour.',
        'options' => ['Combed 30s (DTF A4)', 'Combed 30s (DTF A3)', 'Combed 24s Tebal']
    ]
];

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk - ROSA INDAH PRINTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .brand-text {
            color: #1d4ed8;
            font-weight: 900;
            letter-spacing: -0.02em;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 pb-24 md:pb-8">

    <!-- HEADER NAVIGATION -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-700 flex items-center justify-center text-white shadow-md">
                    <i class="fa-solid fa-print text-lg"></i>
                </div>
                <div>
                    <h1 class="brand-text text-base md:text-xl uppercase leading-tight">ROSA INDAH PRINTING</h1>
                    <p class="text-[10px] md:text-xs text-gray-500 font-medium tracking-wide">Digital & Offset Printing Berkualitas</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <a href="#member" class="flex items-center space-x-1.5 text-xs md:text-sm font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition">
                    <i class="fa-regular fa-user"></i>
                    <span>Member</span>
                </a>

                <!-- ICON KERANJANG HEADER: DISEMBUNYIKAN DI MOBILE (hidden md:flex) -->
                <button onclick="openCartModal()" class="hidden md:flex items-center space-x-2 bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1.5 rounded-lg font-medium text-sm transition relative">
                    <i class="fa-solid fa-basket-shopping text-base"></i>
                    <span>Keranjang</span>
                    <span id="cartCountHeader" class="bg-blue-600 text-white text-[11px] px-1.5 py-0.2 rounded-full font-bold">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- PROMO BANNER -->
    <section class="max-w-6xl mx-auto px-4 mt-4">
        <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 rounded-2xl p-4 md:p-6 text-white shadow-md">
            <span class="bg-white/20 text-white text-[10px] md:text-xs px-2.5 py-1 rounded-full uppercase font-bold tracking-wider">Layanan Cepat & Bergaransi</span>
            <h2 class="text-lg md:text-2xl font-bold mt-2">Pilih Produk & Cetak Kebutuhan Anda</h2>
            <p class="text-xs md:text-sm text-blue-100 mt-1 max-w-xl">Klik salah satu produk percetakan di bawah ini untuk melihat detail bahan, ukuran, dan simulasi biaya pesanan.</p>
        </div>
    </section>

    <!-- KATALOG PRODUK -->
    <main class="max-w-6xl mx-auto px-4 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base md:text-lg font-bold text-gray-800">Daftar Produk Percetakan</h3>
            <span class="text-xs text-gray-500 font-medium">Klik produk untuk detail</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-3 md:gap-5">
            <?php foreach ($products as $p): ?>
                <div onclick="openProductModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                     class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-all cursor-pointer flex flex-col justify-between group">
                    <div>
                        <div class="relative overflow-hidden aspect-video bg-gray-100">
                            <img src="<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <span class="absolute top-2 left-2 bg-blue-600/90 text-white text-[9px] md:text-[11px] font-semibold px-2 py-0.5 rounded-md shadow-sm">
                                <?= $p['category'] ?>
                            </span>
                        </div>
                        <div class="p-3">
                            <h4 class="font-bold text-xs md:text-sm text-gray-900 group-hover:text-blue-600 line-clamp-1 transition">
                                <?= htmlspecialchars($p['name']) ?>
                            </h4>
                            <p class="text-[11px] md:text-xs text-gray-500 line-clamp-2 mt-1">
                                <?= htmlspecialchars($p['desc']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="p-3 pt-0 border-t border-gray-100 mt-2 flex items-center justify-between">
                        <div>
                            <div class="text-[9px] md:text-[10px] text-gray-400">Mulai dari</div>
                            <div class="text-xs md:text-sm font-bold text-blue-700"><?= formatRupiah($p['price']) ?></div>
                        </div>
                        <button class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fa-solid fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- MODAL DETAIL PRODUK -->
    <div id="productModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-3">
        <div class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl max-h-[90vh] flex flex-col">
            <div class="relative">
                <img id="modalImg" src="" alt="Detail Produk" class="w-full h-44 object-cover">
                <button onclick="closeProductModal()" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/80 transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
                <span id="modalCategory" class="absolute bottom-3 left-3 bg-blue-600 text-white text-[10px] px-2.5 py-0.5 rounded-md font-bold"></span>
            </div>

            <div class="p-4 overflow-y-auto space-y-4">
                <div>
                    <h3 id="modalName" class="text-base md:text-lg font-bold text-gray-900"></h3>
                    <div class="flex items-baseline space-x-1 mt-1">
                        <span id="modalPrice" class="text-lg md:text-xl font-extrabold text-blue-700"></span>
                        <span id="modalUnit" class="text-xs text-gray-500"></span>
                    </div>
                    <p id="modalDesc" class="text-xs md:text-sm text-gray-600 mt-2 leading-relaxed"></p>
                </div>

                <div class="border-t border-gray-100 pt-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilihan Bahan / Varian:</label>
                    <select id="modalOption" class="w-full border border-gray-300 rounded-lg p-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </select>
                </div>

                <div class="border-t border-gray-100 pt-3 flex items-center justify-between">
                    <div>
                        <label class="block text-xs font-bold text-gray-700">Jumlah Pesanan:</label>
                        <span class="text-[10px] text-gray-400">Minimal 1 pesanan</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="changeQty(-1)" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 font-bold hover:bg-gray-200">-</button>
                        <input id="modalQty" type="number" value="1" min="1" class="w-14 text-center border border-gray-300 rounded-lg py-1 text-sm font-bold" readonly>
                        <button onclick="changeQty(1)" class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 font-bold hover:bg-gray-200">+</button>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center space-x-2">
                <button onclick="addToCartFromModal()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs md:text-sm shadow-md transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Tambah ke Keranjang</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL KERANJANG -->
    <div id="cartModal" class="fixed inset-0 z-50 bg-black/60 hidden items-end md:items-center justify-center p-0 md:p-3">
        <div class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl max-h-[85vh] flex flex-col shadow-2xl">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-basket-shopping text-blue-600"></i>
                    <h3 class="font-bold text-sm md:text-base text-gray-800">Keranjang Belanja</h3>
                </div>
                <button onclick="closeCartModal()" class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div id="cartItemsList" class="p-4 overflow-y-auto space-y-3 flex-1 text-xs md:text-sm"></div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 space-y-2">
                <div class="flex items-center justify-between text-xs md:text-sm">
                    <span class="text-gray-500 font-medium">Total Perkiraan:</span>
                    <span id="cartTotalText" class="font-bold text-blue-700 text-base">Rp 0</span>
                </div>
                <button onclick="checkoutWhatsApp()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs md:text-sm shadow transition flex items-center justify-center space-x-2">
                    <i class="fa-brands fa-whatsapp text-base"></i>
                    <span>Checkout via WhatsApp</span>
                </button>
            </div>
        </div>
    </div>

    <!-- BOTTOM TAB NAVIGATION (MOBILE) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 px-4 py-2 flex items-center justify-around shadow-lg">
        <a href="produk.php" class="flex flex-col items-center text-blue-700">
            <i class="fa-solid fa-house text-lg"></i>
            <span class="text-[10px] font-bold mt-0.5">Beranda</span>
        </a>

        <a href="produk.php" class="flex flex-col items-center text-blue-700">
            <i class="fa-solid fa-layer-group text-lg"></i>
            <span class="text-[10px] font-bold mt-0.5">Produk</span>
        </a>

        <button onclick="openCartModal()" class="flex flex-col items-center text-gray-500 hover:text-blue-600 relative">
            <div class="relative">
                <i class="fa-solid fa-basket-shopping text-lg"></i>
                <span id="cartCountMobile" class="absolute -top-1 -right-2 bg-red-500 text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-bold">0</span>
            </div>
            <span class="text-[10px] font-medium mt-0.5">Keranjang</span>
        </button>

        <button onclick="alert('Fitur Profil Member & Biodata Diri')" class="flex flex-col items-center text-gray-500 hover:text-blue-600">
            <i class="fa-solid fa-circle-user text-lg"></i>
            <span class="text-[10px] font-medium mt-0.5">Profil</span>
        </button>
    </nav>

    <script>
        let currentProduct = null;
        let cart = JSON.parse(localStorage.getItem('rosa_cart') || '[]');

        function updateCartUI() {
            const count = cart.reduce((acc, item) => acc + item.qty, 0);
            document.getElementById('cartCountMobile').innerText = count;
            const headerCount = document.getElementById('cartCountHeader');
            if (headerCount) headerCount.innerText = count;

            const listEl = document.getElementById('cartItemsList');
            if (cart.length === 0) {
                listEl.innerHTML = `
                    <div class="text-center py-8 text-gray-400">
                        <i class="fa-solid fa-bag-shopping text-3xl mb-2"></i>
                        <p>Keranjang Anda masih kosong.</p>
                        <p class="text-[11px] mt-1">Pilih produk percetakan untuk mulai memesan.</p>
                    </div>`;
                document.getElementById('cartTotalText').innerText = 'Rp 0';
                return;
            }

            let total = 0;
            listEl.innerHTML = cart.map((item, index) => {
                const subtotal = item.price * item.qty;
                total += subtotal;
                return `
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <div class="flex-1 pr-2">
                            <h5 class="font-bold text-gray-800 text-xs">${item.name}</h5>
                            <span class="text-[10px] text-gray-500 block">Varian: ${item.option}</span>
                            <span class="text-xs text-blue-700 font-bold">Rp ${subtotal.toLocaleString('id-ID')}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-semibold px-2 py-0.5 bg-gray-100 rounded">${item.qty}x</span>
                            <button onclick="removeCartItem(${index})" class="text-red-500 hover:text-red-700 text-xs p-1">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>`;
            }).join('');

            document.getElementById('cartTotalText').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function openProductModal(product) {
            currentProduct = product;
            document.getElementById('modalName').innerText = product.name;
            document.getElementById('modalCategory').innerText = product.category;
            document.getElementById('modalPrice').innerText = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalUnit').innerText = product.unit;
            document.getElementById('modalDesc').innerText = product.desc;
            document.getElementById('modalImg').src = product.image;
            document.getElementById('modalQty').value = 1;

            const optionSelect = document.getElementById('modalOption');
            optionSelect.innerHTML = product.options.map(opt => `<option value="${opt}">${opt}</option>`).join('');

            const modal = document.getElementById('productModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentProduct = null;
        }

        function changeQty(delta) {
            const qtyInput = document.getElementById('modalQty');
            let val = parseInt(qtyInput.value) + delta;
            if (val < 1) val = 1;
            qtyInput.value = val;
        }

        function addToCartFromModal() {
            if (!currentProduct) return;
            const option = document.getElementById('modalOption').value;
            const qty = parseInt(document.getElementById('modalQty').value);

            cart.push({
                id: currentProduct.id,
                name: currentProduct.name,
                price: currentProduct.price,
                option: option,
                qty: qty
            });

            localStorage.setItem('rosa_cart', JSON.stringify(cart));
            updateCartUI();
            closeProductModal();
            alert('Produk berhasil ditambahkan ke Keranjang!');
        }

        function removeCartItem(index) {
            cart.splice(index, 1);
            localStorage.setItem('rosa_cart', JSON.stringify(cart));
            updateCartUI();
        }

        function openCartModal() {
            updateCartUI();
            const modal = document.getElementById('cartModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeCartModal() {
            const modal = document.getElementById('cartModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function checkoutWhatsApp() {
            if (cart.length === 0) {
                alert('Keranjang masih kosong!');
                return;
            }
            let text = "*ORDER CETAK - ROSA INDAH PRINTING*\n\n";
            let total = 0;
            cart.forEach((item, i) => {
                const sub = item.price * item.qty;
                total += sub;
                text += `${i + 1}. *${item.name}*\n   - Varian: ${item.option}\n   - Jumlah: ${item.qty}\n   - Subtotal: Rp ${sub.toLocaleString('id-ID')}\n\n`;
            });
            text += `*Total: Rp ${total.toLocaleString('id-ID')}*\n\nMohon info detail pengiriman & instruksi kirim file desain cetak. Terima kasih!`;
            
            const noHp = "6281234567890"; // Ganti dengan nomor WhatsApp Anda
            window.location.href = `https://wa.me/${noHp}?text=${encodeURIComponent(text)}`;
        }

        updateCartUI();
    </script>
</body>
</html>
