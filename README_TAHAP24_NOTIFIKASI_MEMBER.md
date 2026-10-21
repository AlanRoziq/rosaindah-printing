# TAHAP 24 — Notifikasi Promo & Reward Member

Fitur yang ditambahkan:
- Pusat notifikasi Member di `notifikasi-member.php`.
- Badge jumlah notifikasi belum dibaca pada navbar Member.
- Tombol notifikasi pada halaman Akun Member.
- Tandai satu notifikasi sebagai dibaca atau tandai semua.
- Admin dapat mengirim notifikasi ke semua Member atau satu Member melalui `admin/notifikasi.php`.
- Promo baru aktif -> notifikasi otomatis.
- Voucher baru aktif -> notifikasi otomatis.
- Reward baru aktif -> notifikasi otomatis.
- Bonus/penyesuaian poin -> notifikasi ke Member terkait.
- Poin transaksi selesai -> notifikasi ke Member terkait.
- Penukaran reward -> notifikasi berisi kode voucher reward.

## Catatan
Notifikasi Tahap 24 adalah **notifikasi di dalam website** (in-app). Pengiriman WhatsApp tetap menggunakan integrasi Fonnte yang sudah dibuat pada Tahap 21 untuk OTP. Tahap 24 tidak mengirim broadcast WhatsApp otomatis agar tidak menimbulkan spam/biaya provider.

Database: tabel `member_notifications` dibuat otomatis saat fitur pertama kali dipakai.
