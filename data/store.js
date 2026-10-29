// In-memory data store for Rosa Indah Printing
const fs = require('fs');
const path = require('path');

const categories = [
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
  'Gantungan Kunci'
];

const homeCategories = [
  { image: 'undnikah.jpeg', name: 'Undangan Nikah', desc: 'Undangan Katalog/Custom', category: 'Undangan Nikah' },
  { image: 'psc.jpeg', name: 'Print Scan Copy', desc: 'Cetak Cepat', category: 'Print Scan Copy' },
  { image: 'mug.png', name: 'MUG custom', desc: 'Gelas / Tumbler', category: 'MUG' },
  { image: 'banner.png', name: 'Banner', desc: 'Indoor / Outdoor', category: 'Banner' },
  { image: 'buku.png', name: 'Cetak Buku', desc: 'Custom Nama & Gambar', category: 'Cetak Buku' },
  { image: 'lanyard.png', name: 'Lanyard & Id Card', desc: 'Semua Ukuran', category: 'Lanyard & Id Card' },
  { image: 'spanduk.png', name: 'Spanduk', desc: 'Ukuran bebas', category: 'Spanduk' },
  { image: 'kartunama.png', name: 'Kartu Nama', desc: 'Identitas bisnis', category: 'Kartu Nama' },
  { image: 'sticker.jpeg', name: 'Sticker', desc: 'Custom', category: 'Sticker' },
  { image: 'undf4.png', name: 'Undangan Lipat 3', desc: 'Yasin, Tahlil, Walimah, Aqiqah', category: 'Undangan Lipat 3' },
  { image: 'nota.jpeg', name: 'Nota / Buku Tabungan', desc: 'Souvenir', category: 'Nota / Buku Tabungan' },
  { image: 'kalender.png', name: 'Kalender', desc: 'Meja & Dinding', category: 'Kalender' },
  { image: 'sablon.png', name: 'Sablon DTF', desc: 'Sablon Custom', category: 'Sablon DTF' },
  { image: 'foto.jpeg', name: 'Cetak Foto', desc: 'Abadikan Momen terbaikmu', category: 'Cetak Foto' },
  { image: 'pigora.jpeg', name: 'Cetak Pigora A4', desc: 'Semua Ukuran', category: 'Cetak Pigora' },
  { image: 'ganci.png', name: 'Gantungan Kunci', desc: 'Semua Ukuran', category: 'Gantungan Kunci' }
];

let products = [
  {
    id: 'banner',
    name: 'Banner & Spanduk',
    category: 'Banner',
    image: 'products/spanduk.png',
    price: 25000,
    unit: 'meter',
    description: 'Banner dan spanduk custom dengan hasil cetak tajam dan warna cerah untuk kebutuhan promosi maupun acara.',
    short_description: 'Banner dan spanduk custom dengan hasil cetak tajam dan warna cerah untuk promosi usaha dan acara.',
    full_description: 'Cetak spanduk dan banner outdoor/indoor dengan teknologi digital printing resolusi tinggi. Menggunakan tinta solvent anti-luntur yang tahan terhadap terik matahari maupun hujan lebat di luar ruangan. Sangat ideal untuk promosi toko/warung, backdrop event, wisuda, baliho kampanye, dan pameran.',
    specifications: 'Bahan: Flexi Korea 440gr / Frontlite 280gr\nUkuran: Bebas (Custom sesuai kebutuhan)\nFinishing: Mata ayam / Kolong bambu / Lipat pres\nMesin: Digital Printing Outdoor Hi-Res',
    features: ['Ukuran bebas', 'Full color', 'Bahan pilihan', 'Siap pasang'],
    status: 'active',
    is_featured: true,
    pricing: [
      { min_qty: 1, max_qty: 4, price: 25000 },
      { min_qty: 5, max_qty: 19, price: 22000 },
      { min_qty: 20, max_qty: null, price: 20000 }
    ]
  },
  {
    id: 'undangan-nikah',
    name: 'Undangan Nikah',
    category: 'Undangan Nikah',
    image: 'products/undnikah.jpeg',
    price: 1500,
    unit: 'pcs',
    description: 'Undangan pernikahan elegan yang dapat disesuaikan dengan konsep dan kebutuhan acara Anda.',
    short_description: 'Undangan pernikahan elegan yang dapat disesuaikan dengan konsep dan kebutuhan tema acara Anda.',
    full_description: 'Percantik momen bahagia Anda dengan undangan pernikahan eksklusif dari Rosa Indah Printing. Dikerjakan dengan presisi tinggi, warna cerah, garis lipatan rapi, serta pilihan finishing mewah seperti hotprint poly emas dan laminasi doff. Bonus kartu ucapan souvenir dan plastik OPP untuk setiap pemesanan.',
    specifications: 'Bahan Kertas: Jasmine / Art Carton 260gr / BC tebal\nUkuran Terbuka: 20 x 28 cm\nUkuran Lipat: 14 x 20 cm\nFinishing: Hotprint emas / Laminasi Doff\nBonus: Kartu ucapan souvenir + Plastik opp',
    features: ['Desain custom', 'Pilihan kertas', 'Cetak full color', 'Bisa request finishing'],
    status: 'active',
    is_featured: true,
    pricing: [
      { min_qty: 100, max_qty: 299, price: 1500 },
      { min_qty: 300, max_qty: 499, price: 1300 },
      { min_qty: 500, max_qty: null, price: 1100 }
    ]
  },
  {
    id: 'undangan-lipat-3',
    name: 'Undangan Lipat 3',
    category: 'Undangan Lipat 3',
    image: 'products/undf4.png',
    price: 1500,
    unit: 'pcs',
    description: 'Undangan lipat tiga untuk berbagai acara seperti walimah, aqiqah, tahlil, dan acara keluarga.',
    short_description: 'Undangan lipat tiga praktis untuk walimah, aqiqah, tahlil, dan berbagai acara syukuran keluarga.',
    full_description: 'Undangan lipat 3 sisi serbaguna untuk kebutuhan walimatul ursy, aqiqah, tahlil, maupun reuni keluarga. Format rapi ukuran standar F4 terbuka yang nyaman dibaca, teks jelas tajam, dan proses pengerjaan cepat.',
    specifications: 'Bahan: Brief Card 160gr / BC Super\nFormat: Lipat 3 sisi (Ukuran F4 terbuka)\nCetak: 1 warna / Full color\nWaktu: 2 - 3 hari kerja',
    features: ['Desain custom', 'Format lipat 3', 'Kertas berkualitas', 'Cetak tajam'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 100, max_qty: 299, price: 1500 },
      { min_qty: 300, max_qty: null, price: 1200 }
    ]
  },
  {
    id: 'kartu-nama',
    name: 'Kartu Nama',
    category: 'Kartu Nama',
    image: 'products/kartunama.png',
    price: 25000,
    unit: 'box',
    description: 'Kartu nama profesional untuk kebutuhan bisnis, usaha, organisasi, maupun personal branding.',
    short_description: 'Kartu nama profesional full color untuk identitas bisnis, organisasi, dan personal branding.',
    full_description: 'Tingkatkan rasa percaya mitra bisnis dan pelanggan dengan kartu nama berkelas dari Rosa Indah Printing. Dicetak tajam di atas kertas tebal Art Paper 260gr dengan pilihan laminasi doff atau glossy bolak-balik (2 sisi). Dilengkapi wadah kotak mika bening eksklusif.',
    specifications: 'Bahan: Art Paper 260gr / Ivory 260gr\nUkuran: 9 x 5.5 cm\nIsi: 100 lembar per box\nFinishing: Laminasi Doff / Glossy 2 sisi\nKemasan: Kotak mika bening',
    features: ['Desain custom', 'Cetak dua sisi', 'Pilihan finishing', 'Cocok untuk bisnis'],
    status: 'active',
    is_featured: true,
    pricing: [
      { min_qty: 1, max_qty: 4, price: 25000 },
      { min_qty: 5, max_qty: null, price: 22000 }
    ]
  },
  {
    id: 'sticker',
    name: 'Sticker Custom',
    category: 'Sticker',
    image: 'products/sticker.jpeg',
    price: 10000,
    unit: 'lembar',
    description: 'Sticker custom untuk branding produk, kemasan, promosi, label, dan kebutuhan lainnya.',
    features: ['Cutting custom', 'Bahan pilihan', 'Warna tajam', 'Cocok untuk kemasan'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 9, price: 10000 },
      { min_qty: 10, max_qty: null, price: 8500 }
    ]
  },
  {
    id: 'nota',
    name: 'Nota',
    category: 'Nota / Buku Tabungan',
    image: 'products/nota.jpeg',
    price: 40000,
    unit: 'buku',
    description: 'Nota usaha yang praktis dan profesional untuk pencatatan transaksi toko maupun bisnis.',
    features: ['Custom nama usaha', 'Nomor urut', 'Pilihan rangkap', 'Layout sesuai kebutuhan'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 4, price: 40000 },
      { min_qty: 5, max_qty: null, price: 35000 }
    ]
  },
  {
    id: 'buku',
    name: 'Cetak Buku',
    category: 'Cetak Buku',
    image: 'products/buku.png',
    price: 30000,
    unit: 'pcs',
    description: 'Layanan cetak buku custom untuk kebutuhan pribadi, sekolah, komunitas, dan bisnis.',
    features: ['Ukuran custom', 'Cover custom', 'Pilihan jilid', 'Cetak berkualitas'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 9, price: 30000 },
      { min_qty: 10, max_qty: null, price: 25000 }
    ]
  },
  {
    id: 'kalender',
    name: 'Kalender',
    category: 'Kalender',
    image: 'products/kalender.png',
    price: 25000,
    unit: 'pcs',
    description: 'Kalender meja atau dinding dengan desain custom untuk kebutuhan pribadi maupun promosi.',
    features: ['Desain custom', 'Meja & dinding', 'Cetak full color', 'Cocok untuk souvenir'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 9, price: 25000 },
      { min_qty: 10, max_qty: null, price: 20000 }
    ]
  },
  {
    id: 'sablon-dtf',
    name: 'Sablon DTF',
    category: 'Sablon DTF',
    image: 'products/sablon.png',
    price: 75000,
    unit: 'pcs',
    description: 'Sablon DTF untuk kaos dan media tekstil dengan desain custom sesuai keinginan Anda.',
    features: ['Desain custom', 'Warna tajam', 'Detail bagus', 'Cocok untuk kaos'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 5, price: 75000 },
      { min_qty: 6, max_qty: null, price: 65000 }
    ]
  },
  {
    id: 'mug-custom',
    name: 'Mug Custom',
    category: 'MUG',
    image: 'products/mug.png',
    price: 35000,
    unit: 'pcs',
    description: 'Mug custom untuk hadiah, souvenir, branding, acara keluarga, maupun kebutuhan usaha.',
    features: ['Desain custom', 'Cocok untuk souvenir', 'Cetak full color', 'Pesanan satuan tersedia'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 9, price: 35000 },
      { min_qty: 10, max_qty: null, price: 30000 }
    ]
  },
  {
    id: 'lanyard-id-card',
    name: 'Lanyard & ID Card',
    category: 'Lanyard & Id Card',
    image: 'products/lanyard.png',
    price: 15000,
    unit: 'pcs',
    description: 'Lanyard dan ID card custom untuk sekolah, kantor, event, komunitas, dan organisasi.',
    features: ['Desain custom', 'Pilihan ukuran', 'Cocok untuk event', 'Bisa satuan/partai'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 19, price: 15000 },
      { min_qty: 20, max_qty: null, price: 12000 }
    ]
  },
  {
    id: 'cetak-foto',
    name: 'Cetak Foto',
    category: 'Cetak Foto',
    image: 'products/foto.jpeg',
    price: 1000,
    unit: 'lembar',
    description: 'Cetak foto dengan warna tajam untuk mengabadikan momen berharga Anda.',
    features: ['Berbagai ukuran', 'Warna tajam', 'Kertas foto berkualitas', 'Cocok untuk dokumentasi'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 49, price: 1000 },
      { min_qty: 50, max_qty: null, price: 800 }
    ]
  },
  {
    id: 'pigora',
    name: 'Cetak Pigora',
    category: 'Cetak Pigora',
    image: 'products/pigora.jpeg',
    price: 35000,
    unit: 'pcs',
    description: 'Cetak foto dengan pigora untuk dekorasi rumah, hadiah, kenang-kenangan, atau souvenir.',
    features: ['Ukuran custom', 'Pilihan pigora', 'Cocok untuk hadiah', 'Finishing rapi'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 4, price: 35000 },
      { min_qty: 5, max_qty: null, price: 30000 }
    ]
  },
  {
    id: 'gantungan-kunci',
    name: 'Gantungan Kunci',
    category: 'Gantungan Kunci',
    image: 'products/ganci.png',
    price: 10000,
    unit: 'pcs',
    description: 'Gantungan kunci custom untuk souvenir, merchandise, komunitas, maupun hadiah.',
    features: ['Desain custom', 'Cocok untuk souvenir', 'Bisa partai', 'Bentuk menarik'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 19, price: 10000 },
      { min_qty: 20, max_qty: null, price: 7500 }
    ]
  },
  {
    id: 'print-scan-copy',
    name: 'Print, Scan & Copy',
    category: 'Print Scan Copy',
    image: 'products/psc.jpeg',
    price: 500,
    unit: 'lembar',
    description: 'Layanan print, scan, dan copy cepat untuk kebutuhan dokumen sehari-hari.',
    features: ['Proses cepat', 'Dokumen warna & hitam putih', 'Scan dokumen', 'Copy dokumen'],
    status: 'active',
    is_featured: false,
    pricing: [
      { min_qty: 1, max_qty: 99, price: 500 },
      { min_qty: 100, max_qty: null, price: 350 }
    ]
  }
];

let portfolios = [
  { id: 10, image: 'uploads/portofolio/portfolio-20260920131147-194820d446.png', product_name: 'Cetak Banner Hari Lahirnya Arsya Khalif', price: 30000, quantity: 1, customer_name: 'Laili', created_at: '2026-09-20 18:11:47', updated_at: '2026-09-20 18:11:47' },
  { id: 9, image: 'uploads/portofolio/portfolio-20260920131044-5b858b3bfe.png', product_name: 'Jasa Desain CV Warna', price: 10000, quantity: 1, customer_name: 'Khasbi', created_at: '2026-09-20 18:10:44', updated_at: '2026-09-20 18:10:44' },
  { id: 8, image: 'uploads/portofolio/portfolio-20260920130015-b2c474fd86.jpg', product_name: 'Cetak Kartu Iuran Paguyuban Wali Murid Kelas 8J', price: 1000, quantity: 40, customer_name: 'Bilqis Sofia', created_at: '2026-09-20 18:00:15', updated_at: '2026-09-20 18:00:15' },
  { id: 7, image: 'uploads/portofolio/portfolio-20260920125757-69beae7699.png', product_name: 'Cetak Kupon Jalan Sehat Agustusan RT01 Desa Sambirejo', price: 800, quantity: 300, customer_name: 'Ketua RT 01 - R.Hidayat', created_at: '2026-09-20 17:57:57', updated_at: '2026-09-20 17:57:57' },
  { id: 6, image: 'uploads/portofolio/portfolio-20260920123648-9159fd62b9.jpg', product_name: 'Gantungan Kunci - Akrilik', price: 5000, quantity: 10, customer_name: 'Bilqis Sofia', created_at: '2026-09-20 17:36:48', updated_at: '2026-09-20 17:42:16' },
  { id: 5, image: 'uploads/portofolio/portfolio-20260920123532-1933ae757e.png', product_name: 'Stiker Ulang Tahun yang ke-1 Arsya Khalif', price: 500, quantity: 200, customer_name: 'Zavier Arsya Khalif', created_at: '2026-09-20 17:35:32', updated_at: '2026-09-20 17:35:32' },
  { id: 4, image: 'uploads/portofolio/portfolio-20260920123300-f8d68200db.jpg', product_name: 'Cetak Buku Pelajaran SD Kelas 5', price: 20000, quantity: 8, customer_name: 'Mela', created_at: '2026-09-20 17:33:00', updated_at: '2026-09-20 17:33:00' },
  { id: 3, image: 'uploads/portofolio/portfolio-20260920102856-cae0a81845.png', product_name: 'Stand Menu Makan Acara Resepsi Nikah', price: 8000, quantity: 5, customer_name: 'Alan Roziqin', created_at: '2026-09-20 15:28:56', updated_at: '2026-09-20 15:28:56' },
  { id: 2, image: 'uploads/portofolio/portfolio-20260920102442-352b77db0c.png', product_name: 'Kartu Penukaran Souvenir', price: 200, quantity: 100, customer_name: 'Arifin Bugul Lor', created_at: '2026-09-20 15:24:42', updated_at: '2026-09-20 15:24:42' },
  { id: 1, image: 'uploads/portofolio/portfolio-20260920102056-eab2170e91.jpg', product_name: 'Undangan Nikah - seri Griya Silver 013', price: 900, quantity: 600, customer_name: 'Akhmad Zindan', created_at: '2026-09-20 15:20:56', updated_at: '2026-09-20 15:20:56' }
];

let vouchers = [
  { id: 1, code: 'ROSAHEMAT10', name: 'Diskon 10% Spesial', discount_type: 'percent', discount_value: 10, min_order: 50000, max_discount: 25000, status: 'active', quota: 50, usage_count: 8, starts_at: '2025-01-01', ends_at: '2026-12-31' },
  { id: 2, code: 'MEMBERBARU', name: 'Potongan Rp 5.000', discount_type: 'fixed', discount_value: 5000, min_order: 30000, max_discount: null, status: 'active', quota: 100, usage_count: 14, starts_at: '2025-01-01', ends_at: '2026-12-31' },
  { id: 3, code: 'CETAKBANYAK', name: 'Diskon 15% Partai', discount_type: 'percent', discount_value: 15, min_order: 200000, max_discount: 50000, status: 'active', quota: 30, usage_count: 5, starts_at: '2025-01-01', ends_at: '2026-12-31' },
  { id: 4, code: 'MEMBERROSA', name: 'Voucher Spesial Member', discount_type: 'fixed', discount_value: 10000, min_order: 100000, max_discount: null, status: 'active', quota: 50, usage_count: 8, starts_at: '2025-01-01', ends_at: '2026-12-31' }
];

let customers = [
  {
    id: 1,
    name: 'Alan Roziqin',
    username: 'alanroziqin',
    phone: '082331035371',
    email: 'alanroziqin543@gmail.com',
    gender: 'L',
    birth_date: '1998-05-15',
    city: 'Pasuruan',
    address: 'Jl. Raya Rejoso No. 45, RT 02 / RW 01',
    address_note: 'Dekat Masjid Jami Rejoso, Rumah Pagar Hitam',
    points: 150,
    is_verified: 1,
    member_tier: 'Platinum Member',
    avatar: ''
  },
  {
    id: 2,
    name: 'Ahmad Faisal',
    username: 'faisal_pasuruan',
    phone: '081234567890',
    email: 'faisal@example.com',
    gender: 'L',
    birth_date: '1995-11-20',
    city: 'Pasuruan',
    address: 'Jl. Merdeka No. 12, Kel. Kebonagung, Pasuruan',
    address_note: 'Ruko Cat Kuning Sebelah Indomaret',
    points: 80,
    is_verified: 1,
    member_tier: 'Gold Member',
    avatar: ''
  }
];

let orders = [
  {
    id: 1,
    order_code: 'RIP-20260320-1045',
    customer_name: 'Ahmad Faisal',
    customer_phone: '081234567890',
    customer_address: 'Jl. Merdeka No. 12, Pasuruan',
    customer_note: 'Mohon warna dibuat lebih cerah dan laminasi doff.',
    subtotal: 75000,
    discount_amount: 5000,
    voucher_code: 'MEMBERBARU',
    total_estimate: 70000,
    status: 'processing',
    payment_status: 'paid',
    payment_method: 'transfer',
    paid_amount: 70000,
    payment_proof: null,
    created_at: '2026-03-20 10:45:00',
    items: [
      { product_id: 'banner', product_name: 'Banner & Spanduk', quantity: 3, unit: 'meter', unit_price: 25000, subtotal: 75000 }
    ],
    history: [
      { status: 'pending', note: 'Pesanan masuk via website', created_at: '2026-03-20 10:45:00' },
      { status: 'confirmed', note: 'Pembayaran DP/Lunas dikonfirmasi admin', created_at: '2026-03-20 11:15:00' },
      { status: 'processing', note: 'Desain sedang disiapkan untuk cetak', created_at: '2026-03-20 13:00:00' }
    ]
  },
  {
    id: 2,
    order_code: 'RIP-20260322-0814',
    customer_name: 'Siti Nurhaliza',
    customer_phone: '082198765432',
    customer_address: 'Jl. Pahlawan No. 45, Rejoso',
    customer_note: 'Untuk acara khitanan minggu depan.',
    subtotal: 150000,
    discount_amount: 15000,
    voucher_code: 'ROSAHEMAT10',
    total_estimate: 135000,
    status: 'printed',
    payment_status: 'paid',
    payment_method: 'qris',
    paid_amount: 135000,
    payment_proof: null,
    created_at: '2026-03-22 08:14:00',
    items: [
      { product_id: 'undangan-lipat-3', product_name: 'Undangan Lipat 3', quantity: 100, unit: 'pcs', unit_price: 1500, subtotal: 150000 }
    ],
    history: [
      { status: 'pending', note: 'Pesanan masuk', created_at: '2026-03-22 08:14:00' },
      { status: 'confirmed', note: 'Pesanan dikonfirmasi', created_at: '2026-03-22 08:30:00' },
      { status: 'processing', note: 'Sedang dicetak', created_at: '2026-03-22 10:00:00' },
      { status: 'printed', note: 'Proses cetak selesai, proses finishing', created_at: '2026-03-22 14:00:00' }
    ]
  },
  {
    id: 3,
    order_code: 'RIP-20260324-1420',
    customer_name: 'Dewi Lestari',
    customer_phone: '085712345678',
    customer_address: 'Perum Sambirejo Asri B-3',
    customer_note: 'Desain foto menyusul dikirim via WhatsApp.',
    subtotal: 105000,
    discount_amount: 0,
    voucher_code: '',
    total_estimate: 105000,
    status: 'pending',
    payment_status: 'waiting',
    payment_method: 'transfer',
    paid_amount: 0,
    payment_proof: null,
    created_at: '2026-03-24 14:20:00',
    items: [
      { product_id: 'mug-custom', product_name: 'Mug Custom', quantity: 3, unit: 'pcs', unit_price: 35000, subtotal: 105000 }
    ],
    history: [
      { status: 'pending', note: 'Pesanan baru menunggu verifikasi pembayaran', created_at: '2026-03-24 14:20:00' }
    ]
  }
];

function getUnitPrice(product, qty) {
  const quantity = Math.max(1, parseInt(qty, 10) || 1);
  let price = Number(product.price || 0);
  if (Array.isArray(product.pricing)) {
    for (const tier of product.pricing) {
      const min = tier.min_qty || 1;
      const max = tier.max_qty;
      if (quantity >= min && (max === null || max === undefined || quantity <= max)) {
        return Number(tier.price);
      }
    }
  }
  return price;
}

module.exports = {
  categories,
  homeCategories,
  getProducts: (category, search, sort = 'rekomendasi') => {
    let list = products.filter(p => p.status === 'active');
    if (category) {
      list = list.filter(p => p.category.toLowerCase() === category.toLowerCase());
    }
    if (search) {
      const q = search.toLowerCase();
      list = list.filter(p => p.name.toLowerCase().includes(q) || p.description.toLowerCase().includes(q));
    }

    // Sorting Options
    if (sort === 'terbaru') {
      list = [...list].reverse();
    } else if (sort === 'terlama') {
      // original insertion order
    } else if (sort === 'harga_termurah') {
      list = [...list].sort((a, b) => (Number(a.price) || 0) - (Number(b.price) || 0));
    } else if (sort === 'harga_termahal') {
      list = [...list].sort((a, b) => (Number(b.price) || 0) - (Number(a.price) || 0));
    } else if (sort === 'terlaris') {
      const bestSellers = ['banner', 'sticker', 'kartu-nama', 'undangan-nikah', 'sablon-dtf', 'mug', 'kalender'];
      list = [...list].sort((a, b) => {
        const aIndex = bestSellers.indexOf(a.id);
        const bIndex = bestSellers.indexOf(b.id);
        if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
        if (aIndex !== -1) return -1;
        if (bIndex !== -1) return 1;
        return 0;
      });
    } else if (sort === 'rekomendasi') {
      list = [...list].sort((a, b) => (b.is_featured ? 1 : 0) - (a.is_featured ? 1 : 0));
    }

    return list;
  },
  getAllProductsAdmin: () => products,
  getProductById: (id) => products.find(p => p.id === id),
  getUnitPrice,
  addProduct: (data) => {
    const id = data.id || ('prod-' + Date.now());
    const shortDesc = data.short_description || data.description || '';
    const fullDesc = data.full_description || shortDesc;
    const newProd = {
      id,
      name: data.name,
      category: data.category || 'Banner',
      image: data.image || 'products/spanduk.png',
      price: Number(data.price) || 0,
      unit: data.unit || 'pcs',
      description: shortDesc,
      short_description: shortDesc,
      full_description: fullDesc,
      specifications: data.specifications || '',
      features: Array.isArray(data.features) 
        ? data.features 
        : (typeof data.features === 'string' ? data.features.split(/\r?\n/).map(s => s.trim()).filter(Boolean) : ['Kualitas terbaik']),
      status: data.status === 'inactive' ? 'inactive' : 'active',
      is_featured: !!data.is_featured,
      pricing: Array.isArray(data.pricing) ? data.pricing : []
    };
    products.unshift(newProd);
    return newProd;
  },
  updateProduct: (oldId, data) => {
    const idx = products.findIndex(p => p.id === oldId);
    if (idx !== -1) {
      const current = products[idx];
      const newId = data.id || oldId;
      const shortDesc = data.short_description !== undefined ? data.short_description : (data.description !== undefined ? data.description : (current.short_description || current.description || ''));
      const fullDesc = data.full_description !== undefined ? data.full_description : (current.full_description || shortDesc);
      const updated = {
        ...current,
        id: newId,
        name: data.name !== undefined ? data.name : current.name,
        category: data.category !== undefined ? data.category : current.category,
        image: data.image !== undefined && data.image ? data.image : current.image,
        price: data.price !== undefined ? Number(data.price) : current.price,
        unit: data.unit !== undefined ? data.unit : current.unit,
        description: shortDesc,
        short_description: shortDesc,
        full_description: fullDesc,
        specifications: data.specifications !== undefined ? data.specifications : (current.specifications || ''),
        features: Array.isArray(data.features) 
          ? data.features 
          : (typeof data.features === 'string' ? data.features.split(/\r?\n/).map(s => s.trim()).filter(Boolean) : current.features),
        status: data.status !== undefined ? data.status : current.status,
        is_featured: data.is_featured !== undefined ? !!data.is_featured : current.is_featured,
        pricing: Array.isArray(data.pricing) ? data.pricing : (current.pricing || [])
      };
      products[idx] = updated;
      return products[idx];
    }
    return null;
  },
  toggleProductStatus: (id) => {
    const p = products.find(prod => prod.id === id);
    if (p) {
      p.status = p.status === 'active' ? 'inactive' : 'active';
      return p;
    }
    return null;
  },
  toggleProductFeatured: (id) => {
    const p = products.find(prod => prod.id === id);
    if (p) {
      p.is_featured = !p.is_featured;
      return p;
    }
    return null;
  },
  deleteProduct: (id) => {
    products = products.filter(p => p.id !== id);
  },
  getPortfolios: () => portfolios,
  getPortfolioById: (id) => portfolios.find(p => String(p.id) === String(id)),
  addPortfolio: (data) => {
    const newItem = {
      id: portfolios.length + 1,
      image: data.image || 'assets/images/products/banner.png',
      product_name: data.product_name,
      price: Number(data.price) || 0,
      quantity: Number(data.quantity) || 1,
      customer_name: data.customer_name || 'Pelanggan Rosa Indah',
      created_at: new Date().toISOString().replace('T', ' ').slice(0, 19)
    };
    portfolios.unshift(newItem);
    return newItem;
  },
  getVouchers: () => vouchers,
  getVoucherByCode: (code) => {
    if (!code) return null;
    return vouchers.find(v => v.code.toUpperCase() === code.trim().toUpperCase() && v.status === 'active');
  },
  getOrders: (status, paymentStatus) => {
    let list = [...orders].reverse();
    if (status) {
      list = list.filter(o => o.status === status);
    }
    if (paymentStatus) {
      list = list.filter(o => o.payment_status === paymentStatus);
    }
    return list;
  },
  getOrderByCode: (code) => {
    if (!code) return null;
    return orders.find(o => o.order_code.toUpperCase() === code.trim().toUpperCase());
  },
  createOrder: (data) => {
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
    const rand = Math.floor(1000 + Math.random() * 9000);
    const orderCode = data.order_code || `RIP-${dateStr}-${rand}`;

    const newOrder = {
      id: orders.length + 1,
      order_code: orderCode,
      customer_name: data.customer_name,
      customer_phone: data.customer_phone,
      customer_address: data.customer_address || '',
      customer_note: data.customer_note || '',
      subtotal: Number(data.subtotal) || 0,
      discount_amount: Number(data.discount_amount) || 0,
      voucher_code: data.voucher_code || '',
      total_estimate: Number(data.total_estimate) || 0,
      status: data.status || 'pending',
      payment_status: data.payment_status || 'waiting',
      payment_method: data.payment_method || 'transfer',
      paid_amount: Number(data.paid_amount) || 0,
      payment_proof: data.payment_proof || null,
      created_at: data.created_at || now.toISOString().replace('T', ' ').slice(0, 19),
      items: data.items || [],
      history: [
        {
          status: data.status || 'pending',
          note: data.note || 'Pesanan dicatat di sistem',
          created_at: now.toISOString().replace('T', ' ').slice(0, 19)
        }
      ]
    };
    orders.push(newOrder);
    return newOrder;
  },
  updateOrderStatus: (orderCode, newStatus, newPaymentStatus, note, paidAmount) => {
    const order = orders.find(o => o.order_code === orderCode);
    if (!order) return null;
    const now = new Date().toISOString().replace('T', ' ').slice(0, 19);
    if (newStatus && newStatus !== order.status) {
      order.status = newStatus;
      order.history.push({
        status: newStatus,
        note: note || `Status diperbarui menjadi ${newStatus}`,
        created_at: now
      });
    }
    if (newPaymentStatus) {
      order.payment_status = newPaymentStatus;
    }
    if (paidAmount !== undefined) {
      order.paid_amount = Number(paidAmount);
    }
    return order;
  },
  uploadPaymentProof: (orderCode, filename) => {
    const order = orders.find(o => o.order_code === orderCode);
    if (order) {
      order.payment_proof = filename;
      order.payment_status = 'waiting';
      const now = new Date().toISOString().replace('T', ' ').slice(0, 19);
      order.history.push({
        status: order.status,
        note: 'Bukti pembayaran diunggah oleh pelanggan. Menunggu verifikasi admin.',
        created_at: now
      });
      return true;
    }
    return false;
  },
  getCustomers: () => customers,
  getCustomerByPhone: (phone) => customers.find(c => c.phone === phone),
  createCustomer: (data) => {
    const c = {
      id: customers.length + 1,
      name: data.name,
      username: data.username || '',
      phone: data.phone,
      email: data.email || '',
      gender: data.gender || 'L',
      birth_date: data.birth_date || '',
      city: data.city || 'Pasuruan',
      points: 10,
      is_verified: 1,
      member_tier: 'Silver Member',
      address: data.address || '',
      address_note: data.address_note || ''
    };
    customers.push(c);
    return c;
  },
  updateCustomer: (idOrPhone, data) => {
    const c = customers.find(item => item.id === Number(idOrPhone) || item.phone === String(idOrPhone));
    if (c) {
      if (data.name !== undefined) c.name = data.name.trim();
      if (data.username !== undefined) c.username = data.username.trim();
      if (data.phone !== undefined) c.phone = data.phone.trim();
      if (data.email !== undefined) c.email = data.email.trim();
      if (data.gender !== undefined) c.gender = data.gender;
      if (data.birth_date !== undefined) c.birth_date = data.birth_date;
      if (data.city !== undefined) c.city = data.city.trim();
      if (data.address !== undefined) c.address = data.address.trim();
      if (data.address_note !== undefined) c.address_note = data.address_note.trim();
      if (data.avatar !== undefined) c.avatar = data.avatar;
      return c;
    }
    return null;
  },
  getCustomerOrders: (idOrPhone) => {
    const phoneClean = String(idOrPhone || '').replace(/\D/g, '');
    const numId = Number(idOrPhone);
    return orders.filter(o => 
      (numId > 0 && o.customer_id === numId) ||
      (phoneClean && o.customer_phone && o.customer_phone.replace(/\D/g, '').endsWith(phoneClean.slice(-8))) ||
      (o.customer_name && o.customer_name.toLowerCase() === String(idOrPhone).toLowerCase())
    );
  },
  getStats: () => {
    const activeProducts = products.filter(p => p.status === 'active').length;
    const totalOrders = orders.length;
    const waitingPayment = orders.filter(o => o.payment_status === 'waiting').length;
    const paidOrders = orders.filter(o => o.payment_status === 'paid').length;
    const monthSales = orders.reduce((sum, o) => sum + (o.total_estimate || 0), 0);
    const monthPaid = orders.reduce((sum, o) => sum + (o.paid_amount || 0), 0);

    const orderCounts = {
      pending: orders.filter(o => o.status === 'pending').length,
      confirmed: orders.filter(o => o.status === 'confirmed').length,
      processing: orders.filter(o => o.status === 'processing').length,
      printed: orders.filter(o => o.status === 'printed').length,
      completed: orders.filter(o => o.status === 'completed').length,
      cancelled: orders.filter(o => o.status === 'cancelled').length
    };

    return {
      products: products.length,
      active: activeProducts,
      orders: totalOrders,
      customers: customers.length,
      waiting_payment: waitingPayment,
      paid: paidOrders,
      month_sales: monthSales,
      month_paid: monthPaid,
      month_orders: totalOrders,
      orderCounts
    };
  },
  // Promotions & Vouchers
  getPromotions: () => promotions,
  getVouchers: () => vouchers,
  addPromotion: (data) => {
    const item = {
      id: promotions.length + 1,
      name: data.name,
      description: data.description || '',
      discount_type: data.discount_type || 'percent',
      discount_value: Number(data.discount_value) || 0,
      min_order: Number(data.min_order) || 0,
      max_discount: data.max_discount ? Number(data.max_discount) : null,
      scope_type: data.scope_type || 'all',
      scope_value: data.scope_value || null,
      member_only: data.member_only !== undefined ? Number(data.member_only) : 1,
      quota: data.quota ? Number(data.quota) : null,
      usage_count: 0,
      starts_at: data.starts_at || new Date().toISOString().slice(0, 10),
      ends_at: data.ends_at || '2030-12-31',
      status: data.status || 'active'
    };
    promotions.push(item);
    return item;
  },
  deletePromotion: (id) => {
    promotions = promotions.filter(p => p.id !== Number(id));
  },
  addVoucher: (data) => {
    const item = {
      id: vouchers.length + 1,
      code: String(data.code || '').trim().toUpperCase(),
      name: data.name,
      description: data.description || '',
      discount_type: data.discount_type || 'percent',
      discount_value: Number(data.discount_value) || 0,
      min_order: Number(data.min_order) || 0,
      max_discount: data.max_discount ? Number(data.max_discount) : null,
      scope_type: data.scope_type || 'all',
      scope_value: data.scope_value || null,
      member_only: data.member_only !== undefined ? Number(data.member_only) : 1,
      quota: data.quota ? Number(data.quota) : null,
      usage_count: 0,
      starts_at: data.starts_at || new Date().toISOString().slice(0, 10),
      ends_at: data.ends_at || '2030-12-31',
      status: data.status || 'active'
    };
    vouchers.push(item);
    return item;
  },
  deleteVoucher: (id) => {
    vouchers = vouchers.filter(v => v.id !== Number(id));
  },
  // Loyalty & Rewards
  getLoyaltySettings: () => loyaltySettings,
  updateLoyaltySettings: (settings) => {
    if (settings.points_per_amount) loyaltySettings.points_per_amount = Number(settings.points_per_amount);
    if (settings.points_per_order_min !== undefined) loyaltySettings.points_per_order_min = Number(settings.points_per_order_min);
    return loyaltySettings;
  },
  getLoyaltyRewards: () => loyaltyRewards,
  addLoyaltyReward: (data) => {
    const item = {
      id: loyaltyRewards.length + 1,
      name: data.name,
      points_cost: Number(data.points_cost) || 100,
      discount_type: data.discount_type || 'fixed',
      discount_value: Number(data.discount_value) || 10000,
      min_order: Number(data.min_order) || 0,
      max_discount: data.max_discount ? Number(data.max_discount) : null,
      validity_days: Number(data.validity_days) || 30,
      quota: data.quota ? Number(data.quota) : null,
      redeemed_count: 0,
      status: data.status || 'active'
    };
    loyaltyRewards.push(item);
    return item;
  },
  deleteLoyaltyReward: (id) => {
    loyaltyRewards = loyaltyRewards.filter(r => r.id !== Number(id));
  },
  adjustCustomerPoints: (customerId, points, description) => {
    const cust = customers.find(c => c.id === Number(customerId));
    if (!cust) return false;
    cust.points = Math.max(0, (cust.points || 0) + Number(points));
    loyaltyTransactions.push({
      id: loyaltyTransactions.length + 1,
      customer_id: cust.id,
      customer_name: cust.name,
      points: Number(points),
      description: description || 'Penyesuaian poin oleh Admin',
      created_at: new Date().toISOString().replace('T', ' ').slice(0, 16)
    });
    return true;
  },
  getLoyaltyTransactions: () => loyaltyTransactions,
  // Notifications
  getNotifications: () => notifications,
  addNotification: (customerId, title, message, link) => {
    const notif = {
      id: notifications.length + 1,
      customer_id: Number(customerId) || 0,
      customer_name: Number(customerId) > 0 ? (customers.find(c => c.id === Number(customerId))?.name || 'Member') : 'Semua Member',
      title,
      message,
      link: link || '',
      type: 'general',
      is_read: 0,
      created_at: new Date().toISOString().replace('T', ' ').slice(0, 16)
    };
    notifications.unshift(notif);
    return notif;
  },
  // Admin Password
  getAdminPassword: () => adminPassword,
  updateAdminPassword: (newPassword) => {
    adminPassword = newPassword;
    return true;
  }
};

let adminPassword = 'admin';

let promotions = [
  {
    id: 1,
    name: 'Promo Member Pembukaan',
    description: 'Diskon 5% untuk seluruh pesanan cetak member baru.',
    discount_type: 'percent',
    discount_value: 5,
    min_order: 50000,
    max_discount: 25000,
    scope_type: 'all',
    scope_value: null,
    member_only: 1,
    quota: 100,
    usage_count: 14,
    starts_at: '2025-01-01',
    ends_at: '2026-12-31',
    status: 'active'
  }
];

let loyaltySettings = {
  points_per_amount: 10000,
  points_per_order_min: 0
};

let loyaltyRewards = [
  {
    id: 1,
    name: 'Voucher Potongan Rp10.000',
    points_cost: 100,
    discount_type: 'fixed',
    discount_value: 10000,
    min_order: 50000,
    max_discount: null,
    validity_days: 30,
    quota: null,
    redeemed_count: 3,
    status: 'active'
  },
  {
    id: 2,
    name: 'Diskon Cetak 10%',
    points_cost: 150,
    discount_type: 'percent',
    discount_value: 10,
    min_order: 100000,
    max_discount: 30000,
    validity_days: 30,
    quota: null,
    redeemed_count: 1,
    status: 'active'
  }
];

let loyaltyTransactions = [
  {
    id: 1,
    customer_id: 1,
    customer_name: 'Budi Santoso',
    points: 15,
    description: 'Poin bonus pendaftaran Member',
    created_at: '2025-02-10 14:20'
  }
];

let notifications = [
  {
    id: 1,
    customer_id: 0,
    customer_name: 'Semua Member',
    title: 'Selamat Datang di Rosa Indah Member!',
    message: 'Kumpulkan poin setiap pemesanan cetak dan nikmati promo eksklusif khusus member.',
    link: '/produk',
    type: 'general',
    is_read: 0,
    created_at: '2025-02-10 09:00'
  }
];

