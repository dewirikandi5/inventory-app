<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products/index.php');
}
csrf_verify();

$pdo = db();
$id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    flash_set('danger', 'ID produk tidak valid.');
    redirect('products/index.php');
}

// Business rule: produk yang memiliki histori transaksi tidak boleh dihapus.
$count = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id = :id');
$count->execute(['id' => $id]);
if ((int) $count->fetchColumn() > 0) {
    flash_set('danger', 'Produk tidak dapat dihapus karena sudah memiliki histori transaksi stok.');
    redirect('products/index.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');   // product_suppliers ikut terhapus (CASCADE)
    $stmt->execute(['id' => $id]);
    flash_set($stmt->rowCount() > 0 ? 'success' : 'warning',
              $stmt->rowCount() > 0 ? 'Produk berhasil dihapus.' : 'Produk tidak ditemukan.');
} catch (PDOException $e) {
    error_log('Hapus produk gagal: ' . $e->getMessage());
    flash_set('danger', 'Produk tidak dapat dihapus karena masih dipakai data lain.');
}
redirect('products/index.php');
