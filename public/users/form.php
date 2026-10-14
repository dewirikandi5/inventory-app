<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');

$pdo    = db();
$id     = get_int('id');
$isEdit = $id > 0;
$isSelf = $isEdit && $id === (int) current_user()['id'];
$errors = [];
$data   = ['username' => '', 'role' => 'staff', 'is_active' => '1', 'full_name' => '', 'phone' => '', 'email' => ''];

if ($isEdit) {
    $stmt = $pdo->prepare(
        'SELECT u.username, u.role, u.is_active, up.full_name, up.phone, up.email
         FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id
         WHERE u.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('danger', 'Pengguna tidak ditemukan.');
        redirect('users/index.php');
    }
    $data = [
        'username'  => $row['username'],
        'role'      => $row['role'],
        'is_active' => (string) $row['is_active'],
        'full_name' => (string) $row['full_name'],
        'phone'     => (string) $row['phone'],
        'email'     => (string) $row['email'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $password = (string) ($_POST['password'] ?? '');
    $posted = [
        'username'  => $isEdit ? $data['username'] : post_string('username'),
        'role'      => post_string('role'),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
        'full_name' => post_string('full_name'),
        'phone'     => post_string('phone'),
        'email'     => post_string('email'),
    ];
    // Admin tidak boleh mengubah role / status akunnya sendiri (mencegah mengunci diri).
    if ($isSelf) {
        $posted['role']      = $data['role'];
        $posted['is_active'] = '1';
    }
    $data = $posted;

    if (!$isEdit) {
        if (!valid_username($data['username'])) {
            $errors[] = 'Username harus 3-50 karakter (huruf, angka, underscore).';
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
            $dup->execute(['u' => $data['username']]);
            if ((int) $dup->fetchColumn() > 0) {
                $errors[] = 'Username sudah digunakan.';
            }
        }
    }
    if (!$isEdit || $password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        } elseif (strlen($password) > 72) {
            $errors[] = 'Password maksimal 72 karakter.';
        }
    }
    if (!in_array($data['role'], ['admin', 'staff'], true)) {
        $errors[] = 'Role tidak valid.';
    }
    check_length($errors, 'Nama lengkap', $data['full_name'], 1, 100);
    if ($data['phone'] !== '' && preg_match('/^[0-9+\-\s()]{5,30}$/', $data['phone']) !== 1) {
        $errors[] = 'Format telepon tidak valid.';
    }
    if ($data['email'] !== '' && !valid_email($data['email'])) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors) {
        $phone = $data['phone'] === '' ? null : $data['phone'];
        $email = $data['email'] === '' ? null : $data['email'];

        $pdo->beginTransaction();
        try {
            if ($isEdit) {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET role = :role, is_active = :active, password_hash = :hash WHERE id = :id');
                    $stmt->execute(['role' => $data['role'], 'active' => (int) $data['is_active'],
                                    'hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET role = :role, is_active = :active WHERE id = :id');
                    $stmt->execute(['role' => $data['role'], 'active' => (int) $data['is_active'], 'id' => $id]);
                }
                $userId = $id;
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role, is_active) VALUES (:u, :hash, :role, :active)');
                $stmt->execute(['u' => $data['username'], 'hash' => password_hash($password, PASSWORD_DEFAULT),
                                'role' => $data['role'], 'active' => (int) $data['is_active']]);
                $userId = (int) $pdo->lastInsertId();
            }

            // Profil (one-to-one): insert baru atau update jika sudah ada (user_id UNIQUE)
            $profile = $pdo->prepare(
                'INSERT INTO user_profiles (user_id, full_name, phone, email)
                 VALUES (:uid, :name, :phone, :email)
                 ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone = VALUES(phone), email = VALUES(email)'
            );
            $profile->execute(['uid' => $userId, 'name' => $data['full_name'], 'phone' => $phone, 'email' => $email]);

            $pdo->commit();
            flash_set('success', $isEdit ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.');
            redirect('users/index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Simpan pengguna gagal: ' . $e->getMessage());
            $errors[] = $e->getCode() === '23000' ? 'Username sudah digunakan.' : 'Gagal menyimpan pengguna.';
        }
    }
}

$pageTitle  = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
$activeMenu = 'users';
require BASE_PATH . '/views/header.php';
?>
<div class="card form-card">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" action="" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" maxlength="50" value="<?= e($data['username']) ?>"
                       <?= $isEdit ? 'readonly' : 'required' ?>>
            </div>
            <div class="form-group">
                <label for="password">Password <?= $isEdit ? '(kosongkan jika tidak diubah)' : '' ?></label>
                <input type="password" id="password" name="password" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
                <div class="hint">Minimal 8 karakter. Disimpan sebagai hash.</div>
            </div>
        </div>
        <div class="form-group">
            <label for="full_name">Nama Lengkap</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($data['full_name']) ?>" required>
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
        <div class="form-row">
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="staff" <?= $data['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <?php if ($isSelf): ?><div class="hint">Anda tidak dapat mengubah role akun sendiri.</div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="is_active">Status Akun</label>
                <label style="font-weight:400">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           <?= $data['is_active'] === '1' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>> Aktif
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-outline" href="<?= e(url('users/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
