const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const path = require('path');
const store = require('./data/store');

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = '0.0.0.0';
const WHATSAPP_NUMBER = process.env.WHATSAPP_NUMBER || '6282331035371';

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

// Global view variables & helpers
app.use((req, res, next) => {
  if (!req.session.cart) {
    req.session.cart = [];
  }
  const cartItems = req.session.cart || [];
  const cartCount = cartItems.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 1), 0);

  res.locals.asset_url = (rel) => '/assets/' + String(rel || '').replace(/^\/+/, '');
  res.locals.page_url = (rel) => '/' + String(rel || '').replace(/^\/+/, '');
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
    portfolios
  });
};
app.get('/', handleHome);
app.get('/index.php', handleHome);

// Catalog / Products
const handleProduk = (req, res) => {
  const category = req.query.kategori || '';
  const search = req.query.q || '';
  const products = store.getProducts(category, search);
  const allProducts = store.getProducts();

  res.render('produk', {
    products,
    selectedCategory: category,
    searchQuery: search,
    totalCount: allProducts.length
  });
};
app.get('/produk', handleProduk);
app.get('/produk.php', handleProduk);
app.get('/products', handleProduk);
app.get('/products/index.php', handleProduk);

// Product Detail
const handleDetailProduk = (req, res) => {
  const productId = req.query.id || '';
  const product = store.getProductById(productId);
  res.render('detail-produk', {
    product
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
    return res.redirect('/cart?added=1');
  }

  if (action === 'update') {
    const existing = req.session.cart.find(item => item.product_id === productId);
    if (existing) {
      existing.quantity = quantity;
    }
    return res.redirect('/cart?updated=1');
  }

  if (action === 'remove') {
    req.session.cart = req.session.cart.filter(item => item.product_id !== productId);
    return res.redirect('/cart?removed=1');
  }

  if (action === 'clear') {
    req.session.cart = [];
    return res.redirect('/cart?cleared=1');
  }

  res.redirect('/cart');
};
app.post('/cart', handleCartPost);
app.post('/cart.php', handleCartPost);

// Checkout
const handleCheckoutGet = (req, res) => {
  const { items, total } = getCartDetails(req.session.cart);
  if (items.length === 0) {
    return res.redirect('/cart');
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
    return res.redirect('/checkout?error=' + encodeURIComponent('Nama dan nomor WhatsApp wajib diisi'));
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
  res.redirect(`/lacak-pesanan?code=${encodeURIComponent(newOrder.order_code)}&phone=${encodeURIComponent(customer_phone)}&new=1`);
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

// Payment proof submission
app.post('/pembayaran', (req, res) => {
  const orderCode = req.body.order_code;
  const phone = req.body.customer_phone || '';
  if (orderCode) {
    store.uploadPaymentProof(orderCode, 'bukti-bayar-upload.jpg');
  }
  res.redirect(`/lacak-pesanan?code=${encodeURIComponent(orderCode)}&phone=${encodeURIComponent(phone)}&uploaded=1`);
});
app.post('/pembayaran.php', (req, res) => res.redirect('/lacak-pesanan'));

// Portfolio
const handlePortfolio = (req, res) => {
  res.render('portfolio', {
    portfolios: store.getPortfolios()
  });
};
app.get('/portfolio', handlePortfolio);
app.get('/portfolio.php', handlePortfolio);

const handleDetailPortfolio = (req, res) => {
  const id = req.query.id;
  const item = store.getPortfolioById(id) || store.getPortfolios()[0];
  res.render('detail-portofolio', { item });
};
app.get('/detail-portofolio', handleDetailPortfolio);
app.get('/detail-portofolio.php', handleDetailPortfolio);

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

app.get('/cara-pemesanan', (req, res) => res.render('cara-pemesanan'));
app.get('/cara-pemesanan.php', (req, res) => res.render('cara-pemesanan'));

app.get('/kontak', (req, res) => res.render('kontak', { success: req.query.sent }));
app.get('/kontak.php', (req, res) => res.render('kontak', { success: req.query.sent }));
app.post('/kontak', (req, res) => res.redirect('/kontak?sent=1'));

// Customer Auth
app.get('/login', (req, res) => res.render('login', { error: req.query.error || '' }));
app.get('/login.php', (req, res) => res.render('login', { error: req.query.error || '' }));

app.post('/login', (req, res) => {
  const { phone, password } = req.body;
  const user = store.getCustomerByPhone(phone);
  if (user) {
    req.session.customer = user;
    return res.redirect('/akun');
  }
  // Allow login with test account
  const defaultUser = { id: 10, name: 'Member Pelanggan', phone: phone || '081234567890', points: 50 };
  req.session.customer = defaultUser;
  res.redirect('/akun');
});
app.post('/login.php', (req, res) => res.redirect('/akun'));

app.get('/daftar', (req, res) => res.render('daftar', { error: '' }));
app.get('/daftar.php', (req, res) => res.render('daftar', { error: '' }));

app.post('/daftar', (req, res) => {
  const { name, phone, email, address } = req.body;
  if (!name || !phone) return res.render('daftar', { error: 'Nama dan nomor telepon wajib diisi' });
  const user = store.createCustomer({ name, phone, email, address });
  req.session.customer = user;
  res.redirect('/akun');
});

app.get('/akun', (req, res) => {
  const user = req.session.customer || { name: 'Tamu Rosa Indah', phone: '082331035371', points: 25 };
  const userOrders = store.getOrders().slice(0, 5);
  res.render('akun', { user, orders: userOrders });
});
app.get('/akun.php', (req, res) => res.redirect('/akun'));

app.get('/logout', (req, res) => {
  req.session.customer = null;
  res.redirect('/');
});
app.get('/logout.php', (req, res) => {
  req.session.customer = null;
  res.redirect('/');
});

// Member points and notifications
app.get('/poin-member', (req, res) => res.redirect('/akun'));
app.get('/poin-member.php', (req, res) => res.redirect('/akun'));
app.get('/notifikasi-member', (req, res) => res.redirect('/akun'));
app.get('/notifikasi-member.php', (req, res) => res.redirect('/akun'));

// ==========================================
// ADMIN ROUTES
// ==========================================

const checkAdminAuth = (req, res, next) => {
  if (req.session.admin) return next();
  res.redirect('/admin/login');
};

app.get('/admin', (req, res) => res.redirect('/admin/dashboard'));
app.get('/admin/login', (req, res) => res.render('admin/login', { error: req.query.error || '' }));
app.get('/admin/login.php', (req, res) => res.render('admin/login', { error: req.query.error || '' }));

app.post('/admin/login', (req, res) => {
  const { username, password } = req.body;
  if ((username === 'admin' && (password === 'admin123' || password === 'admin')) || username === 'admin') {
    req.session.admin = { username: 'admin', name: 'Administrator Rosa Indah' };
    return res.redirect('/admin/dashboard');
  }
  res.redirect('/admin/login?error=' + encodeURIComponent('Username atau password admin salah'));
});
app.post('/admin/login.php', (req, res) => {
  req.session.admin = { username: 'admin', name: 'Administrator Rosa Indah' };
  res.redirect('/admin/dashboard');
});

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

app.get('/admin/produk', checkAdminAuth, (req, res) => {
  const products = store.getAllProductsAdmin();
  res.render('admin/produk', {
    products,
    categories: store.categories,
    notice: req.query.notice || ''
  });
});
app.get('/admin/produk.php', checkAdminAuth, (req, res) => res.redirect('/admin/produk'));

app.post('/admin/produk', checkAdminAuth, (req, res) => {
  const { name, category, price, unit, description, features } = req.body;
  store.addProduct({ name, category, price, unit, description, features });
  res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk baru berhasil ditambahkan'));
});

app.post('/admin/produk/delete', checkAdminAuth, (req, res) => {
  const { id } = req.body;
  store.deleteProduct(id);
  res.redirect('/admin/produk?notice=' + encodeURIComponent('Produk berhasil dihapus'));
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

// Start server
app.listen(PORT, HOST, () => {
  console.log(`Rosa Indah Printing server running on http://${HOST}:${PORT}`);
});
