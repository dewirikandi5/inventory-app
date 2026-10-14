<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users/index.php');
}
csrf_verify();

$pdo = db();
$id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    flash_set('danger', 'ID pengguna tidak valid.');
    redirect('users/index.php');
}
if ($id === (int) current_user()['id']) {
    flash_set('danger', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
    redirect('users/index.php');
}

$count = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE user_id = :id');
$count->execute(['id' => $id]);
if ((int) $count->fetchColumn() > 0) {
    flash_set('danger', 'Pengguna sudah memiliki histori transaksi dan tidak dapat dihapus. Nonaktifkan akunnya melalui menu Edit.');
    redirect('users/index.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');   // user_profiles ikut terhapus (CASCADE)
    $stmt->execute(['id' => $id]);
    flash_set($stmt->rowCount() > 0 ? 'success' : 'warning',
              $stmt->rowCount() > 0 ? 'Pengguna berhasil dihapus.' : 'Pengguna tidak ditemukan.');
} catch (PDOException $e) {
    error_log('Hapus pengguna gagal: ' . $e->getMessage());
    flash_set('danger', 'Pengguna tidak dapat dihapus.');
}
redirect('users/index.php');
