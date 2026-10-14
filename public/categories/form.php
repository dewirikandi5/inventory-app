<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

$pdo    = db();
$id     = get_int('id');
$isEdit = $id > 0;
$errors = [];
$data   = ['name' => '', 'description' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('danger', 'Kategori tidak ditemukan.');
        redirect('categories/index.php');
    }
    $data = ['name' => $row['name'], 'description' => (string) $row['description']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data = ['name' => post_string('name'), 'description' => post_string('description')];

    check_length($errors, 'Nama kategori', $data['name'], 1, 100);
    check_length($errors, 'Deskripsi', $data['description'], 0, 255);

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE name = :name AND id <> :id');
        $dup->execute(['name' => $data['name'], 'id' => $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'Nama kategori sudah digunakan.';
        }
    }

    if (!$errors) {
        try {
            $description = $data['description'] === '' ? null : $data['description'];
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
                $stmt->execute(['name' => $data['name'], 'description' => $description, 'id' => $id]);
                flash_set('success', 'Kategori berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
                $stmt->execute(['name' => $data['name'], 'description' => $description]);
                flash_set('success', 'Kategori berhasil ditambahkan.');
            }
            redirect('categories/index.php');
        } catch (PDOException $e) {
            error_log('Simpan kategori gagal: ' . $e->getMessage());
            $errors[] = 'Gagal menyimpan kategori.';
        }
    }
}

$pageTitle  = $isEdit ? 'Edit Kategori' : 'Tambah Kategori';
$activeMenu = 'categories';
require BASE_PATH . '/views/header.php';
?>
<div class="card form-card">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Nama Kategori</label>
            <input type="text" id="name" name="name" maxlength="100" value="<?= e($data['name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Deskripsi (opsional)</label>
            <input type="text" id="description" name="description" maxlength="255" value="<?= e($data['description']) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-outline" href="<?= e(url('categories/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
