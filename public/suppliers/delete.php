<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('suppliers/index.php');
}
csrf_verify();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    flash_set('danger', 'ID supplier tidak valid.');
    redirect('suppliers/index.php');
}

try {
    // Baris product_suppliers ikut terhapus (ON DELETE CASCADE); data produk tetap aman.
    $stmt = db()->prepare('DELETE FROM suppliers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    flash_set($stmt->rowCount() > 0 ? 'success' : 'warning',
              $stmt->rowCount() > 0 ? 'Supplier berhasil dihapus.' : 'Supplier tidak ditemukan.');
} catch (PDOException $e) {
    error_log('Hapus supplier gagal: ' . $e->getMessage());
    flash_set('danger', 'Supplier tidak dapat dihapus.');
}
redirect('suppliers/index.php');
