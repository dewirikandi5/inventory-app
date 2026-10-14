<?php
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    logout_user();
    start_secure_session();            // sesi baru agar notifikasi bisa ditampilkan
    flash_set('info', 'Anda telah keluar.');
    redirect('login.php');
}
redirect('dashboard.php');
