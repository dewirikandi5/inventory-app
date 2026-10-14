# Rencana Pengujian

> Belum ada pengujian yang dijalankan oleh pembuat kode. Kolom "Hasil Anda" diisi sendiri setelah mencoba pada aplikasi yang berjalan.

Akun: `admin` / `<PASSWORD_ADMIN>`, `staff1` / `<PASSWORD_STAFF>` (lihat PANDUAN langkah 5). Data awal dari `seed.sql` (toko handphone).

| No | Pengujian | Langkah | Hasil yang diharapkan | Hasil Anda |
|---|---|---|---|---|
| 1a | Login valid | Login `admin` + password benar | Masuk ke Dashboard, notifikasi sukses | |
| 1b | Login tidak valid | Password salah / username tidak ada | Tetap di login, pesan "Username atau password salah." | |
| 2a | Hak akses staff | Login staff1 | Menu hanya: Dashboard, Produk, Masuk, Keluar, Riwayat, Laporan. Di Produk tidak ada tombol Tambah/Edit/Hapus | |
| 2b | Hak akses admin | Login admin | Menu lengkap + Kategori, Supplier, Pengguna | |
| 3 | CRUD master data | Admin: tambah, ubah, hapus kategori; tambah, ubah, hapus supplier; tambah produk dengan 2 supplier lalu edit | Data tersimpan/berubah/terhapus, notifikasi sukses; relasi supplier tampil di kolom Supplier | |
| 3b | Hapus yang dibatasi | Hapus kategori "Smartphone" (punya produk) | Ditolak: "masih memiliki produk" | |
| 4 | SKU duplikat | Tambah produk dengan SKU `HP-NVX5-256` (atau `hp-nvx5-256`) | Ditolak: "SKU sudah digunakan" | |
| 5 | Angka negatif | Harga `-100`; stok awal `-5`; transaksi jumlah `-3` atau `0` | Ditolak dengan pesan validasi. (Uji server-side juga: ubah `min="0"` lewat DevTools lalu kirim) | |
| 6 | Barang keluar > stok | Produk stok 2 (`HP-ZNP10`), keluar 10 | Ditolak "Stok tidak cukup", stok tetap 2, tidak ada baris baru di Riwayat | |
| 6b | Barang keluar sama dengan stok | Keluar 2 pada `HP-ZNP10` | Berhasil, stok jadi 0, status "Habis" | |
| 6c | Barang masuk | Masuk 20 pada `TG-NVX5` (stok 8) | Stok jadi 28, tercatat di Riwayat dengan nama user | |
| 7 | Stok rendah | Lihat Dashboard | Daftar stok rendah memuat `HP-ZNP10`, `HP-ZNL7`, `TG-NVX5`, `CBL-MICRO` (sesuai seed); angka berubah setelah transaksi | |
| 8 | Search/filter/sort/pagination | Produk: cari `nova`; cari `TG-`; filter kategori; sort harga DESC; per halaman 5 lalu pindah halaman | Hasil sesuai; parameter tetap terbawa di link halaman berikutnya. Coba `?sort=price;DROP` di URL: jatuh ke sort `name` | |
| 9a | SQL Injection (aman) | Login dengan username `' OR '1'='1` dan password sembarang; cari produk `' OR 1=1 --` | Login gagal; pencarian dianggap teks biasa (hasil kosong) | |
| 9b | XSS (aman) | Admin: buat kategori bernama `<b>tes</b>` atau `<script>alert(1)</script>` | Tampil sebagai teks apa adanya, tidak dieksekusi | |
| 10 | Rollback transaksi | Di `config/app.php` ubah `SIMULATE_STOCK_FAILURE` jadi `true`. Lakukan barang masuk pada suatu produk | Muncul pesan transaksi gagal/dibatalkan; **stok produk tidak berubah** dan **tidak ada** baris histori baru. Kembalikan ke `false` | |
| 11a | Akses terlarang (staff) | Login staff1, buka `/inventory-app/public/users/index.php`, `/categories/index.php`, `/products/form.php` | Halaman "403 - Akses Ditolak" (HTTP 403) | |
| 11b | Tanpa login | Logout, buka `/inventory-app/public/dashboard.php` | Diarahkan ke login | |
| 11c | Proses terlarang | Staff mengirim POST ke `products/delete.php` (mis. via DevTools) | 403, data tidak terhapus | |
| 12 | Nonaktifkan akun | Admin nonaktifkan staff1 saat staff1 sedang login | Request berikutnya staff1 diarahkan ke login | |
| 13 | Produk punya histori | Admin hapus produk yang sudah punya transaksi | Ditolak: memiliki histori | |
