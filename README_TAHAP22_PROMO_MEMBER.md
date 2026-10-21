# TAHAP 22 — PROMO, VOUCHER & DISKON MEMBER

Fitur: promo otomatis, voucher kode, diskon persen/nominal, minimum belanja, maksimum diskon, target semua produk/kategori/produk tertentu, masa berlaku, kuota, status aktif/nonaktif, dan opsi khusus Member.

## Halaman
- `promo.php` — katalog promo/voucher yang sedang aktif.
- `admin/promo.php` — Seller/Admin membuat dan menghapus promo/voucher.
- `includes/promotions.php` — engine validasi dan perhitungan diskon.
- `checkout.php` — input voucher dan penerapan diskon pada total pesanan.

## Aturan
- Guest tetap dapat checkout.
- Promo/voucher yang ditandai `Khusus Member` hanya berlaku setelah login Member.
- Jika customer tidak memasukkan voucher, sistem memilih promo otomatis dengan diskon terbesar yang memenuhi syarat.
- Jika voucher valid digunakan, voucher diprioritaskan dan promo otomatis tidak ditumpuk.
- Diskon dicatat pada pesanan agar histori harga tidak berubah ketika promo berikutnya diedit.
- Kuota dikurangi saat pesanan berhasil dibuat.
