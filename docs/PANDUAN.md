# Panduan Menjalankan Inventory Management System (XAMPP)

> Catatan: kode ini ditulis tanpa bisa dijalankan langsung di lingkungan pembuatnya (tidak ada PHP/MySQL di sana).
> Karena itu **jalankan pengujian di `PENGUJIAN.md` pada komputer Anda** sebelum mengumpulkan, dan perbaiki bila ada error.

## 1. Instal dan jalankan XAMPP
1. Unduh XAMPP (PHP 8.0 atau lebih baru disarankan) dari apachefriends.org dan instal.
2. Buka **XAMPP Control Panel**, klik **Start** pada **Apache** dan **MySQL**. Keduanya harus berwarna hijau.

## 2. Taruh proyek di htdocs
Salin folder **`inventory-app`** ke `C:\xampp\htdocs\` sehingga ada `C:\xampp\htdocs\inventory-app\public\login.php`.

## 3. Buat database dan impor schema
1. Buka `http://localhost/phpmyadmin`.
2. Tab **Import** > pilih `inventory-app/database/schema.sql` > **Import**.
   (File ini otomatis membuat database `inventory_db`. Jika Anda lebih suka membuatnya manual: **New** > nama `inventory_db`, collation `utf8mb4_unicode_ci`, lalu pilih database itu dan Import.)
3. Pilih database `inventory_db` > **Import** > pilih `database/seed.sql` (kategori, supplier, produk contoh).

## 4. Sesuaikan konfigurasi
- `config/database.php`: default XAMPP sudah sesuai (`root`, password kosong). Jika MySQL Anda berpassword, salin `config/database.local.example.php` menjadi `config/database.local.php` dan isi di sana (file itu tidak ikut Git).
- `config/app.php`: `BASE_URL` harus sesuai nama folder. Jika folder bernama `inventory-app`, biarkan `/inventory-app/public`.

## 5. Buat akun admin dan staff (password di-hash)
Buka **Command Prompt**, lalu:

```
cd C:\xampp\htdocs\inventory-app
C:\xampp\php\php.exe database\create_user.php admin <PASSWORD_ADMIN> admin "Administrator"
C:\xampp\php\php.exe database\create_user.php staff1 <PASSWORD_STAFF> staff "Staff Gudang"
```

- Script menyimpan `password_hash()` ke database, bukan teks biasa.
- Ganti `<PASSWORD_ADMIN>` dan `<PASSWORD_STAFF>` dengan password pilihan Anda (minimal 8 karakter). Jangan menuliskan password asli ke file yang di-commit.
- Password yang diketik di command line bisa tersimpan di riwayat terminal; untuk demo lokal ini dapat diterima.
- Script hanya berjalan lewat CLI; jika dibuka dari browser akan ditolak.

## 6. Jalankan aplikasi
Buka `http://localhost/inventory-app/public/login.php`, login dengan `admin` / `<PASSWORD_ADMIN>` (atau staff).

## 7. Uji fitur
Ikuti `PENGUJIAN.md`.

## 8. Error umum
| Gejala | Penyebab | Solusi |
|---|---|---|
| "Tidak dapat terhubung ke database" | MySQL belum jalan / kredensial salah | Start MySQL di XAMPP, cek `config/database.php` |
| `could not find driver` | Ekstensi `pdo_mysql` mati | Di `C:\xampp\php\php.ini` pastikan `extension=pdo_mysql` tidak diberi `;`, restart Apache |
| Halaman 404 / CSS tidak muncul | `BASE_URL` salah | Samakan `BASE_URL` di `config/app.php` dengan lokasi folder `public` |
| Import schema error pada `CHECK` | MySQL/MariaDB terlalu lama | Perbarui XAMPP, atau hapus baris `CONSTRAINT chk_...` (validasi tetap ada di PHP) |
| Import error `#1451/#1452` | Urutan impor salah | Impor `schema.sql` dulu, baru `seed.sql` |
| Login selalu gagal | Akun belum dibuat / salah ketik | Ulangi langkah 5 |
| Halaman putih | Error PHP | Cek `C:\xampp\apache\logs\error.log` |
| Folder `config`, `helpers`, dst. "Forbidden" di browser | Memang disengaja (`.htaccess`) | Akses aplikasi hanya lewat `/public/` |
