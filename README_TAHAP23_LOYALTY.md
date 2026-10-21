# TAHAP 23 — Poin Member / Loyalty & Reward

## Fitur
- Poin otomatis diberikan **satu kali** saat pesanan Member berubah menjadi `completed` / **Selesai**.
- Default: setiap Rp10.000 nilai pesanan = 1 poin.
- Admin dapat mengubah aturan perolehan poin dan minimum belanja.
- Admin dapat membuat reward: voucher nominal atau persentase, minimum belanja, maksimum diskon, masa berlaku, kuota, status.
- Member dapat melihat saldo dan riwayat poin di `poin-member.php`.
- Member dapat menukar poin menjadi voucher reward unik.
- Voucher reward terikat pada Member yang menukarkannya dan tidak dapat dipakai Member lain.
- Voucher reward otomatis hanya 1 kali penggunaan dan memiliki masa berlaku.
- Admin dapat memberi/mengurangi poin secara manual dengan catatan.

## Cara tes
1. Login Admin.
2. Buka **Poin & Reward**.
3. Atur contoh `Rp10.000 = 1 poin`.
4. Buat reward: `Voucher Rp10.000`, biaya `100 poin`, minimum `Rp0`, berlaku `30 hari`, aktif.
5. Login Member dan buka **Akun Member → Poin & Reward**.
6. Selesaikan satu pesanan Member melalui Admin. Saat status berubah menjadi **Selesai**, poin otomatis masuk.
7. Jika saldo mencukupi, klik **Tukar Reward**. Sistem membuat kode voucher unik.
8. Gunakan kode tersebut saat checkout Member yang sama.
9. Coba gunakan kode itu dari akun Member lain: harus ditolak.
10. Ubah status pesanan yang sama dari Selesai ke status lain lalu kembali Selesai: poin tidak boleh bertambah dua kali.

## Catatan
- Customer/guest tetap dapat checkout seperti sebelumnya.
- Fitur Tahap 22 Promo & Voucher tetap dipertahankan.
- Database lama tidak perlu di-import ulang karena tabel/migrasi dibuat otomatis saat halaman loyalty dijalankan.
