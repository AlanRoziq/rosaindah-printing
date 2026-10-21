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
  { id: 1, image: 'assets/images/products/banner.png', product_name: 'Banner Indoor Acara Seminar', price: 75000, quantity: 3, customer_name: 'Budi Santoso', created_at: '2026-03-15 10:00:00' },
  { id: 2, image: 'assets/images/products/spanduk.png', product_name: 'Spanduk Promosi Warung', price: 100000, quantity: 4, customer_name: 'Siti Aminah', created_at: '2026-03-18 11:30:00' },
  { id: 3, image: 'assets/images/products/mug.png', product_name: 'Mug Custom Souvenir Kantor', price: 700000, quantity: 20, customer_name: 'PT Maju Bersama', created_at: '2026-03-20 14:15:00' },
  { id: 4, image: 'assets/images/products/sablon.png', product_name: 'Kaos Sablon DTF Komunitas', price: 1200000, quantity: 15, customer_name: 'Komunitas Gowes', created_at: '2026-03-22 09:20:00' },
  { id: 5, image: 'assets/images/products/kartunama.png', product_name: 'Kartu Nama Bisnis Eksekutif', price: 50000, quantity: 2, customer_name: 'Hendra Wijaya', created_at: '2026-03-24 16:45:00' },
  { id: 6, image: 'assets/images/products/kalender.png', product_name: 'Kalender Dinding Tahun 2026', price: 500000, quantity: 25, customer_name: 'Klinik Sehat', created_at: '2026-03-25 13:10:00' },
  { id: 7, image: 'assets/images/products/undnikah.jpeg', product_name: 'Undangan Pernikahan Softcover', price: 450000, quantity: 300, customer_name: 'Dimas & Rina', created_at: '2026-03-27 15:30:00' },
  { id: 8, image: 'assets/images/products/lanyard.png', product_name: 'Lanyard & ID Card Panitia', price: 300000, quantity: 20, customer_name: 'SMK Negeri 1', created_at: '2026-03-28 10:05:00' }
];

let vouchers = [
  { code: 'ROSAHEMAT10', name: 'Diskon 10% Spesial', discount_type: 'percent', discount_value: 10, min_order: 50000, max_discount: 25000, status: 'active' },
  { code: 'MEMBERBARU', name: 'Potongan Rp 5.000', discount_type: 'fixed', discount_value: 5000, min_order: 30000, max_discount: null, status: 'active' },
  { code: 'CETAKBANYAK', name: 'Diskon 15% Partai', discount_type: 'percent', discount_value: 15, min_order: 200000, max_discount: 50000, status: 'active' }
];

let customers = [
  { id: 1, name: 'Alan Roziqin', phone: '082331035371', email: 'alanroziqin543@gmail.com', points: 150, is_verified: 1, address: 'Rejoso, Pasuruan' },
  { id: 2, name: 'Ahmad Faisal', phone: '081234567890', email: 'faisal@example.com', points: 80, is_verified: 1, address: 'Jl. Merdeka No. 12, Pasuruan' }
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
  getProducts: (category, search) => {
    let list = products.filter(p => p.status === 'active');
    if (category) {
      list = list.filter(p => p.category.toLowerCase() === category.toLowerCase());
    }
    if (search) {
      const q = search.toLowerCase();
      list = list.filter(p => p.name.toLowerCase().includes(q) || p.description.toLowerCase().includes(q));
    }
    return list;
  },
  getAllProductsAdmin: () => products,
  getProductById: (id) => products.find(p => p.id === id),
  getUnitPrice,
  addProduct: (data) => {
    const id = data.id || ('prod-' + Date.now());
    const newProd = {
      id,
      name: data.name,
      category: data.category || 'Banner',
      image: data.image || 'products/spanduk.png',
      price: Number(data.price) || 0,
      unit: data.unit || 'pcs',
      description: data.description || '',
      features: Array.isArray(data.features) ? data.features : (data.features ? data.features.split(',').map(s => s.trim()) : ['Kualitas terbaik']),
      status: data.status || 'active',
      is_featured: !!data.is_featured,
      pricing: data.pricing || []
    };
    products.unshift(newProd);
    return newProd;
  },
  updateProduct: (id, data) => {
    const idx = products.findIndex(p => p.id === id);
    if (idx !== -1) {
      products[idx] = {
        ...products[idx],
        ...data,
        price: Number(data.price !== undefined ? data.price : products[idx].price)
      };
      return products[idx];
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
    const orderCode = `RIP-${dateStr}-${rand}`;

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
      status: 'pending',
      payment_status: 'waiting',
      payment_method: data.payment_method || 'transfer',
      paid_amount: 0,
      payment_proof: null,
      created_at: now.toISOString().replace('T', ' ').slice(0, 19),
      items: data.items || [],
      history: [
        {
          status: 'pending',
          note: 'Pesanan dibuat oleh pelanggan',
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
      phone: data.phone,
      email: data.email || '',
      points: 10,
      is_verified: 1,
      address: data.address || ''
    };
    customers.push(c);
    return c;
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
  }
};
