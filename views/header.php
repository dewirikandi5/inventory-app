<?php
/**
 * views/header.php
 * Layout: sidebar + navbar. Variabel yang dibaca: $pageTitle, $activeMenu.
 */
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Forbidden');
}

$authUser   = current_user();
$pageTitle  = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';

// Menu hanya TAMPILAN. Pembatasan akses sebenarnya tetap diperiksa di server (require_role).
$menuItems = [
    ['key' => 'dashboard',  'label' => 'Dashboard',     'path' => 'dashboard.php',          'icon' => '▦', 'roles' => ['admin', 'staff']],
    ['key' => 'products',   'label' => 'Produk',        'path' => 'products/index.php',     'icon' => '▣', 'roles' => ['admin', 'staff']],
    ['key' => 'stock_in',   'label' => 'Barang Masuk',  'path' => 'stock/in.php',           'icon' => '⬇', 'roles' => ['admin', 'staff']],
    ['key' => 'stock_out',  'label' => 'Barang Keluar', 'path' => 'stock/out.php',          'icon' => '⬆', 'roles' => ['admin', 'staff']],
    ['key' => 'history',    'label' => 'Riwayat Stok',  'path' => 'stock/history.php',      'icon' => '≡', 'roles' => ['admin', 'staff']],
    ['key' => 'report',     'label' => 'Laporan',       'path' => 'stock/report.php',       'icon' => '▤', 'roles' => ['admin', 'staff']],
    ['key' => 'categories', 'label' => 'Kategori',      'path' => 'categories/index.php',   'icon' => '◫', 'roles' => ['admin']],
    ['key' => 'suppliers',  'label' => 'Supplier',      'path' => 'suppliers/index.php',    'icon' => '⛟', 'roles' => ['admin']],
    ['key' => 'users',      'label' => 'Pengguna',      'path' => 'users/index.php',        'icon' => '☺', 'roles' => ['admin']],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">📱 <span>Inventory HP</span></div>
        <nav class="sidebar-nav">
            <?php foreach ($menuItems as $item): ?>
                <?php if (in_array($authUser['role'], $item['roles'], true)): ?>
                    <a href="<?= e(url($item['path'])) ?>"
                       class="nav-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>">
                        <span class="nav-icon"><?= $item['icon'] ?></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu">☰</button>
            <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
            <div class="topbar-user">
                <div class="user-info">
                    <strong><?= e($authUser['full_name']) ?></strong>
                    <span class="badge <?= $authUser['role'] === 'admin' ? 'badge-info' : 'badge-muted' ?>"><?= e(strtoupper($authUser['role'])) ?></span>
                </div>
                <form method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline btn-sm">Keluar</button>
                </form>
            </div>
        </header>

        <main class="content">
            <?php foreach (flash_get() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
