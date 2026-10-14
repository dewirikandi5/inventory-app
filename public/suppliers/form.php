<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

$pdo    = db();
$id     = get_int('id');
$isEdit = $id > 0;
$errors = [];
$data   = ['name' => '', 'phone' => '', 'email' => '', 'address' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT name, phone, email, address FROM suppliers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('danger', 'Supplier tidak ditemukan.');
        redirect('suppliers/index.php');
    }
    foreach ($data as $key => $_) {
        $data[$key] = (string) $row[$key];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data = [
        'name'    => post_string('name'),
        'phone'   => post_string('phone'),
        'email'   => post_string('email'),
        'address' => post_string('address'),
    ];

    check_length($errors, 'Nama supplier', $data['name'], 1, 150);
    check_length($errors, 'Telepon', $data['phone'], 0, 30);
    check_length($errors, 'Alamat', $data['address'], 0, 255);
    if ($data['phone'] !== '' && preg_match('/^[0-9+\-\s()]{5,30}$/', $data['phone']) !== 1) {
        $errors[] = 'Format telepon tidak valid.';
    }
    if ($data['email'] !== '' && !valid_email($data['email'])) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors) {
        $params = [
            'name'    => $data['name'],
            'phone'   => $data['phone'] === '' ? null : $data['phone'],
            'email'   => $data['email'] === '' ? null : $data['email'],
            'address' => $data['address'] === '' ? null : $data['address'],
        ];
        try {
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE suppliers SET name = :name, phone = :phone, email = :email, address = :address WHERE id = :id');
                $stmt->execute($params + ['id' => $id]);
                flash_set('success', 'Supplier berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (:name, :phone, :email, :address)');
                $stmt->execute($params);
                flash_set('success', 'Supplier berhasil ditambahkan.');
            }
            redirect('suppliers/index.php');
        } catch (PDOException $e) {
            error_log('Simpan supplier gagal: ' . $e->getMessage());
            $errors[] = 'Gagal menyimpan supplier.';
        }
    }
}

$pageTitle  = $isEdit ? 'Edit Supplier' : 'Tambah Supplier';
$activeMenu = 'suppliers';
require BASE_PATH . '/views/header.php';
?>
<div class="card form-card">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="name">Nama Supplier</label>
            <input type="text" id="name" name="name" maxlength="150" value="<?= e($data['name']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Telepon</label>
                <input type="text" id="phone" name="phone" maxlength="30" value="<?= e($data['phone']) ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="100" value="<?= e($data['email']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Alamat</label>
            <input type="text" id="address" name="address" maxlength="255" value="<?= e($data['address']) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-outline" href="<?= e(url('suppliers/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
