<?php
/**
 * database/create_user.php
 * Membuat akun awal (admin/staff) dengan password yang di-hash.
 * HANYA bisa dijalankan dari command line (CLI), bukan dari browser.
 *
 * Pemakaian (Windows + XAMPP):
 *   C:\xampp\php\php.exe database\create_user.php <username> <password> <admin|staff> "<Nama Lengkap>"
 * Contoh:
 *   C:\xampp\php\php.exe database\create_user.php admin Admin12345 admin "Administrator"
 *   C:\xampp\php\php.exe database\create_user.php staff1 Staff12345 staff "Staff Gudang"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../config/database.php';

if ($argc < 5) {
    fwrite(STDERR, "Pemakaian: php database/create_user.php <username> <password> <admin|staff> \"<Nama Lengkap>\"\n");
    exit(1);
}

[, $username, $password, $role, $fullName] = $argv;
$errors = [];

if (preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) !== 1) {
    $errors[] = 'Username harus 3-50 karakter (huruf, angka, underscore).';
}
if (strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter.';
}
if (!in_array($role, ['admin', 'staff'], true)) {
    $errors[] = 'Role harus admin atau staff.';
}
if ($fullName === '' || mb_strlen($fullName) > 100) {
    $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
}
if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

$pdo = db();

$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
$check->execute(['username' => $username]);
if ((int) $check->fetchColumn() > 0) {
    fwrite(STDERR, "Username '$username' sudah ada.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, :role)');
    $stmt->execute([
        'username' => $username,
        'hash'     => password_hash($password, PASSWORD_DEFAULT),
        'role'     => $role,
    ]);
    $userId = (int) $pdo->lastInsertId();

    $profile = $pdo->prepare('INSERT INTO user_profiles (user_id, full_name) VALUES (:user_id, :full_name)');
    $profile->execute(['user_id' => $userId, 'full_name' => $fullName]);

    $pdo->commit();
    echo "Akun '$username' ($role) berhasil dibuat.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Gagal membuat akun: ' . $e->getMessage() . "\n");
    exit(1);
}
