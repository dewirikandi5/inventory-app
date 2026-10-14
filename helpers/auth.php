<?php
/**
 * helpers/auth.php
 * Session, authentication, authorization, dan CSRF token.
 */

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function current_user(): ?array
{
    return (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

/**
 * AUTHENTICATION: cek username + password.
 * Mengembalikan true jika valid dan menyimpan data user ke session.
 */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT u.id, u.username, u.password_hash, u.role, u.is_active,
                COALESCE(up.full_name, u.username) AS full_name
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.id
         WHERE u.username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user'] = [
        'id'        => (int) $user['id'],
        'username'  => $user['username'],
        'role'      => $user['role'],
        'full_name' => $user['full_name'],
    ];
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Halaman internal wajib login.
 * Setiap request juga memeriksa ulang ke database: akun yang dihapus/dinonaktifkan
 * langsung kehilangan akses, dan perubahan role langsung berlaku.
 */
function require_login(): void
{
    $user = current_user();

    if ($user === null) {
        flash_set('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }

    $stmt = db()->prepare('SELECT role, is_active FROM users WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['is_active'] !== 1) {
        logout_user();
        start_secure_session();
        flash_set('warning', 'Sesi berakhir karena akun tidak aktif atau tidak ditemukan.');
        redirect('login.php');
    }

    $_SESSION['user']['role'] = $row['role'];
}

/**
 * AUTHORIZATION: batasi halaman/proses berdasarkan role (diperiksa di server).
 * Contoh: require_role('admin');  atau  require_role('admin', 'staff');
 */
function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();

    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $pageTitle  = 'Akses Ditolak';
        $activeMenu = '';
        require BASE_PATH . '/views/403.php';
        exit;
    }
}

/* ---------- CSRF (tambahan di luar PPT, praktik keamanan dasar untuk form POST) ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Token keamanan tidak valid atau kedaluwarsa. Kembali dan muat ulang halaman.');
    }
}
