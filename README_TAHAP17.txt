TAHAP 17 — PENYEMPURNAAN SISTEM PELANGGAN (DASAR MEMBER)
Rosa Indah Printing

FITUR:
1. Daftar akun Member dengan nama, WhatsApp, email opsional, alamat, dan password.
2. Login Member menggunakan nomor WhatsApp + password.
3. Halaman akun Member: profil, statistik, riwayat pesanan, status pembayaran.
4. Edit profil pelanggan.
5. Pesanan lama dengan nomor WhatsApp yang sama otomatis dikaitkan ke akun.
6. Checkout otomatis mengisi nama/WhatsApp/alamat pelanggan yang sedang login.
7. Pesanan baru dari akun otomatis menyimpan customer_id.
8. Navbar menampilkan tombol Akun pelanggan ketika sudah login.
9. Logout pelanggan.
10. Lacak Pesanan tetap dapat digunakan tanpa login.

INSTALASI:
- Backup folder website lama.
- Extract isi ZIP dan replace folder rosaindahprinting dengan versi ini.
- Tidak perlu import SQL ulang untuk website yang sudah berjalan. Saat halaman pelanggan/checkout dibuka, tabel customers dan kolom orders.customer_id dibuat otomatis jika belum ada.
- Jika instalasi dari nol, database/rosaindahprinting.sql sudah berisi tabel customers dan customer_id.

PENGUJIAN:
1. Buka /rosaindahprinting/daftar.php dan buat akun.
2. Pastikan diarahkan ke /akun.php.
3. Edit profil dan simpan.
4. Tambahkan produk ke keranjang lalu checkout; data profil harus otomatis terisi.
5. Selesaikan pesanan dan pastikan riwayat muncul di Akun Saya.
6. Klik Tracking, Nota, dan Bayar dari riwayat.
7. Logout lalu pastikan navbar kembali menampilkan Login / Daftar.
8. Login lagi dan pastikan riwayat tetap ada.

CATATAN KEAMANAN:
- Password menggunakan password_hash/password_verify.
- Form login, daftar, dan edit profil menggunakan CSRF token.
- Customer tidak dapat melihat order hanya dengan login; halaman invoice/tracking tetap melakukan verifikasi yang sesuai.
