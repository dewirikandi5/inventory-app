<?php
/**
 * config/app.php
 * Pengaturan umum aplikasi.
 */
const APP_NAME = 'Inventory Toko Handphone';

// URL dasar folder public. Sesuaikan jika nama folder proyek Anda berbeda.
// Contoh: proyek di C:\xampp\htdocs\inventory-app  =>  '/inventory-app/public'
const BASE_URL = '/inventory-app/public';

const PER_PAGE_DEFAULT = 10;          // jumlah data per halaman (default)
const PER_PAGE_OPTIONS = [5, 10, 25, 50];
const MAX_QUANTITY     = 1000000;     // batas atas jumlah satu transaksi

// HANYA UNTUK PENGUJIAN rollback (lihat docs/PENGUJIAN.md, pengujian 10).
// Jika true, transaksi stok sengaja gagal SETELAH stok diupdate dan SEBELUM histori disimpan.
// Kembalikan ke false setelah selesai menguji.
const SIMULATE_STOCK_FAILURE = false;

date_default_timezone_set('Asia/Jakarta');

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}
