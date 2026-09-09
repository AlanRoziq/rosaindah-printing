CREATE DATABASE IF NOT EXISTS rosaindahprinting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rosaindahprinting;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(80) PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  category VARCHAR(100) NOT NULL,
  image VARCHAR(255) NOT NULL,
  price DECIMAL(14,2) NOT NULL DEFAULT 0,
  unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
  description TEXT NULL,
  features JSON NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_category (category),
  INDEX idx_products_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_variants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id VARCHAR(80) NOT NULL,
  variant_type VARCHAR(50) NOT NULL,
  variant_name VARCHAR(120) NOT NULL,
  extra_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_variant_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_price_tiers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id VARCHAR(80) NOT NULL,
  min_qty INT UNSIGNED NOT NULL DEFAULT 1,
  max_qty INT UNSIGNED NULL,
  price DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_price_tier_product_qty (product_id, min_qty)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(160) NULL,
  password VARCHAR(255) NOT NULL,
  address TEXT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 1,
  verification_code_hash VARCHAR(255) NULL,
  verification_expires_at DATETIME NULL,
  verification_sent_at DATETIME NULL,
  verification_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customers_phone (phone),
  INDEX idx_customers_verified (is_verified)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NULL,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  customer_name VARCHAR(120) NOT NULL,
  customer_phone VARCHAR(30) NOT NULL,
  customer_note TEXT NULL,
  subtotal_before_discount DECIMAL(14,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  voucher_code VARCHAR(60) NULL,
  promotion_id BIGINT UNSIGNED NULL,
  total_estimate DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('pending','confirmed','processing','printed','completed','cancelled') NOT NULL DEFAULT 'pending',
  payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  payment_method VARCHAR(30) NULL,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  payment_note TEXT NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_customer (customer_id),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id VARCHAR(80) NULL,
  product_name VARCHAR(180) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
  variant_data JSON NULL,
  unit_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  method VARCHAR(30) NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  proof_path VARCHAR(500) NULL,
  proof_name VARCHAR(255) NULL,
  note TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'waiting',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_order_payments_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_order_attachments_order (order_id)
) ENGINE=InnoDB;



CREATE TABLE IF NOT EXISTS promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
  max_discount DECIMAL(14,2) NULL,
  scope_type ENUM('all','category','product') NOT NULL DEFAULT 'all',
  scope_value VARCHAR(180) NULL,
  member_only TINYINT(1) NOT NULL DEFAULT 1,
  quota INT UNSIGNED NULL,
  usage_count INT UNSIGNED NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_promotions_active (status, starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vouchers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
  max_discount DECIMAL(14,2) NULL,
  scope_type ENUM('all','category','product') NOT NULL DEFAULT 'all',
  scope_value VARCHAR(180) NULL,
  member_only TINYINT(1) NOT NULL DEFAULT 1,
  quota INT UNSIGNED NULL,
  usage_count INT UNSIGNED NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_vouchers_active (status, starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS voucher_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voucher_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(60) NOT NULL,
  discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voucher_order (voucher_id, order_id),
  INDEX idx_voucher_customer (voucher_id, customer_id),
  FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO admins (name, username, password)
SELECT 'Administrator', 'admin', '$2y$12$6G9UtGmvN6b814LsSP8QIuX1QtxJrA/1yc365IQrpI/3QzEdruDjO'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username='admin');

INSERT IGNORE INTO categories (name) VALUES
('Undangan Nikah'),('Print Scan Copy'),('MUG'),('Banner'),('Cetak Buku'),('Lanyard & Id Card'),('Spanduk'),('Kartu Nama'),('Sticker'),('Undangan Lipat 3'),('Nota / Buku Tabungan'),('Kalender'),('Sablon DTF'),('Cetak Foto'),('Cetak Pigora'),('Gantungan Kunci');

INSERT INTO products (id,name,category,image,price,unit,description,features,is_featured)
SELECT * FROM (
  SELECT 'banner','Banner & Spanduk','Banner','products/spanduk.png',25000,'meter','Banner dan spanduk custom dengan hasil cetak tajam dan warna cerah untuk kebutuhan promosi maupun acara.',JSON_ARRAY('Ukuran bebas','Full color','Bahan pilihan','Siap pasang'),1 UNION ALL
  SELECT 'undangan-nikah','Undangan Nikah','Undangan Nikah','products/undnikah.jpeg',1500,'pcs','Undangan pernikahan elegan yang dapat disesuaikan dengan konsep dan kebutuhan acara Anda.',JSON_ARRAY('Desain custom','Pilihan kertas','Cetak full color','Bisa request finishing'),1 UNION ALL
  SELECT 'undangan-lipat-3','Undangan Lipat 3','Undangan Lipat 3','products/undf4.png',1500,'pcs','Undangan lipat tiga untuk berbagai acara seperti walimah, aqiqah, tahlil, dan acara keluarga.',JSON_ARRAY('Desain custom','Format lipat 3','Kertas berkualitas','Cetak tajam'),0 UNION ALL
  SELECT 'kartu-nama','Kartu Nama','Kartu Nama','products/kartunama.png',25000,'box','Kartu nama profesional untuk kebutuhan bisnis, usaha, organisasi, maupun personal branding.',JSON_ARRAY('Desain custom','Cetak dua sisi','Pilihan finishing','Cocok untuk bisnis'),1 UNION ALL
  SELECT 'sticker','Sticker Custom','Sticker','products/sticker.jpeg',10000,'lembar','Sticker custom untuk branding produk, kemasan, promosi, label, dan kebutuhan lainnya.',JSON_ARRAY('Cutting custom','Bahan pilihan','Warna tajam','Cocok untuk kemasan'),0 UNION ALL
  SELECT 'nota','Nota','Nota / Buku Tabungan','products/nota.jpeg',40000,'buku','Nota usaha yang praktis dan profesional untuk pencatatan transaksi toko maupun bisnis.',JSON_ARRAY('Custom nama usaha','Nomor urut','Pilihan rangkap','Layout sesuai kebutuhan'),0 UNION ALL
  SELECT 'buku','Cetak Buku','Cetak Buku','products/buku.png',30000,'pcs','Layanan cetak buku custom untuk kebutuhan pribadi, sekolah, komunitas, dan bisnis.',JSON_ARRAY('Ukuran custom','Cover custom','Pilihan jilid','Cetak berkualitas'),0 UNION ALL
  SELECT 'kalender','Kalender','Kalender','products/kalender.png',25000,'pcs','Kalender meja atau dinding dengan desain custom untuk kebutuhan pribadi maupun promosi.',JSON_ARRAY('Desain custom','Meja & dinding','Cetak full color','Cocok untuk souvenir'),0 UNION ALL
  SELECT 'sablon-dtf','Sablon DTF','Sablon DTF','products/sablon.png',75000,'pcs','Sablon DTF untuk kaos dan media tekstil dengan desain custom sesuai keinginan Anda.',JSON_ARRAY('Desain custom','Warna tajam','Detail bagus','Cocok untuk kaos'),0 UNION ALL
  SELECT 'mug-custom','Mug Custom','MUG','products/mug.png',35000,'pcs','Mug custom untuk hadiah, souvenir, branding, acara keluarga, maupun kebutuhan usaha.',JSON_ARRAY('Desain custom','Cocok untuk souvenir','Cetak full color','Pesanan satuan tersedia'),0 UNION ALL
  SELECT 'lanyard-id-card','Lanyard & ID Card','Lanyard & Id Card','products/lanyard.png',15000,'pcs','Lanyard dan ID card custom untuk sekolah, kantor, event, komunitas, dan organisasi.',JSON_ARRAY('Desain custom','Pilihan ukuran','Cocok untuk event','Bisa satuan/partai'),0 UNION ALL
  SELECT 'cetak-foto','Cetak Foto','Cetak Foto','products/foto.jpeg',1000,'lembar','Cetak foto dengan warna tajam untuk mengabadikan momen berharga Anda.',JSON_ARRAY('Berbagai ukuran','Warna tajam','Kertas foto berkualitas','Cocok untuk dokumentasi'),0 UNION ALL
  SELECT 'pigora','Cetak Pigora','Cetak Pigora','products/pigora.jpeg',35000,'pcs','Cetak foto dengan pigora untuk dekorasi rumah, hadiah, kenang-kenangan, atau souvenir.',JSON_ARRAY('Ukuran custom','Pilihan pigora','Cocok untuk hadiah','Finishing rapi'),0 UNION ALL
  SELECT 'gantungan-kunci','Gantungan Kunci','Gantungan Kunci','products/ganci.png',10000,'pcs','Gantungan kunci custom untuk souvenir, merchandise, komunitas, maupun hadiah.',JSON_ARRAY('Desain custom','Cocok untuk souvenir','Bisa partai','Bentuk menarik'),0 UNION ALL
  SELECT 'print-scan-copy','Print, Scan & Copy','Print Scan Copy','products/psc.jpeg',500,'lembar','Layanan print, scan, dan copy cepat untuk kebutuhan dokumen sehari-hari.',JSON_ARRAY('Proses cepat','Dokumen warna & hitam putih','Scan dokumen','Copy dokumen'),0
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM products LIMIT 1);

-- Migrasi kategori produk ke kategori spesifik sesuai katalog Home/Admin.
UPDATE products SET category='Undangan Nikah' WHERE id='undangan-nikah';
UPDATE products SET category='Print Scan Copy' WHERE id='print-scan-copy';
UPDATE products SET category='MUG' WHERE id='mug-custom';
UPDATE products SET category='Banner' WHERE id='banner';
UPDATE products SET category='Cetak Buku' WHERE id='buku';
UPDATE products SET category='Lanyard & Id Card' WHERE id='lanyard-id-card';
UPDATE products SET category='Spanduk' WHERE id='spanduk';
UPDATE products SET category='Kartu Nama' WHERE id='kartu-nama';
UPDATE products SET category='Sticker' WHERE id='sticker';
UPDATE products SET category='Undangan Lipat 3' WHERE id='undangan-lipat-3';
UPDATE products SET category='Nota / Buku Tabungan' WHERE id IN ('nota','buku-tabungan');
UPDATE products SET category='Kalender' WHERE id='kalender';
UPDATE products SET category='Sablon DTF' WHERE id='sablon-dtf';
UPDATE products SET category='Cetak Foto' WHERE id='cetak-foto';
UPDATE products SET category='Cetak Pigora' WHERE id='pigora';
UPDATE products SET category='Gantungan Kunci' WHERE id='gantungan-kunci';

-- Tahap 23: Member Points / Loyalty & Reward
CREATE TABLE IF NOT EXISTS loyalty_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  points_per_amount DECIMAL(14,2) NOT NULL DEFAULT 10000,
  points_per_order_min DECIMAL(14,2) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO loyalty_settings (id, points_per_amount, points_per_order_min) VALUES (1,10000,0);

CREATE TABLE IF NOT EXISTS loyalty_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  type ENUM('earn','redeem','adjust') NOT NULL,
  points INT NOT NULL,
  balance_after INT NOT NULL DEFAULT 0,
  description VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_loyalty_order_earn (order_id, type),
  INDEX idx_loyalty_customer (customer_id, created_at),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loyalty_rewards (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  points_cost INT UNSIGNED NOT NULL,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
  discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
  min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
  max_discount DECIMAL(14,2) NULL,
  validity_days INT UNSIGNED NOT NULL DEFAULT 30,
  quota INT UNSIGNED NULL,
  redeemed_count INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loyalty_redemptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reward_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  points_spent INT UNSIGNED NOT NULL,
  voucher_id BIGINT UNSIGNED NULL,
  voucher_code VARCHAR(60) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_loyalty_red_customer (customer_id, created_at),
  FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS customer_id BIGINT UNSIGNED NULL AFTER member_only;

-- TAHAP 24: Notifikasi Member
CREATE TABLE IF NOT EXISTS member_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'general',
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(500) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  INDEX idx_member_notifications_customer (customer_id, is_read, created_at),
  INDEX idx_member_notifications_type (type, created_at),
  CONSTRAINT fk_member_notifications_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
