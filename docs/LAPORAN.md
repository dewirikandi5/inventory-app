# Laporan Mini Project 3 – Inventory Management System
Mata kuliah: Pemrograman Web 4 (Relational Database + Advanced PHP)

> Penanda: **[PPT]** = berasal dari materi/instruksi dosen. **[Asumsi]** = keputusan implementasi saya, tidak disebut di PPT.

## 1. Ringkasan instruksi tugas (dari PPT)
- **[PPT]** Fitur wajib: authentication (login, logout, password hashing); authorization admin/staff; master data kategori, produk, supplier; many-to-many produk–supplier; transaksi masuk/keluar + histori; listing dengan search, filter, sorting, pagination; dashboard dan laporan; keamanan (prepared statement, validasi, escaping).
- **[PPT]** Business rules: SKU unik; harga dan quantity tidak negatif; barang keluar tidak melebihi stok; stok tidak negatif; produk berhistori tidak boleh dihapus sembarangan; halaman internal wajib login; staff tidak boleh mengakses administrasi user; sorting hanya dari kolom yang diizinkan.
- **[PPT]** Deliverable: source code, schema SQL, screenshot fitur, penjelasan business rule. Uji minimal satu validation error dan satu akses terlarang.
- **[PPT]** Rubrik: desain database & relasi 20%, FK & integritas 10%, CRUD master 10%, transaksi 20%, listing 10%, login & authorization 10%, keamanan 15%, kerapian 5%.

## 2. Tujuan aplikasi
Membantu toko handphone mengelola persediaan (handphone, casing, charger, dan aksesori lain) secara terintegrasi: data master, pencatatan barang masuk/keluar, stok yang konsisten, peringatan stok rendah, dan laporan, dengan akses dibatasi menurut role.

## 3. Analisis kebutuhan
**Fungsional:** login/logout; CRUD pengguna (admin), kategori, supplier, produk; relasi produk–supplier; transaksi stok; riwayat; pencarian/filter/sort/pagination; dashboard; laporan.
**Non-fungsional:** keamanan (hash password, prepared statement, escaping, otorisasi di server), integritas data (FK, constraint, transaction), responsif, berjalan di XAMPP.
**Aktor:** Admin (semua fitur), Staff (lihat produk, catat transaksi, lihat riwayat/laporan **[Asumsi: riwayat & laporan boleh dilihat staff]**).

## 4. ERD dan relasi
Lihat `ERD.mermaid` (dapat ditempel ke mermaid.live untuk screenshot) atau gunakan Designer di phpMyAdmin.

| Relasi | Jenis | Implementasi |
|---|---|---|
| users – user_profiles | One-to-one | `user_profiles.user_id` FK + UNIQUE |
| categories – products | One-to-many | `products.category_id` FK (ON DELETE RESTRICT) |
| products – suppliers | Many-to-many | Tabel `product_suppliers`, PK gabungan (product_id, supplier_id) |
| products – stock_movements | One-to-many | `stock_movements.product_id` FK (RESTRICT) |
| users – stock_movements | One-to-many | `stock_movements.user_id` FK (RESTRICT) |

Pilihan aturan FK: **RESTRICT** untuk histori (sesuai saran PPT); **CASCADE** untuk `user_profiles` dan `product_suppliers` karena itu data pendamping/penghubung, bukan histori **[Asumsi]**.

## 5. Fungsi setiap tabel
- `users`: akun login, `password_hash`, role, status aktif.
- `user_profiles`: data diri pengguna (nama, telepon, email).
- `categories`: pengelompokan produk.
- `products`: SKU unik, nama, harga, stok saat ini, minimum stok.
- `suppliers`: data pemasok.
- `product_suppliers`: junction produk–supplier, menyimpan harga dari supplier.
- `stock_movements`: histori IN/OUT: produk, jumlah, pelaku, waktu, catatan.
Constraint tambahan: `CHECK` (harga ≥ 0, stok ≥ 0, quantity > 0), UNIQUE pada `sku`, `username`, `categories.name`.

## 6. Fitur aplikasi
Login/logout dan sesi; dashboard (total produk, nilai inventory, stok rendah, total masuk/keluar, ringkasan kategori, transaksi terbaru); CRUD kategori/supplier/produk/pengguna; form produk dengan pilihan banyak supplier; listing produk (cari nama/SKU, filter kategori, sort nama/harga/stok ASC/DESC, per halaman, pagination); barang masuk/keluar; riwayat; laporan inventory.

## 7. Business rule dan lokasi penerapannya
| Rule | Penerapan |
|---|---|
| SKU unik | UNIQUE di DB + `sku_exists()` di PHP |
| Harga/quantity tidak negatif | `valid_price()`, validasi quantity `> 0`, CHECK di DB |
| Keluar ≤ stok, stok tidak negatif | `record_stock_movement()` memeriksa setelah `SELECT ... FOR UPDATE`; CHECK `stock >= 0` sebagai pengaman kedua |
| Produk berhistori tidak dihapus | `products/delete.php` memeriksa histori + FK RESTRICT |
| Halaman internal wajib login | `require_login()` |
| Staff tanpa akses admin | `require_role('admin')` (HTTP 403) |
| Sort hanya kolom diizinkan | allowlist `$sortColumns` |
| Stok produk hanya berubah lewat transaksi | Form edit produk menampilkan stok read-only; UPDATE produk tidak menyentuh kolom stok **[Asumsi]** |

## 8. Keamanan
- PDO + prepared statement (`EMULATE_PREPARES=false`); nama kolom sorting dari allowlist.
- Validasi server-side (tipe, format, panjang) dan semantik (stok cukup, produk/kategori ada).
- `password_hash()` / `password_verify()`; `session_regenerate_id(true)` setelah login.
- `htmlspecialchars()` melalui fungsi `e()` pada semua output.
- Otorisasi di server pada setiap halaman dan proses (bukan hanya menyembunyikan menu); status akun dan role dicek ulang ke DB tiap request.
- **[Asumsi, di luar PPT]** token CSRF pada semua form POST, cookie sesi HttpOnly + SameSite, `.htaccess` memblokir folder non-publik, logout via POST.

## 9. Cara kerja transaksi barang masuk dan keluar
Fungsi `record_stock_movement()` (`helpers/stock.php`):
1. `beginTransaction()`
2. `SELECT name, stock FROM products WHERE id = :id FOR UPDATE` (mengunci baris agar dua transaksi bersamaan tidak saling menimpa)
3. Validasi: produk ada; untuk OUT, jumlah ≤ stok
4. `UPDATE products SET stock = stock ± :qty`
5. `INSERT INTO stock_movements` (produk, user, jenis, jumlah, catatan)
6. `commit()`; bila ada kegagalan di langkah mana pun: `rollBack()` sehingga stok dan histori tidak pernah berbeda.

Hasil akhir: stok akhir = stok awal + total masuk − total keluar.

## 10. Query laporan
- Laporan produk (`stock/report.php`): `products JOIN categories LEFT JOIN stock_movements`, `SUM(CASE WHEN type='IN' ...)`, `SUM(CASE WHEN type='OUT' ...)`, `GROUP BY` — LEFT JOIN agar produk tanpa transaksi tetap tampil dengan 0.
- Ringkasan kategori: `COUNT`, `SUM`, `AVG`, `GROUP BY` (+ `HAVING COUNT(p.id) >= 1` pada halaman laporan).
- Dashboard: `COUNT(products.id)`, `SUM(stock*price)`, `WHERE stock <= minimum_stock`, `SUM(quantity)` per jenis.

## 11. Hasil pengujian
Isi dari tabel `PENGUJIAN.md` setelah Anda menjalankan sendiri. **Jangan menulis "lulus" untuk pengujian yang belum dijalankan.**

## 12. Kesimpulan (draf, sesuaikan dengan hasil uji Anda)
Aplikasi menerapkan relasi one-to-one, one-to-many, dan many-to-many dengan foreign key; transaksi stok memakai database transaction sehingga stok dan histori konsisten; akses dibatasi role di server; dan input/output diamankan dengan prepared statement, validasi, dan escaping. Pengembangan lanjutan: ekspor laporan, filter tanggal pada riwayat, pembatasan percobaan login.

## 13. Daftar screenshot
1. ERD (mermaid atau Designer phpMyAdmin)
2. phpMyAdmin: daftar 7 tabel dan struktur `products` (index/FK)
3. Halaman login + login gagal
4. Dashboard (kartu statistik, stok rendah)
5. Daftar produk dengan search, filter, sort, pagination (tunjukkan URL berparameter)
6. Form produk dengan pilihan supplier + error SKU duplikat
7. Kategori dan supplier (daftar + form)
8. Barang masuk sukses; barang keluar gagal (stok tidak cukup)
9. Riwayat stok
10. Laporan inventory
11. Halaman 403 saat staff membuka `users/index.php`
12. Tampilan login staff (menu terbatas)
13. Pengujian rollback (pesan gagal + stok tetap)
14. Tampilan HP (mode responsif lewat DevTools)
