<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

$pdo = db();

// Semua angka berasal dari database (tidak ditulis manual).
$totalProducts  = (int) $pdo->query('SELECT COUNT(p.id) FROM products p')->fetchColumn();
$inventoryValue = (float) $pdo->query('SELECT COALESCE(SUM(p.stock * p.price), 0) FROM products p')->fetchColumn();
$lowStockCount  = (int) $pdo->query('SELECT COUNT(*) FROM products p WHERE p.stock <= p.minimum_stock')->fetchColumn();

$totals = $pdo->query(
    "SELECT COALESCE(SUM(CASE WHEN sm.type = 'IN'  THEN sm.quantity ELSE 0 END), 0) AS total_in,
            COALESCE(SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END), 0) AS total_out
     FROM stock_movements sm"
)->fetch();

$lowStockProducts = $pdo->query(
    'SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.stock <= p.minimum_stock
     ORDER BY (p.stock - p.minimum_stock) ASC, p.name ASC
     LIMIT 10'
)->fetchAll();

$categorySummary = $pdo->query(
    'SELECT c.name, COUNT(p.id) AS total_products,
            COALESCE(SUM(p.stock), 0) AS total_stock,
            AVG(p.price) AS average_price
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name ASC'
)->fetchAll();

$recentMovements = $pdo->query(
    'SELECT sm.type, sm.quantity, sm.created_at, p.name AS product_name, u.username
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     JOIN users u ON u.id = sm.user_id
     ORDER BY sm.created_at DESC, sm.id DESC
     LIMIT 5'
)->fetchAll();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
require BASE_PATH . '/views/header.php';
?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total Produk</div>
        <div class="value"><?= e(format_number($totalProducts)) ?></div>
    </div>
    <div class="stat-card stat-info">
        <div class="label">Nilai Inventory (stok × harga)</div>
        <div class="value"><?= e(format_rupiah($inventoryValue)) ?></div>
    </div>
    <div class="stat-card stat-warning">
        <div class="label">Produk Stok Rendah</div>
        <div class="value"><?= e(format_number($lowStockCount)) ?></div>
    </div>
    <div class="stat-card stat-success">
        <div class="label">Total Barang Masuk</div>
        <div class="value"><?= e(format_number($totals['total_in'])) ?></div>
    </div>
    <div class="stat-card stat-danger">
        <div class="label">Total Barang Keluar</div>
        <div class="value"><?= e(format_number($totals['total_out'])) ?></div>
    </div>
</div>

<div class="card">
    <h2>⚠ Produk dengan Stok Rendah</h2>
    <?php if (!$lowStockProducts): ?>
        <p class="muted">Tidak ada produk dengan stok rendah.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>SKU</th><th>Produk</th><th>Kategori</th><th class="num">Stok</th><th class="num">Minimum</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($lowStockProducts as $p): ?>
                    <tr class="<?= (int) $p['stock'] === 0 ? 'row-empty' : 'row-low' ?>">
                        <td><?= e($p['sku']) ?></td>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['category_name']) ?></td>
                        <td class="num"><?= e(format_number($p['stock'])) ?></td>
                        <td class="num"><?= e(format_number($p['minimum_stock'])) ?></td>
                        <td><?= stock_badge((int) $p['stock'], (int) $p['minimum_stock']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Ringkasan per Kategori</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kategori</th><th class="num">Produk</th><th class="num">Total Stok</th><th class="num">Rata-rata Harga</th></tr></thead>
                <tbody>
                <?php foreach ($categorySummary as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td class="num"><?= e(format_number($row['total_products'])) ?></td>
                        <td class="num"><?= e(format_number($row['total_stock'])) ?></td>
                        <td class="num"><?= $row['average_price'] === null ? '-' : e(format_rupiah($row['average_price'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Transaksi Terbaru</h2>
        <?php if (!$recentMovements): ?>
            <p class="muted">Belum ada transaksi stok.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Waktu</th><th>Produk</th><th>Jenis</th><th class="num">Jumlah</th><th>Oleh</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentMovements as $m): ?>
                        <tr>
                            <td><?= e(format_datetime($m['created_at'])) ?></td>
                            <td><?= e($m['product_name']) ?></td>
                            <td><span class="badge <?= $m['type'] === 'IN' ? 'badge-success' : 'badge-danger' ?>"><?= $m['type'] === 'IN' ? 'MASUK' : 'KELUAR' ?></span></td>
                            <td class="num"><?= e(format_number($m['quantity'])) ?></td>
                            <td><?= e($m['username']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
