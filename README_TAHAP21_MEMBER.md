# TAHAP 21 — SISTEM MEMBER

## Konsep
- Customer biasa tetap bisa melihat katalog, checkout, upload desain, pembayaran, dan tracking tanpa login.
- Customer yang mendaftar + verifikasi WhatsApp + login disebut **Member**.
- Nomor WhatsApp wajib unik dan menjadi identitas login. Nama boleh sama. Email opsional dan tidak menjadi identitas unik.
- Password menggunakan `password_hash/password_verify`.
- OTP terdiri dari 4 digit, disimpan dalam bentuk hash, berlaku 10 menit, maksimal 5 percobaan, dan resend memiliki cooldown 60 detik.

## File utama
- `config/customer-auth.php` — autentikasi Member + OTP.
- `daftar.php` — formulir Daftar Akun Member.
- `verifikasi-member.php` — input kode 4 digit + kirim ulang.
- `login.php` — Login Member menggunakan WhatsApp + password.
- `akun.php` — profil dan riwayat Member.
- `detail-produk.php` — informasi keuntungan Member sebelum checkout.
- `admin/pelanggan.php` — daftar Member dan riwayat pesanan terkait.

## WhatsApp OTP
Pengiriman OTP menggunakan Fonnte melalui cURL. Isi token provider pada `config/config.php`:

```php
define('WHATSAPP_OTP_TOKEN', 'TOKEN_FONNTE_ANDA');
```

Jangan membagikan token dan jangan memasukkannya ke repository publik. Pastikan ekstensi PHP cURL aktif.

**Penting:** source code sudah siap untuk pengiriman OTP, tetapi nomor WhatsApp saja belum cukup untuk mengirim pesan otomatis. Diperlukan akun/provider WhatsApp API dan token yang valid. Tanpa token, pendaftaran tidak mengaktifkan akun Member dan sistem akan menampilkan pesan konfigurasi.

## Database
Website existing tidak perlu import SQL ulang. Saat `customer-auth.php` dijalankan, kolom Member/OTP dibuat otomatis jika belum ada. Email tidak lagi unik karena identitas unik Member hanya nomor WhatsApp. Akun lama dari Tahap 17 otomatis dianggap Member terverifikasi agar tidak terkunci.

Untuk instalasi baru, `database/rosaindahprinting.sql` sudah berisi struktur terbaru.

## Alur pengguna
1. Customer buka `daftar.php`.
2. Isi nama, WhatsApp unik, password, dan data opsional.
3. Klik **Buat Akun Member**.
4. Sistem membuat akun berstatus belum terverifikasi dan mengirim OTP 4 digit ke WhatsApp.
5. Customer memasukkan OTP di `verifikasi-member.php`.
6. Jika benar, akun menjadi Member terverifikasi dan diarahkan ke Login Member.
7. Jika salah, sistem menolak kode; maksimal 5 percobaan.
8. Tombol **Kirim Ulang Kode** tersedia setelah cooldown.
9. Login menggunakan WhatsApp + password.
10. Detail produk menampilkan keuntungan Member.

## Pengujian penting
- Coba nama yang sama pada dua akun: **boleh**.
- Coba nomor WhatsApp yang sama: **ditolak**.
- Coba email yang sama: **boleh**, karena email bukan identitas unik.
- Coba login sebelum verifikasi: diarahkan ke verifikasi Member.
- Coba customer guest checkout: harus tetap bisa.
- Coba tracking tanpa login: harus tetap bisa.
