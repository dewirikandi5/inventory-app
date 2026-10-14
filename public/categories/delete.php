<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('categories/index.php');
}
csrf_verify();

$pdo = db();
$id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    flash_set('danger', 'ID kategori tidak valid.');
    redirect('categories/index.php');
}

$count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
$count->execute(['id' => $id]);
if ((int) $count->fetchColumn() > 0) {
    flash_set('danger', 'Kategori tidak dapat dihapus karena masih memiliki produk.');
    redirect('categories/index.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    flash_set($stmt->rowCount() > 0 ? 'success' : 'warning',
              $stmt->rowCount() > 0 ? 'Kategori berhasil dihapus.' : 'Kategori tidak ditemukan.');
} catch (PDOException $e) {
    error_log('Hapus kategori gagal: ' . $e->getMessage());
    flash_set('danger', 'Kategori tidak dapat dihapus karena masih dipakai data lain.');
}
redirect('categories/index.php');
