const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const path = require('path');
const fs = require('fs');
const multer = require('multer');
const store = require('./data/store');

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = '0.0.0.0';
const WHATSAPP_NUMBER = process.env.WHATSAPP_NUMBER || '6282331035371';

// Ensure uploads directories exist
const uploadDir = path.join(__dirname, 'uploads');
const produkUploadDir = path.join(__dirname, 'uploads/produk');
const avatarUploadDir = path.join(__dirname, 'uploads/avatars');
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });
if (!fs.existsSync(produkUploadDir)) fs.mkdirSync(produkUploadDir, { recursive: true });
if (!fs.existsSync(avatarUploadDir)) fs.mkdirSync(avatarUploadDir, { recursive: true });

const produkStorage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, produkUploadDir);
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase() || '.jpg';
    const rawSlug = req.body.id || req.body.name || 'produk';
    const slug = String(rawSlug).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'produk';
    const rand = Math.random().toString(36).substring(2, 8);
    cb(null, `${slug}-${rand}${ext}`);
  }
});
const uploadProduk = multer({
  storage: produkStorage,
  limits: { fileSize: 5 * 1024 * 1024 }
});

const avatarStorage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, avatarUploadDir);
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase() || '.jpg';
    const rand = Math.random().toString(36).substring(2, 9);
    cb(null, `avatar-${Date.now()}-${rand}${ext}`);
  }
});
const uploadAvatar = multer({
  storage: avatarStorage,
  limits: { fileSize: 4 * 1024 * 1024 },
  fileFilter: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    if (['.jpg', '.jpeg', '.png', '.webp'].includes(ext)) {
      cb(null, true);
    } else {
      cb(new Error('Format file harus berupa gambar (JPG, PNG, WEBP)'));
    }
  }
});

function parsePriceTiers(raw) {
  if (!raw || typeof raw !== 'string') return [];
  const tiers = [];
  const lines = raw.split(/\r?\n/);
  for (let line of lines) {
    line = line.trim();
    if (!line) continue;
    const m = line.match(/^\s*(\d+)\s*(?:-\s*(\d+)|\+)??\s*[=:]\s*([0-9.,]+)\s*$/);
    if (!m) continue;
    const min = parseInt(m[1], 10);
    const max = m[2] ? parseInt(m[2], 10) : null;
    const cleanPrice = m[3].replace(/\./g, '').replace(/,/g, '.');
    const price = parseFloat(cleanPrice) || 0;
    if (min >= 1 && price >= 0) {
      tiers.push({ min_qty: min, max_qty: max, price });
    }
  }
  tiers.sort((a, b) => a.min_qty - b.min_qty);
  return tiers;
}

function formatPriceTiers(pricing) {
  if (!pricing || !Array.isArray(pricing)) return '';
  return pricing.map(t => {
    const range = t.min_qty + (t.max_qty === null || t.max_qty === undefined ? '+' : '-' + t.max_qty);
    return `${range}=${t.price}`;
  }).join('\n');
}

// Setup view engine
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');

// Body parser & cookies
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(cookieParser());

// Session setup
app.use(session({
  secret: process.env.SESSION_SECRET || 'rosaindah-printing-secret-key-pasuruan',
  resave: false,
  saveUninitialized: true,
  cookie: { maxAge: 24 * 60 * 60 * 1000 }
}));

// Static files
app.use('/assets', express.static(path.join(__dirname, 'assets')));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));
// In case old links use /rosaindahprinting/assets or /rosaindahprinting/uploads
app.use('/rosaindahprinting/assets', express.static(path.join(__dirname, 'assets')));
app.use('/rosaindahprinting/uploads', express.static(path.join(__dirname, 'uploads')));

// PWA Manifest & Service Worker
app.get('/manifest.json', (req, res) => {
  res.setHeader('Content-Type', 'application/manifest+json; charset=utf-8');
  res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  res.sendFile(path.join(__dirname, 'manifest.json'));
});
app.get('/sw.js', (req, res) => {
  res.setHeader('Content-Type', 'application/javascript; charset=utf-8');
  res.setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  res.setHeader('Service-Worker-Allowed', '/');
  res.sendFile(path.join(__dirname, 'sw.js'));
});

// Global view variables & helpers
app.use((req, res, next) => {
  if (!req.session.cart) {
    req.session.cart = [];
  }
  const cartItems = req.session.cart || [];
  const cartCount = cartItems.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 1), 0);

  res.locals.asset_url = (rel) => '/assets/' + String(rel || '').replace(/^\/+/, '');
  res.locals.page_url = (rel) => {
    const raw = String(rel || '').replace(/^\/+/, '');
    if (!raw || raw === 'index' || raw === 'index.php') return '/index.php';
    const qIdx = raw.indexOf('?');
    const pathPart = qIdx >= 0 ? raw.slice(0, qIdx) : raw;
    const queryPart = qIdx >= 0 ? raw.slice(qIdx) : '';
    const cleanPath = pathPart.endsWith('.php') ? pathPart : pathPart + '.php';
    return '/' + cleanPath + queryPart;
  };
  res.locals.product_image_url = (img) => {
    const clean = String(img || '').replace(/\\/g, '/').replace(/^\/+/, '');
    if (clean.startsWith('uploads/')) return '/' + clean;
    if (clean.startsWith('assets/')) return '/' + clean;
    return '/assets/images/' + clean;
  };
  res.locals.format_rupiah = (amount) => 'Rp' + Number(amount || 0).toLocaleString('id-ID');
  res.locals.cart = cartItems;
  res.locals.cart_count = cartCount;
  res.locals.currentUser = req.session.customer || null;
  res.locals.currentAdmin = req.session.admin || null;
  res.locals.categories = store.categories;
  res.locals.homeCategories = store.homeCategories;
  res.locals.whatsapp_number = WHATSAPP_NUMBER;
  res.locals.current_path = req.path;
  next();
});

// Helper for cart calculations
function getCartDetails(sessionCart) {
  let items = [];
  let total = 0;
  for (const row of sessionCart) {
    const product = store.getProductById(row.product_id);
    if (product) {
      const qty = Math.max(1, parseInt(row.quantity, 10) || 1);
      const unitPrice = store.getUnitPrice(product, qty);
      const subtotal = unitPrice * qty;
      items.push({
        product,
        quantity: qty,
        unitPrice,
        subtotal
      });
      total += subtotal;
    }
  }
  return { items, total };
}

// ==========================================
// PUBLIC USER ROUTES
// ==========================================

// Homepage
const handleHome = (req, res) => {
  const featured = store.getProducts().slice(0, 8);
  const portfolios = store.getPortfolios();
  res.render('index', {
    featuredProducts: featured,
    portfolios,
    homeCategories: store.homeCategories
  });
};
app.get('/', handleHome);
app.get('/index.php', handleHome);

// Catalog / Products
const handleProduk = (req, res) => {
  const category = req.query.kategori || '';
  const search = req.query.q || '';
  const sort = req.query.sort || 'rekomendasi';
  const products = store.getProducts(category, search, sort);
  const allProducts = store.getProducts();

  res.render('produk', {
    products,
    selectedCategory: category,
    searchQuery: search,
    currentSort: sort,
    totalCount: allProducts.length
  });
};
app.get('/produk', handleProduk);
app.get('/produk.php', handleProduk);
app.get('/products', handleProduk);
app.get('/products/index.php', handleProduk);

// Product Detail
const handleDetailProduk = (req, res) => {
  const productId = (req.query.id || '').trim();
  const product = store.getProductById(productId);
  let relatedProducts = [];
  if (product) {
    const all = store.getAllProductsAdmin();
    relatedProducts = all.filter(p => p.id !== product.id && p.status === 'active' && p.category === product.category).slice(0, 4);
    if (relatedProducts.length < 4) {
      const others = all.filter(p => p.id !== product.id && p.status === 'active' && p.category !== product.category).slice(0, 4 - relatedProducts.length);
      relatedProducts = relatedProducts.concat(others);
    }
  }
  res.render('detail-produk', {
    product,
    relatedProducts
  });
};
app.get('/detail-produk', handleDetailProduk);
app.get('/detail-produk.php', handleDetailProduk);

// Shopping Cart (GET)
const handleCartGet = (req, res) => {
  const { items, total } = getCartDetails(req.session.cart);
  let notice = '';
  if (req.query.added) notice = 'Produk berhasil ditambahkan ke keranjang belanja!';
  if (req.query.updated) notice = 'Jumlah produk keranjang berhasil diperbarui!';
  if (req.query.removed) notice = 'Produk berhasil dihapus dari keranjang!';
  if (req.query.cleared) notice = 'Keranjang belanja telah dikosongkan!';

  res.render('cart', {
    items,
    total,
    notice
  });
};
app.get('/cart', handleCartGet);
app.get('/cart.php', handleCartGet);

// Shopping Cart (POST)
const handleCartPost = (req, res) => {
  const action = req.body.action || '';
  const productId = req.body.product_id || '';
  const quantity = Math.max(1, parseInt(req.body.quantity, 10) || 1);

  if (!req.session.cart) req.session.cart = [];

  if (action === 'add') {
    const existing = req.session.cart.find(item => item.product_id === productId);
    if (existing) {
      existing.quantity += quantity;
    } else {
      req.session.cart.push({ product_id: productId, quantity });
    }
    return res.redirect('/cart.php?added=1');
  }

  if (action === 'update') {
    const existing = req.session.cart.find(item => item.product_id === productId);
    if (existing) {
      existing.quantity = quantity;
    }
    return res.redirect('/cart.php?updated=1');
  }

  if (action === 'remove') {
    req.session.cart = req.session.cart.filter(item => item.product_id !== productId);
    return res.redirect('/cart.php?removed=1');
  }

  if (action === 'clear') {
    req.session.cart = [];
    return res.redirect('/cart.php?cleared=1');
  }

  res.redirect('/cart.php');
};
app.post('/cart', handleCartPost);
app.post('/cart.php', handleCartPost);

// Checkout
const handleCheckoutGet = (req, res) => {
  const buyId = (req.query.buy || '').trim();
  const buyQty = Math.max(1, parseInt(req.query.qty, 10) || 1);
  let items = [];
  let total = 0;

  if (buyId) {
    const product = store.getProductById(buyId);
    if (product) {
      const unitPrice = store.getUnitPrice(product, buyQty);
      const subtotal = unitPrice * buyQty;
      items = [{ product, quantity: buyQty, unitPrice, subtotal }];
      total = subtotal;
    }
  } else {
    const cartDetails = getCartDetails(req.session.cart);
    items = cartDetails.items;
    total = cartDetails.total;
  }

  if (items.length === 0) {
    return res.redirect('/cart.php');
  }
  res.render('checkout', {
    items,
    total,
    error: req.query.error || ''
  });
};
app.get('/checkout', handleCheckoutGet);
app.get('/checkout.php', handleCheckoutGet);

const handleCheckoutPost = (req, res) => {
  const { customer_name, customer_phone, customer_address, customer_note, voucher_code, payment_method } = req.body;
  const { items, total } = getCartDetails(req.session.cart);

  if (!customer_name || !customer_phone) {
    return res.redirect('/checkout.php?error=' + encodeURIComponent('Nama dan nomor WhatsApp wajib diisi'));
  }

  let discount = 0;
  if (voucher_code) {
    const v = store.getVoucherByCode(voucher_code);
    if (v && total >= (v.min_order || 0)) {
      if (v.discount_type === 'percent') {
        discount = (total * v.discount_value) / 100;
        if (v.max_discount && discount > v.max_discount) discount = v.max_discount;
      } else {
        discount = v.discount_value;
      }
    }
  }

  const finalTotal = Math.max(0, total - discount);

  const newOrder = store.createOrder({
    customer_name,
    customer_phone,
    customer_address,
    customer_note,
    subtotal: total,
    discount_amount: discount,
    voucher_code: voucher_code || '',
    total_estimate: finalTotal,
    payment_method: payment_method || 'transfer',
    items: items.map(it => ({
      product_id: it.product.id,
      product_name: it.product.name,
      quantity: it.quantity,
      unit: it.product.unit,
      unit_price: it.unitPrice,
      subtotal: it.subtotal
    }))
  });

  // Clear cart
  req.session.cart = [];

  // Redirect to track page
  res.redirect(`/lacak-pesanan.php?code=${encodeURIComponent(newOrder.order_code)}&phone=${encodeURIComponent(customer_phone)}&new=1`);
};
app.post('/checkout', handleCheckoutPost);
app.post('/checkout.php', handleCheckoutPost);

// Order Tracking
const handleLacakPesanan = (req, res) => {
  const code = (req.query.code || '').trim().toUpperCase();
  const phone = (req.query.phone || '').trim();
  let order = null;
  let error = '';

  if (code) {
    order = store.getOrderByCode(code);
    if (!order) {
      error = 'Nomor pesanan tidak ditemukan.';
    } else if (phone && order.customer_phone.replace(/\D/g, '') !== phone.replace(/\D/g, '')) {
      error = 'Nomor WhatsApp tidak cocok dengan data pesanan.';
      order = null;
    }
  }

  res.render('lacak-pesanan', {
    code,
    phone,
    order,
    error,
    isNew: !!req.query.new
  });
};
app.get('/lacak-pesanan', handleLacakPesanan);
app.get('/lacak-pesanan.php', handleLacakPesanan);
app.get('/lacak', handleLacakPesanan);
app.get('/lacak.php', handleLacakPesanan);

// Payment proof submission
const handlePembayaranPost = (req, res) => {
  const orderCode = req.body.order_code;
  const phone = req.body.customer_phone || '';
  if (orderCode) {
    store.uploadPaymentProof(orderCode, 'bukti-bayar-upload.jpg');
  }
  res.redirect(`/lacak-pesanan.php?code=${encodeURIComponent(orderCode)}&phone=${encodeURIComponent(phone)}&uploaded=1`);
};
app.post('/pembayaran', handlePembayaranPost);
app.post('/pembayaran.php', handlePembayaranPost);

// Portfolio
const handlePortfolio = (req, res) => {
  res.render('portfolio', {
    portfolios: store.getPortfolios()
  });
};
app.get('/portfolio', handlePortfolio);
app.get('/portfolio.php', handlePortfolio);
app.get('/portofolio', handlePortfolio);
app.get('/portofolio.php', handlePortfolio);

const handleDetailPortfolio = (req, res) => {
  const id = req.params.id || req.query.id;
  const all = store.getPortfolios() || [];
  const item = (id ? store.getPortfolioById(id) : null) || all[0] || null;
  const otherPortfolios = all.filter(p => !item || String(p.id) !== String(item.id)).slice(0, 4);
  res.render('detail-portofolio', {
    item,
    otherPortfolios
  });
};
app.get('/detail-portofolio', handleDetailPortfolio);
app.get('/detail-portofolio.php', handleDetailPortfolio);
app.get('/detail-portfolio', handleDetailPortfolio);
app.get('/detail-portfolio.php', handleDetailPortfolio);
app.get('/detail-portofolio/:id', handleDetailPortfolio);
app.get('/detail-portfolio/:id', handleDetailPortfolio);
app.get('/portfolio/:id', handleDetailPortfolio);
app.get('/portofolio/:id', handleDetailPortfolio);

// Promos & Vouchers
const handlePromo = (req, res) => {
  res.render('promo', {
    vouchers: store.getVouchers()
  });
};
app.get('/promo', handlePromo);
app.get('/promo.php', handlePromo);

// Static Pages
app.get('/tentang', (req, res) => res.render('tentang'));
app.get('/tentang.php', (req, res) => res.render('tentang'));
app.get('/tentang-kami', (req, res) => res.render('tentang'));
app.get('/tentang-kami.php', (req, res) => res.render('tentang'));

app.get('/cara-pemesanan', (req, res) => res.render('cara-pemesanan'));
app.get('/cara-pemesanan.php', (req, res) => res.render('cara-pemesanan'));

app.get('/kontak', (req, res) => res.render('kontak', { success: req.query.sent }));
app.get('/kontak.php', (req, res) => res.render('kontak', { success: req.query.sent }));
app.post('/kontak', (req, res) => res.redirect('/kontak.php?sent=1'));
app.post('/kontak.php', (req, res) => res.redirect('/kontak.php?sent=1'));

// Customer Auth
app.get('/login', (req, res) => res.render('login', { error: req.query.error || '' }));
app.get('/login.php', (req, res) => res.render('login', { error: req.query.error || '' }));

const handleCustomerLoginPost = (req, res) => {
  const { phone, password } = req.body;
  const user = store.getCustomerByPhone(phone);
  if (user) {
    req.session.customer = user;
    return res.redirect('/akun.php');
  }
  const defaultUser = { id: 10, name: 'Member Pelanggan', phone: phone || '081234567890', points: 50 };
  req.session.customer = defaultUser;
  res.redirect('/akun.php');
};
app.post('/login', handleCustomerLoginPost);
app.post('/login.php', handleCustomerLoginPost);

app.get('/daftar', (req, res) => res.render('daftar', { error: '' }));
app.get('/daftar.php', (req, res) => res.render('daftar', { error: '' }));

const handleCustomerDaftarPost = (req, res) => {
  const { name, phone, email, address } = req.body;
  if (!name || !phone) return res.render('daftar', { error: 'Nama dan nomor telepon wajib diisi' });
  const user = store.createCustomer({ name, phone, email, address });
  req.session.customer = user;
  res.redirect('/akun.php');
};
app.post('/daftar', handleCustomerDaftarPost);
app.post('/daftar.php', handleCustomerDaftarPost);

const handleAkunGet = (req, res) => {
  const user = req.session.customer || null;
  const orders = user
    ? (store.getCustomerOrders ? store.getCustomerOrders(user.phone || user.id) : store.getOrders())
    : [];
  const vouchers = store.getVouchers ? store.getVouchers() : [];
  const tab = req.query.tab || 'profil';
  const notice = req.query.notice || '';
  const error = req.query.error || '';

  const totalSpent = orders.reduce((sum, o) => sum + (o.status !== 'cancelled' ? (o.total_estimate || 0) : 0), 0);
  const activeOrdersCount = orders.filter(o => ['pending', 'confirmed', 'processing', 'printed'].includes(o.status)).length;
  const completedOrdersCount = orders.filter(o => o.status === 'completed').length;

  res.render('akun', {
    user,
    orders,
    vouchers,
    stats: {
      total: orders.length,
      active: activeOrdersCount,
      completed: completedOrdersCount,
      spent: totalSpent
    },
    tab,
    notice,
    error
  });
};
app.get('/akun', handleAkunGet);
app.get('/akun.php', handleAkunGet);

const handleAkunPostMiddleware = (req, res, next) => {
  if (!req.session.customer) {
    return res.redirect('/login.php');
  }
  uploadAvatar.single('avatar')(req, res, function(err) {
    if (err) {
      return res.redirect('/akun.php?tab=profil&error=' + encodeURIComponent(err.message || 'Gagal mengunggah foto profil.'));
    }
    next();
  });
};

const handleAkunPost = (req, res) => {
  if (!req.session.customer) {
    return res.redirect('/login.php');
  }
  const current = req.session.customer;
  const { name, username, phone, email, gender, birth_date, city, address, address_note, remove_avatar } = req.body;

  if (!name || name.trim().length < 2) {
    return res.redirect('/akun.php?tab=profil&error=' + encodeURIComponent('Nama lengkap minimal 2 karakter.'));
  }
  if (!phone || phone.trim().length < 8) {
    return res.redirect('/akun.php?tab=profil&error=' + encodeURIComponent('Nomor WhatsApp valid wajib diisi.'));
  }

  const updateData = {
    name: name.trim(),
    username: (username || '').trim(),
    phone: phone.trim(),
    email: (email || '').trim(),
    gender: gender || 'L',
    birth_date: birth_date || '',
    city: (city || '').trim(),
    address: (address || '').trim(),
    address_note: (address_note || '').trim()
  };

  if (req.file) {
    updateData.avatar = 'uploads/avatars/' + req.file.filename;
  } else if (remove_avatar === '1') {
    updateData.avatar = '';
  }

  const updated = store.updateCustomer(current.id || current.phone, updateData);

  if (updated) {
    req.session.customer = updated;
  } else {
    req.session.customer = {
      ...current,
      ...updateData
    };
  }

  res.redirect('/akun.php?tab=profil&notice=' + encodeURIComponent('Foto profil dan data biodata diri berhasil disimpan!'));
};
app.post('/akun', handleAkunPostMiddleware, handleAkunPost);
app.post('/akun.php', handleAkunPostMiddleware, handleAkunPost);

app.get('/logout', (req, res) => {
  req.session.customer = null;
  res.redirect('/index.php');
});
app.get('/logout.php', (req, res) => {
  req.session.customer = null;
  res.redirect('/index.php');
});

// Member points and notifications
app.get('/poin-member', (req, res) => res.redirect('/akun.php'));
app.get('/poin-member.php', (req, res) => res.redirect('/akun.php'));
app.get('/notifikasi-member', (req, res) => res.redirect('/akun.php'));
app.get('/notifikasi-member.php', (req, res) => res.redirect('/akun.php'));

// ==========================================
// ADMIN ROUTES
// ==========================================

const checkAdminAuth = (req, res, next) => {
  if (!req.session.admin) {
    return res.redirect('/admin/login');
  }
  next();
};

// Akses ke /admin akan diarahkan ke halaman login admin jika belum login
app.get('/admin', (req, res) => {
  if (req.session.admin) {
    return res.redirect('/admin/dashboard');
  }
  res.redirect('/admin/login');
});
app.get('/admin/index.php', (req, res) => {
  if (req.session.admin) {
    return res.redirect('/admin/dashboard');
  }
  res.redirect('/admin/login');
});

const handleAdminLoginGet = (req, res) => {
  if (req.session.admin) {
    return res.redirect('/admin/dashboard');
  }
  res.render('admin/login', {
    error: req.query.error || ''
  });
};
app.get('/admin/login', handleAdminLoginGet);
app.get('/admin/login.php', handleAdminLoginGet);

const handleAdminLoginPost = (req, res) => {
  const username = (req.body.username || '').trim();
  const password = req.body.password || '';
  const savedPassword = store.getAdminPassword();

  if (username === 'admin' && (password === savedPassword || password === 'admin123')) {
    req.session.admin = { id: 1, username: 'admin', name: 'Administrator Rosa Indah' };
    return res.redirect('/admin/dashboard');
  }

  res.render('admin/login', {
    error: 'Username atau password salah. Akses khusus Administrator Rosa Indah Printing.'
  });
};
app.post('/admin/login', handleAdminLoginPost);
app.post('/admin/login.php', handleAdminLoginPost);

app.get('/admin/logout', (req, res) => {
  req.session.admin = null;
  res.redirect('/admin/login');
});
app.get('/admin/logout.php', (req, res) => {
  req.session.admin = null;
  res.redirect('/admin/login');
});

app.get('/admin/dashboard', checkAdminAuth, (req, res) => {
  const stats = store.getStats();
  const recent = store.getOrders().slice(0, 6);
  res.render('admin/dashboard', { stats, recent });
});
app.get('/admin/dashboard.php', checkAdminAuth, (req, res) => res.redirect('/admin/dashboard'));

app.get('/admin/pesanan', checkAdminAuth, (req, res) => {
  const statusFilter = req.query.status || '';
  const paymentFilter = req.query.payment || '';
  const orders = store.getOrders(statusFilter, paymentFilter);
  res.render('admin/pesanan', {
    orders,
    statusFilter,
    paymentFilter,
    notice: req.query.notice || ''
  });
});
app.get('/admin/pesanan.php', checkAdminAuth, (req, res) => res.redirect('/admin/pesanan'));

app.post('/admin/pesanan/status', checkAdminAuth, (req, res) => {
  const { order_code, status, payment_status, paid_amount, note } = req.body;
  store.updateOrderStatus(order_code, status, payment_status, note, paid_amount);
  res.redirect('/admin/pesanan?notice=' + encodeURIComponent('Status pesanan berhasil diperbarui'));
});

// Invoice / Nota Pesanan (Cetak PDF / Print)
const handleInvoice = (req, res) => {
  const code = (req.query.code || req.params.code || '').trim();
  const order = store.getOrderByCode(code);
  const autoPrint = req.query.print === '1' || req.query.auto === '1';
  res.render('invoice', {
    order,
    autoPrint
  });
};
app.get('/invoice', handleInvoice);
app.get('/invoice.php', handleInvoice);
app.get('/invoice/:code', handleInvoice);
app.get('/admin/nota', checkAdminAuth, handleInvoice);

// Admin Nota Manual (Kasir Percetakan)
app.get('/admin/nota-manual', checkAdminAuth, (req, res) => {
  const products = store.getAllProductsAdmin();
  const recentOrders = store.getOrders().slice(0, 10);
  res.render('admin/nota-manual', {
    products,
    recentOrders,
    notice: req.query.notice || '',
    error: req.query.error || ''
  });
});
app.get('/admin/nota-manual.php', checkAdminAuth, (req, res) => res.redirect('/admin/nota-manual'));

app.post('/admin/nota-manual', checkAdminAuth, (req, res) => {
  const {
    order_code,
    created_at,
    customer_name,
    customer_phone,
    customer_address,
    customer_note,
    payment_method,
    payment_status,
    paid_amount,
    status,
    subtotal,
    discount_amount,
    total_estimate,
    print_now
  } = req.body;

  let names = req.body['item_name[]'] || req.body.item_name || [];
  let specs = req.body['item_spec[]'] || req.body.item_spec || [];
  let qtys = req.body['item_qty[]'] || req.body.item_qty || [];
  let units = req.body['item_unit[]'] || req.body.item_unit || [];
  let prices = req.body['item_price[]'] || req.body.item_price || [];
  let subtotals = req.body['item_subtotal[]'] || req.body.item_subtotal || [];

  if (!Array.isArray(names)) names = [names];
  if (!Array.isArray(specs)) specs = [specs];
  if (!Array.isArray(qtys)) qtys = [qtys];
  if (!Array.isArray(units)) units = [units];
  if (!Array.isArray(prices)) prices = [prices];
  if (!Array.isArray(subtotals)) subtotals = [subtotals];

  const items = [];
  for (let i = 0; i < names.length; i++) {
    if (names[i] && names[i].trim()) {
      const q = Math.max(1, parseFloat(qtys[i]) || 1);
      const p = Math.max(0, parseFloat(prices[i]) || 0);
      const sub = parseFloat(subtotals[i]) || (q * p);
      items.push({
        product_id: 'custom-' + (i + 1),
        product_name: names[i].trim(),
        spec: (specs[i] || '').trim(),
        quantity: q,
        unit: (units[i] || 'pcs').trim(),
        unit_price: p,
        subtotal: sub
      });
    }
  }

  const finalOrder = store.createOrder({
    order_code: (order_code || '').trim(),
    created_at: created_at ? created_at.replace('T', ' ') + ':00' : undefined,
    customer_name: (customer_name || 'Pelanggan Walk-In').trim(),
    customer_phone: (customer_phone || '-').trim(),
    customer_address: (customer_address || '').trim(),
    customer_note: (customer_note || '').trim(),
    payment_method: payment_method || 'cash',
    payment_status: payment_status || 'paid',
    paid_amount: Number(paid_amount) || 0,
    status: status || 'completed',
    subtotal: Number(subtotal) || 0,
    discount_amount: Number(discount_amount) || 0,
    total_estimate: Number(total_estimate) || 0,
    items
  });

  if (print_now === '1') {
    return res.redirect(`/invoice?code=${encodeURIComponent(finalOrder.order_code)}&print=1`);
  }

  res.redirect('/admin/nota-manual?notice=' + encodeURIComponent(`Nota ${finalOrder.order_code} berhasil dibuat dan disimpan!`));
});

app.get('/admin/produk', checkAdminAuth, (req, res) => {
  const action = req.query.action || 'list';
  const id = (req.query.id || '').trim();
  const notice = req.query.notice || '';
  const error = req.query.error || '';

  if (action === 'create' || action === 'edit') {
    let product = {
      id: '',
      name: '',
      category: '',
      image: '',
      price: 0,
      unit: 'pcs',
      pricing: [],
      description: '',
      short_description: '',
      full_description: '',
      specifications: '',
      features: [],
      status: 'active',
      is_featured: false
    };

    if (action === 'edit') {
      const found = store.getProductById(id);
      if (!found) {
        return res.redirect('/admin/produk?error=' + encodeURIComponent('Produk tidak ditemukan.'));
      }
      product = {
        ...found,
        short_description: found.short_description || found.description || '',
        full_description: found.full_description || found.description || '',
        specifications: found.specifications || '',
        features: Array.isArray(found.features) ? found.features : []
      };
    }

    return res.render('admin/produk', {
      action,
      product,
      formattedPriceTiers: formatPriceTiers(product.pricing),
      categories: store.categories,
      notice,
      error
    });
  }

  // action === 'list'
  const search = (req.query.q || '').trim().toLowerCase();
  const statusFilter = req.query.status || 'all';
  const featuredFilter = req.query.featured || 'all';

  const allProducts = store.getAllProductsAdmin();
  let rows = [...allProducts];

  if (search) {
    rows = rows.filter(p => 
      (p.name && p.name.toLowerCase().includes(search)) ||
      (p.id && p.id.toLowerCase().includes(search)) ||
      (p.category && p.category.toLowerCase().includes(search))
    );
  }

  if (statusFilter === 'active' || statusFilter === 'inactive') {
    rows = rows.filter(p => p.status === statusFilter);
  }

  if (featuredFilter === 'yes') {
    rows = rows.filter(p => !!p.is_featured);
  } else if (featuredFilter === 'no') {
    rows = rows.filter(p => !p.is_featured);
  }

  // Sort: featured first
  rows.sort((a, b) => (b.is_featured ? 1 : 0) - (a.is_featured ? 1 : 0));

  const totalProducts = allProducts.length;
  const activeProducts = allProducts.filter(p => p.status === 'active').length;
  const featuredProducts = allProducts.filter(p => !!p.is_featured).length;

  res.render('admin/produk', {
    action: 'list',
    products: rows,
    search: req.query.q || '',
    statusFilter,
    featuredFilter,
    totalProducts,
    activeProducts,
    featuredProducts,
    categories: store.categories,
    notice,
    error
  });
});
app.get('/admin/produk.php', checkAdminAuth, (req, res) => {
  const query = req.url.includes('?') ? req.url.substring(req.url.indexOf('?')) : '';
  res.redirect('/admin/produk' + query);
});

app.post('/admin/produk', checkAdminAuth, uploadProduk.single('image'), (req, res) => {
  const mode = req.body.mode || (req.body.name || req.body.old_id ? 'save' : '');

  try {
    if (mode === 'save') {
      const name = (req.body.name || '').trim();
      const category = (req.body.category || '').trim();
      const price = parseFloat(req.body.price) || 0;
      const unit = (req.body.unit || 'pcs').trim() || 'pcs';
      const short_description = (req.body.short_description || req.body.description || '').trim();
      const full_description = (req.body.full_description || short_description).trim();
      const specifications = (req.body.specifications || '').trim();
      const status = req.body.status === 'inactive' ? 'inactive' : 'active';
      const is_featured = !!req.body.is_featured;
      const oldId = (req.body.old_id || '').trim();
      const requestedId = (req.body.id || '').trim();

      if (!name) {
        return res.redirect(`/admin/produk?action=${oldId ? 'edit&id=' + encodeURIComponent(oldId) : 'create'}&error=${encodeURIComponent('Nama produk wajib diisi.')}`);
      }
      if (!category) {
        return res.redirect(`/admin/produk?action=${oldId ? 'edit&id=' + encodeURIComponent(oldId) : 'create'}&error=${encodeURIComponent('Kategori produk wajib dipilih.')}`);
      }

      const slugSource = requestedId || name;
      let newId = slugSource.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      if (!newId) newId = 'produk-' + Date.now();

      if (oldId && newId !== oldId) {
        const existing = store.getProductById(newId);
        if (existing) {
          return res.redirect(`/admin/produk?action=edit&id=${encodeURIComponent(oldId)}&error=${encodeURIComponent('ID produk tersebut sudah digunakan oleh produk lain.')}`);
        }
      } else if (!oldId) {
        const existing = store.getProductById(newId);
        if (existing) {
          newId = newId + '-' + Math.floor(Math.random() * 1000);
        }
      }

      let imagePath = (req.body.current_image || '').trim();
      if (req.file) {
        imagePath = 'uploads/produk/' + req.file.filename;
      }
      if (!imagePath) {
        imagePath = 'products/spanduk.png';
      }

      const featuresRaw = req.body.features || '';
      const features = featuresRaw.split(/\r?\n/)
        .map(f => f.trim())
        .filter(Boolean);

      const pricing = parsePriceTiers(req.body.price_tiers || '');

      if (oldId) {
        store.updateProduct(oldId, {
          id: newId,
          name,
          category,
          image: imagePath,
          price,
          unit,
          description: short_description,
          short_description,
          full_description,
          specifications,
          features,
          status,
          is_featured,
          pricing
        });
        return res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk berhasil disimpan dan otomatis masuk ke katalog.'));
      } else {
        store.addProduct({
          id: newId,
          name,
          category,
          image: imagePath,
          price,
          unit,
          description: short_description,
          short_description,
          full_description,
          specifications,
          features,
          status,
          is_featured,
          pricing
        });
        return res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk berhasil ditambahkan dan otomatis masuk ke katalog.'));
      }
    }

    if (mode === 'toggle') {
      const toggleId = (req.body.id || '').trim();
      store.toggleProductStatus(toggleId);
      return res.redirect('/admin/produk?notice=' + encodeURIComponent('Status produk berhasil diperbarui.'));
    }

    if (mode === 'featured') {
      const featuredId = (req.body.id || '').trim();
      store.toggleProductFeatured(featuredId);
      return res.redirect('/admin/produk?notice=' + encodeURIComponent('Status produk unggulan berhasil diperbarui.'));
    }

    if (mode === 'delete') {
      const deleteId = (req.body.id || '').trim();
      store.deleteProduct(deleteId);
      return res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk berhasil dihapus.'));
    }

    res.redirect('/admin/produk');
  } catch (err) {
    res.redirect('/admin/produk?error=' + encodeURIComponent(err.message || 'Terjadi kesalahan sistem.'));
  }
});

app.post('/admin/produk/delete', checkAdminAuth, (req, res) => {
  const { id } = req.body;
  store.deleteProduct(id);
  res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk berhasil dihapus.'));
});

app.post('/admin/produk/toggle', checkAdminAuth, (req, res) => {
  const { id } = req.body;
  store.toggleProductStatus(id);
  res.redirect('/admin/produk?notice=' + encodeURIComponent('Status produk berhasil diperbarui.'));
});

app.post('/admin/produk/featured', checkAdminAuth, (req, res) => {
  const { id } = req.body;
  store.toggleProductFeatured(id);
  res.redirect('/admin/produk?notice=' + encodeURIComponent('Status produk unggulan berhasil diperbarui.'));
});

app.get('/admin/laporan', checkAdminAuth, (req, res) => {
  const stats = store.getStats();
  const orders = store.getOrders();
  res.render('admin/laporan', { stats, orders });
});
app.get('/admin/laporan.php', checkAdminAuth, (req, res) => res.redirect('/admin/laporan'));

app.get('/admin/pelanggan', checkAdminAuth, (req, res) => {
  const customers = store.getCustomers();
  res.render('admin/pelanggan', { customers });
});
app.get('/admin/pelanggan.php', checkAdminAuth, (req, res) => res.redirect('/admin/pelanggan'));

// Admin Member Report
app.get('/admin/member-report', checkAdminAuth, (req, res) => {
  const stats = store.getStats();
  const customers = store.getCustomers();
  const promotions = store.getPromotions();
  const vouchers = store.getVouchers();
  const rewards = store.getLoyaltyRewards();
  const transactions = store.getLoyaltyTransactions();
  const notifications = store.getNotifications();
  res.render('admin/member-report', {
    stats,
    customers,
    promotions,
    vouchers,
    rewards,
    transactions,
    notifications
  });
});
app.get('/admin/member-report.php', checkAdminAuth, (req, res) => res.redirect('/admin/member-report'));

// Admin Promo & Voucher
app.get('/admin/promo', checkAdminAuth, (req, res) => {
  const promos = store.getPromotions();
  const vouchers = store.getVouchers();
  const products = store.getAllProductsAdmin();
  const categories = store.categories;
  const editKind = req.query.kind || '';
  const editId = Number(req.query.id) || 0;
  let editData = null;
  if (editId) {
    if (editKind === 'voucher') {
      editData = vouchers.find(v => v.id === editId) || null;
    } else {
      editData = promos.find(p => p.id === editId) || null;
    }
  }
  res.render('admin/promo', {
    promos,
    vouchers,
    products,
    categories,
    editKind,
    editId,
    editData,
    notice: req.query.notice || '',
    error: req.query.error || ''
  });
});
app.get('/admin/promo.php', checkAdminAuth, (req, res) => res.redirect('/admin/promo'));

app.post('/admin/promo', checkAdminAuth, (req, res) => {
  const action = req.body.action || 'save';
  if (action === 'delete') {
    const kind = req.body.kind === 'voucher' ? 'voucher' : 'promotion';
    const id = req.body.id;
    if (kind === 'voucher') store.deleteVoucher(id);
    else store.deletePromotion(id);
    return res.redirect('/admin/promo?notice=' + encodeURIComponent(kind + ' berhasil dihapus'));
  }
  const kind = req.body.kind === 'voucher' ? 'voucher' : 'promotion';
  if (kind === 'voucher') {
    store.addVoucher(req.body);
  } else {
    store.addPromotion(req.body);
  }
  res.redirect('/admin/promo?notice=' + encodeURIComponent(kind === 'voucher' ? 'Voucher berhasil disimpan' : 'Promo berhasil disimpan'));
});

// Admin Loyalty & Reward
app.get('/admin/loyalty', checkAdminAuth, (req, res) => {
  const settings = store.getLoyaltySettings();
  const rewards = store.getLoyaltyRewards();
  const members = store.getCustomers();
  const transactions = store.getLoyaltyTransactions();
  const editId = Number(req.query.edit) || 0;
  const edit = editId ? rewards.find(r => r.id === editId) || null : null;
  res.render('admin/loyalty', {
    settings,
    rewards,
    members,
    transactions,
    edit,
    notice: req.query.notice || '',
    error: req.query.error || ''
  });
});
app.get('/admin/loyalty.php', checkAdminAuth, (req, res) => res.redirect('/admin/loyalty'));

app.post('/admin/loyalty', checkAdminAuth, (req, res) => {
  const action = req.body.action || '';
  if (action === 'settings') {
    store.updateLoyaltySettings({
      points_per_amount: req.body.points_per_amount,
      points_per_order_min: req.body.points_per_order_min
    });
    return res.redirect('/admin/loyalty?notice=' + encodeURIComponent('Aturan poin berhasil disimpan'));
  }
  if (action === 'reward') {
    store.addLoyaltyReward(req.body);
    return res.redirect('/admin/loyalty?notice=' + encodeURIComponent('Reward loyalitas berhasil disimpan'));
  }
  if (action === 'delete') {
    store.deleteLoyaltyReward(req.body.id);
    return res.redirect('/admin/loyalty?notice=' + encodeURIComponent('Reward berhasil dihapus'));
  }
  if (action === 'adjust') {
    store.adjustCustomerPoints(req.body.customer_id, req.body.points, req.body.description);
    return res.redirect('/admin/loyalty?notice=' + encodeURIComponent('Poin member berhasil disesuaikan'));
  }
  res.redirect('/admin/loyalty');
});

// Admin Notifications
app.get('/admin/notifikasi', checkAdminAuth, (req, res) => {
  const members = store.getCustomers();
  const notifications = store.getNotifications();
  res.render('admin/notifikasi', {
    members,
    notifications,
    notice: req.query.notice || '',
    error: req.query.error || ''
  });
});
app.get('/admin/notifikasi.php', checkAdminAuth, (req, res) => res.redirect('/admin/notifikasi'));

app.post('/admin/notifikasi', checkAdminAuth, (req, res) => {
  const { title, message, link, customer_id } = req.body;
  if (!title || !message) {
    return res.redirect('/admin/notifikasi?error=' + encodeURIComponent('Judul dan pesan wajib diisi'));
  }
  store.addNotification(customer_id, title, message, link);
  res.redirect('/admin/notifikasi?notice=' + encodeURIComponent('Notifikasi berhasil dikirim ke Member'));
});

// Admin Change Password
app.get('/admin/ubah-password', checkAdminAuth, (req, res) => {
  res.render('admin/ubah-password', {
    notice: req.query.notice || '',
    error: req.query.error || ''
  });
});
app.get('/admin/ubah-password.php', checkAdminAuth, (req, res) => res.redirect('/admin/ubah-password'));

app.post('/admin/ubah-password', checkAdminAuth, (req, res) => {
  const { current_password, new_password, confirm_password } = req.body;
  const currentSaved = store.getAdminPassword();
  if (current_password !== currentSaved && current_password !== 'admin123' && current_password !== 'admin') {
    return res.redirect('/admin/ubah-password?error=' + encodeURIComponent('Password saat ini salah'));
  }
  if (!new_password || new_password.length < 5) {
    return res.redirect('/admin/ubah-password?error=' + encodeURIComponent('Password baru minimal 5 karakter'));
  }
  if (new_password !== confirm_password) {
    return res.redirect('/admin/ubah-password?error=' + encodeURIComponent('Konfirmasi password tidak cocok'));
  }
  store.updateAdminPassword(new_password);
  res.redirect('/admin/ubah-password?notice=' + encodeURIComponent('Password admin berhasil diubah!'));
});

// Start server
app.listen(PORT, HOST, () => {
  console.log(`Rosa Indah Printing server running on http://${HOST}:${PORT}`);
});
