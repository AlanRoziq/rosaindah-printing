# Rosa Indah Printing — Tahap 19

## Security Audit + Backup + Persiapan Hosting

### Yang diperkuat
- Security headers: nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy.
- HSTS otomatis hanya saat HTTPS aktif.
- Session cookie: HttpOnly + SameSite=Lax + Secure saat HTTPS.
- Akses web ke `config/`, `database/`, `tools/`, `backups/` diblokir.
- Directory listing dimatikan.
- File konfigurasi, dump SQL, arsip, log, dan file tersembunyi diblokir.
- Folder upload desain dan produk menolak eksekusi script.
- Debug output pada login admin dihapus.
- Backup database tersedia melalui CLI.

## Backup database lokal

Pastikan XAMPP/MySQL aktif, lalu dari Command Prompt pada folder project jalankan:

```
php tools/backup-database.php
```

File backup masuk ke folder `backups/`. Folder tersebut tidak dapat diakses lewat browser.

Jika `mysqldump` tidak ditemukan, jalankan dari folder `C:\xampp\mysql\bin` atau set environment `MYSQLDUMP_BIN`.

## Persiapan hosting

1. Buat database MySQL baru di hosting.
2. Import `database/rosaindahprinting.sql` melalui phpMyAdmin/CLI.
3. Ubah `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` pada `config/database.php`.
4. Ubah `BASE_URL` pada `config/config.php` sesuai domain/path. Untuk domain root biasanya `''`.
5. Ubah `APP_ENV` menjadi `production`.
6. Aktifkan HTTPS/SSL. HSTS akan aktif otomatis ketika HTTPS digunakan.
7. Jangan upload folder `backups/` berisi dump database ke public web.
8. Setelah deployment, hapus file ZIP backup dan file diagnostik/installer yang tidak diperlukan.
9. Pastikan PHP extension PDO MySQL, Fileinfo, Mbstring (jika digunakan) tersedia.
10. Uji login admin, produk, checkout, upload desain, pembayaran, tracking, invoice, laporan, dan export CSV.

## Checklist sebelum go-live
- [ ] HTTPS aktif
- [ ] Password admin sudah diganti dari password awal
- [ ] DB user hosting bukan user root
- [ ] `APP_ENV=production`
- [ ] `BASE_URL` sesuai domain
- [ ] Upload desain berhasil
- [ ] Upload produk admin berhasil
- [ ] Backup database pertama tersimpan di lokasi privat
- [ ] Restore backup pernah diuji
- [ ] WhatsApp Admin sudah benar
- [ ] Invoice dan tracking berhasil
- [ ] Tidak ada file debug/installer/reset password di public folder

---
MOBILE REVISION FINAL (Android / iPhone)
- Topbar email: rosaindahprinting@gmail.com
- Alamat: regek-sambirejo-rejoso
- Urutan navbar mobile: Brand → Search → Cart → Login → Menu
- Header sticky saat scroll khusus mobile
- Kategori mobile tetap 2 kolom dengan gambar landscape
- Produk & katalog mobile 2 kolom dengan gambar persegi
- Hero mobile dikembalikan ke skala font compact
- Font mobile diperkecil tanpa mengubah desktop
