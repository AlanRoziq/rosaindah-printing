# ROSA INDAH PRINTING — TAHAP 20
## Paket Siap Persiapan Hosting

File ZIP ini adalah versi yang dirapikan dari website terakhir yang sudah stabil. Perubahan Tahap 20 fokus pada **security + deployment**, bukan redesign. Fitur customer/admin, desktop, dan responsive mobile yang sudah selesai dipertahankan.

---

# A. Yang sudah saya rapikan di file ini

### 20A — Security Hardening
- `admin/test-admin.php` dihapus dari paket production.
- Teks password awal `admin/admin123` di halaman login dihapus.
- Login Admin sekarang memakai CSRF token.
- Login Admin mendapat pembatasan percobaan: maksimal 5 kegagalan dalam jendela sekitar 10 menit pada sesi yang sama + jeda kecil untuk memperlambat percobaan otomatis.
- Ditambahkan halaman `admin/ubah-password.php` untuk mengganti password Admin dengan aman setelah login.
- Form konfirmasi pembayaran diberi CSRF token.
- Folder upload diberi proteksi agar file PHP/script tidak dapat dieksekusi.
- Security headers dan session security yang sudah ada tetap dipertahankan.

### 20B — Database Production
Website masih menggunakan konfigurasi XAMPP agar tetap bisa dites di komputer lokal.

Sebelum online, hanya bagian ini yang perlu disesuaikan:

`config/database.php`

```text
DB_HOST = host database dari hosting
DB_NAME = nama database hosting
DB_USER = user database hosting
DB_PASS = password database hosting
```

**Jangan gunakan user `root` di hosting.**

Database awal ada di:

`database/rosaindahprinting.sql`

Import file tersebut melalui phpMyAdmin hosting.

### 20C — Deployment Hosting
Setelah hosting sudah dibeli:

1. Buat database MySQL.
2. Import `database/rosaindahprinting.sql`.
3. Upload isi folder website ke document root hosting, biasanya `public_html/`.
4. Sesuaikan `config/database.php`.
5. Sesuaikan `BASE_URL` di `config/config.php`.
6. Ubah `APP_ENV` dari `local` menjadi `production`.
7. Aktifkan SSL/HTTPS.
8. Pastikan folder upload dapat ditulis oleh PHP sesuai permission hosting.
9. Jangan upload backup database ke folder public.
10. Jangan upload file ZIP ini ke `public_html` setelah proses selesai.

### 20D — Final Testing
Setelah domain online, lakukan testing dari HP dan laptop.

## Customer
- [ ] Home
- [ ] Navbar desktop/mobile
- [ ] Menu mobile
- [ ] Kategori
- [ ] Produk
- [ ] Detail produk
- [ ] Fullscreen + zoom gambar
- [ ] Keranjang
- [ ] Checkout
- [ ] Upload desain
- [ ] Login/Daftar pelanggan
- [ ] Riwayat pesanan
- [ ] Tracking pesanan
- [ ] Pembayaran + upload bukti
- [ ] Invoice/Nota
- [ ] Responsive Android
- [ ] Responsive iPhone

## Admin
- [ ] Login Admin
- [ ] Ubah password Admin
- [ ] Dashboard statistik
- [ ] Produk: tambah/edit/hapus
- [ ] Aktif/nonaktif
- [ ] Produk unggulan
- [ ] Pesanan
- [ ] Pembayaran
- [ ] Pelanggan
- [ ] Download desain pelanggan
- [ ] Laporan
- [ ] Backup database

---

# URUTAN KERJA YANG MUDAH DIPAHAMI

## SEKARANG — MASIH LOCALHOST

Tidak perlu membeli domain dulu untuk menyelesaikan audit.

1. Replace project lokal dengan ZIP Tahap 20 ini.
2. Jalankan XAMPP Apache + MySQL.
3. Login Admin menggunakan akun Admin yang sekarang.
4. Buka `Admin → Ubah Password`.
5. Ganti password dari password lama ke password kuat.
6. Tes semua fitur seperti biasa.

> Catatan: database lokal yang sudah ada tidak perlu di-import ulang hanya untuk memakai perubahan PHP/CSS/security pada paket ini.

## SETELAH SUDAH SIAP ONLINE

1. Beli hosting.
2. Beli domain (bisa sekaligus dari provider yang sama atau terpisah).
3. Buat database hosting.
4. Import SQL.
5. Upload website.
6. Edit `config/database.php`.
7. Edit `config/config.php`:
   - `APP_ENV = production`
   - `BASE_URL` sesuai posisi domain.
8. Aktifkan HTTPS.
9. Tes semua fitur.
10. Website resmi dibuka dari domain.

---

# KETIKA NANTI INGIN UPDATE WEBSITE

Website online **tidak perlu dibuat ulang dari nol**.

Alurnya:

```text
Edit / perbaiki website di localhost
          ↓
Tes sampai berhasil
          ↓
Backup website online + database
          ↓
Upload file yang berubah ke hosting
          ↓
Tes kembali website online
```

Untuk perubahan CSS/HTML/PHP sederhana biasanya cukup upload file yang berubah.

Jika perubahan database diperlukan, buat backup database terlebih dahulu dan jalankan migration/SQL yang sesuai.

**Jangan menimpa `config/database.php` production dengan konfigurasi localhost secara sembarangan.**

---

# FILE PENTING

```text
config/config.php          → BASE_URL + APP_ENV
config/database.php        → koneksi MySQL
config/auth.php            → keamanan session + CSRF Admin
admin/login.php            → login Admin
admin/ubah-password.php    → ganti password Admin
admin/test-admin.php       → SUDAH DIHAPUS

database/rosaindahprinting.sql → struktur/data awal database

tools/backup-database.php  → backup database via CLI
backups/                   → lokasi backup lokal, jangan dipublish
```

---

# CATATAN PENTING

### Jangan mengubah ini sekarang jika masih localhost

```php
define('APP_ENV', 'local');
define('BASE_URL', '/rosaindahprinting');
```

Biarkan seperti itu selama website masih di:

`http://localhost/rosaindahprinting`

Saat pindah ke domain root, baru sesuaikan `BASE_URL` dan `APP_ENV`.

### Password Admin

Paket ini **tidak lagi menampilkan password default di halaman login**.
Gunakan password Admin yang sudah Anda miliki untuk login lokal, lalu ganti melalui:

`Admin → Ubah Password`

Sebelum website benar-benar online, pastikan password lama sudah diganti.

---

# STATUS TAHAP 20

- 20A Security Hardening → **SUDAH DISIAPKAN**
- 20B Database Production → **SIAP DIATUR SAAT HOSTING**
- 20C Deployment Hosting → **DILAKUKAN SETELAH HOSTING + DOMAIN SIAP**
- 20D Final Testing → **DILAKUKAN SETELAH DOMAIN ONLINE**

Jadi Anda tidak perlu mengerjakan 20A–20D sekaligus.

**Untuk sekarang cukup replace file → tes localhost → ganti password Admin.**

Setelah itu baru kita lanjut ke pemilihan hosting/domain dan deployment.
