<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

$pdo = db();
$categories = $pdo->query(
    'SELECT c.id, c.name, c.description, COUNT(p.id) AS total_products
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.description
     ORDER BY c.name ASC'
)->fetchAll();

$pageTitle  = 'Kategori';
$activeMenu = 'categories';
require BASE_PATH . '/views/header.php';
?>
<div class="page-header">
    <p class="muted">Kelola kategori produk.</p>
    <a class="btn btn-primary" href="<?= e(url('categories/form.php')) ?>">+ Tambah Kategori</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Deskripsi</th><th class="num">Jumlah Produk</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="4" class="muted">Belum ada kategori.</td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td class="wrap"><?= e($c['description']) ?></td>
                    <td class="num"><?= e(format_number($c['total_products'])) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('categories/form.php?id=' . (int) $c['id'])) ?>">Edit</a>
                            <form method="post" action="<?= e(url('categories/delete.php')) ?>"
                                  data-confirm="Hapus kategori ini?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
