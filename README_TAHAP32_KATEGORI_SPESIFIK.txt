TAHAP 32 — SISTEM KATEGORI PRODUK SPESIFIK

Master kategori produk sekarang sama di seluruh sistem:
1. Undangan Nikah
2. Print Scan Copy
3. MUG
4. Banner
5. Cetak Buku
6. Lanyard & Id Card
7. Spanduk
8. Kartu Nama
9. Sticker
10. Undangan Lipat 3
11. Nota / Buku Tabungan
12. Kalender
13. Sablon DTF
14. Cetak Foto
15. Cetak Pigora
16. Gantungan Kunci

Perubahan:
- Kartu kategori di Home mengarah ke kategori spesifik yang sama persis.
- produk.php menggunakan 16 kategori master yang sama.
- Admin > Produk hanya menyediakan 16 kategori spesifik tersebut.
- Admin > Promo/Voucher menggunakan 16 kategori spesifik tersebut untuk target kategori.
- Navbar > Produk menggunakan 16 kategori spesifik tersebut.
- Produk bawaan yang sebelumnya memakai kategori generik dimigrasikan ke kategori spesifik berdasarkan ID.
- Produk lama yang masih memakai kategori generik yang dikenali akan dicoba dipetakan berdasarkan nama produk.
- Promo lama yang masih menyimpan target kategori generik tetap didukung oleh compatibility mapping agar tidak langsung rusak.
- Database SQL untuk instalasi baru sudah menggunakan kategori spesifik dan memiliki UPDATE migrasi kategori.

Catatan Banner/Spanduk:
Produk bawaan lama bernama "Banner & Spanduk" dipetakan ke kategori "Banner". Kategori "Spanduk" sudah tersedia di Admin > Produk sehingga produk Spanduk dapat dibuat terpisah. Ini diperlukan karena satu produk memiliki satu kategori spesifik.
