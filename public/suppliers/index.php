<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

$pdo = db();
$suppliers = $pdo->query(
    'SELECT s.id, s.name, s.phone, s.email, s.address, COUNT(ps.product_id) AS total_products
     FROM suppliers s
     LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
     GROUP BY s.id, s.name, s.phone, s.email, s.address
     ORDER BY s.name ASC'
)->fetchAll();

$pageTitle  = 'Supplier';
$activeMenu = 'suppliers';
require BASE_PATH . '/views/header.php';
?>
<div class="page-header">
    <p class="muted">Kelola supplier. Relasi produk-supplier diatur pada form produk.</p>
    <a class="btn btn-primary" href="<?= e(url('suppliers/form.php')) ?>">+ Tambah Supplier</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nama</th><th>Telepon</th><th>Email</th><th>Alamat</th><th class="num">Produk</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (!$suppliers): ?>
                <tr><td colspan="6" class="muted">Belum ada supplier.</td></tr>
            <?php endif; ?>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['phone']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td class="wrap"><?= e($s['address']) ?></td>
                    <td class="num"><?= e(format_number($s['total_products'])) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('suppliers/form.php?id=' . (int) $s['id'])) ?>">Edit</a>
                            <form method="post" action="<?= e(url('suppliers/delete.php')) ?>"
                                  data-confirm="Hapus supplier ini? Relasinya dengan produk ikut dihapus.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
