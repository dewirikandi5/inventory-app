<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');                 // staff mendapat 403

$pdo   = db();
$users = $pdo->query(
    'SELECT u.id, u.username, u.role, u.is_active, u.created_at,
            up.full_name, up.phone, up.email
     FROM users u
     LEFT JOIN user_profiles up ON up.user_id = u.id
     ORDER BY u.username ASC'
)->fetchAll();
$selfId = current_user()['id'];

$pageTitle  = 'Pengguna';
$activeMenu = 'users';
require BASE_PATH . '/views/header.php';
?>
<div class="page-header">
    <p class="muted">Kelola akun admin dan staff.</p>
    <a class="btn btn-primary" href="<?= e(url('users/form.php')) ?>">+ Tambah Pengguna</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Status</th><th>Telepon</th><th>Email</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-info' : 'badge-muted' ?>"><?= e(strtoupper($u['role'])) ?></span></td>
                    <td><span class="badge <?= (int) $u['is_active'] === 1 ? 'badge-success' : 'badge-danger' ?>"><?= (int) $u['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td><?= e($u['phone']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-outline btn-sm" href="<?= e(url('users/form.php?id=' . (int) $u['id'])) ?>">Edit</a>
                            <?php if ((int) $u['id'] !== (int) $selfId): ?>
                                <form method="post" action="<?= e(url('users/delete.php')) ?>"
                                      data-confirm="Hapus pengguna ini? Pengguna yang sudah memiliki histori transaksi tidak dapat dihapus (nonaktifkan saja).">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
