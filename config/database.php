<?php
/**
 * config/database.php
 * Koneksi database menggunakan PDO.
 * Default sesuai XAMPP. Jika MySQL Anda memakai password, buat config/database.local.php (lihat file .example).
 */
// Kredensial lokal (tidak ikut Git) boleh ditaruh di config/database.local.php.
// Salin dari config/database.local.example.php. Jika file itu tidak ada, dipakai default XAMPP di bawah.
if (is_file(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
}
defined('DB_HOST')    || define('DB_HOST', '127.0.0.1');
defined('DB_NAME')    || define('DB_NAME', 'inventory_db');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');      // default XAMPP: tanpa password
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // prepared statement asli
            ]);
        } catch (PDOException $e) {
            error_log('Koneksi database gagal: ' . $e->getMessage());
            http_response_code(500);
            exit('Tidak dapat terhubung ke database. Periksa MySQL di XAMPP dan file config/database.php.');
        }
    }

    return $pdo;
}
