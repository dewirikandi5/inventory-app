# Inventory Management System – Toko Handphone

Mini Project 3, mata kuliah **Pemrograman Web 4** (Relational Database + Advanced PHP).
Aplikasi web untuk mengelola persediaan toko handphone: data master, transaksi barang masuk/keluar, riwayat stok, dashboard, dan laporan, dengan akses berbasis role (admin dan staff).

**Teknologi:** PHP (PDO + prepared statement), MySQL/MariaDB, HTML, CSS, JavaScript. Berjalan lokal dengan XAMPP.

## Fitur
- Login/logout, password di-hash (`password_hash()` / `password_verify()`), session dengan `session_regenerate_id(true)`.
- Role **admin** (kelola pengguna, kategori, produk, supplier, transaksi) dan **staff** (lihat produk, catat transaksi). Otorisasi diperiksa di server.
- CRUD kategori, produk, supplier, dan pengguna; relasi many-to-many produk–supplier; SKU unik.
- Barang masuk/keluar dengan **database transaction** (`commit()` / `rollBack()`); stok tidak boleh negatif dan histori selalu konsisten.
- Daftar produk: pencarian (nama/SKU), filter kategori, sorting (allowlist), ASC/DESC, pagination yang mempertahankan parameter.
- Dashboard (total produk, nilai inventory, stok rendah, total masuk/keluar) dan laporan inventory (JOIN + COUNT/SUM/AVG/GROUP BY).
- Keamanan: validasi input server-side, `htmlspecialchars()` untuk output, token CSRF, folder non-publik diblokir `.htaccess`.
- Tampilan responsif (sidebar, kartu statistik, indikator stok rendah).

## Kebutuhan sistem
- XAMPP dengan PHP 8.0+ dan MySQL 8 / MariaDB 10.4+ (ekstensi `pdo_mysql` aktif)
- Browser modern

## Instalasi (XAMPP)
1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin/clone folder proyek ke `C:\xampp\htdocs\inventory-app` (nama folder harus sama dengan `BASE_URL` di `config/app.php`).
3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/schema.sql`, lalu **Import** `database/seed.sql` (data contoh toko handphone).
4. Konfigurasi database: default XAMPP (`root`, tanpa password) langsung bekerja. Jika MySQL Anda berpassword, salin `config/database.local.example.php` menjadi `config/database.local.php` dan isi kredensialnya (file ini diabaikan Git).
5. Buat akun awal lewat Command Prompt (password di-hash, tidak disimpan di repository):
   ```
   cd C:\xampp\htdocs\inventory-app
   C:\xampp\php\php.exe database\create_user.php admin <PASSWORD_ADMIN> admin "Administrator"
   C:\xampp\php\php.exe database\create_user.php staff1 <PASSWORD_STAFF> staff "Staff Gudang"
   ```
   Ganti `<PASSWORD_...>` dengan password Anda sendiri (minimal 8 karakter).
6. Buka `http://localhost/inventory-app/public/login.php`.

Panduan lengkap dan penanganan error: [`docs/PANDUAN.md`](docs/PANDUAN.md). Skenario pengujian: [`docs/PENGUJIAN.md`](docs/PENGUJIAN.md). Bahan laporan: [`docs/LAPORAN.md`](docs/LAPORAN.md). ERD: [`docs/ERD.mermaid`](docs/ERD.mermaid).

## Struktur proyek
```
inventory-app/
├── bootstrap.php
├── config/        app.php, database.php, database.local.example.php
├── helpers/       auth, escape, flash, validation, pagination, product, stock
├── views/         header, footer, 403, stock_transaction
├── database/      schema.sql, seed.sql, create_user.php
├── docs/          PANDUAN, PENGUJIAN, LAPORAN, ERD
└── public/        login, logout, dashboard, categories/, products/,
                   suppliers/, users/, stock/, assets/
```

## Catatan
- Data contoh (merek, model, harga) fiktif.
- Folder selain `public/` diblokir lewat `.htaccess`; akses aplikasi hanya melalui `/public/`.
- Status pengujian: ikuti `docs/PENGUJIAN.md` pada komputer Anda dan isi hasilnya sendiri.
