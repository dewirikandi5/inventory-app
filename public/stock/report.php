<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin', 'staff');   // asumsi: laporan dapat dilihat (read-only) oleh admin dan staff

$pdo        = db();
$categoryId = max(0, get_int('category_id'));

$where  = '';
$params = [];
if ($categoryId > 0) {
    $where = 'WHERE p.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}

// Laporan inventory: JOIN + SUM(CASE) + GROUP BY (pola dari slide "Contoh Laporan Inventory")
$stmt = $pdo->prepare(
    "SELECT p.id, p.sku, p.name, c.name AS category_name, p.price, p.stock, p.minimum_stock,
            SUM(CASE WHEN sm.type = 'IN'  THEN sm.quantity ELSE 0 END) AS total_in,
            SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END) AS total_out
     FROM products p
     JOIN categories c ON c.id = p.category_id
     LEFT JOIN stock_movements sm ON sm.product_id = p.id
     $where
     GROUP BY p.id, p.sku, p.name, c.name, p.price, p.stock, p.minimum_stock
     ORDER BY c.name ASC, p.name ASC"
);
bind_params($stmt, $params);
$stmt->execute();
$rows = $stmt->fetchAll();

// Total keseluruhan laporan (dihitung dari hasil query)
$sumIn = $sumOut = $sumValue = 0;
foreach ($rows as &$r) {
    $r['total_in']      = (int) $r['total_in'];
    $r['total_out']     = (int) $r['total_out'];
    // stok akhir = stok awal + masuk - keluar  =>  stok awal = stok akhir - masuk + keluar
    $r['initial_stock'] = (int) $r['stock'] - $r['total_in'] + $r['total_out'];
    $r['value']         = (float) $r['stock'] * (float) $r['price'];
    $sumIn    += $r['total_in'];
    $sumOut   += $r['total_out'];
    $sumValue += $r['value'];
}
unset($r);

// Ringkasan agregat per kategori memakai HAVING (hanya kategori yang punya produk)
$summary = $pdo->query(
    'SELECT c.name, COUNT(p.id) AS total_products, SUM(p.stock) AS total_stock,
            AVG(p.price) AS average_price, SUM(p.stock * p.price) AS total_value
     FROM categories c
     JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     HAVING COUNT(p.id) >= 1
     ORDER BY total_value DESC'
)->fetchAll();

$categories = fetch_categories($pdo);

$pageTitle  = 'Laporan Inventory';
$activeMenu = 'report';
require BASE_PATH . '/views/header.php';
?>
<div class="card">
    <form method="get" action="" class="filter-form">
        <div>
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="0">Semua kategori</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a class="btn btn-outline" href="<?= e(url('stock/report.php')) ?>">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <h2>Laporan Pergerakan Stok per Produk</h2>
    <p class="muted">Stok akhir = stok awal + total masuk − total keluar. Stok awal dihitung dari stok saat ini dan histori transaksi.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>SKU</th><th>Produk</th><th>Kategori</th><th class="num">Stok Awal</th><th class="num">Masuk</th><th class="num">Keluar</th><th class="num">Stok Akhir</th><th>Status</th><th class="num">Nilai</th></tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="9" class="muted">Tidak ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['sku']) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['category_name']) ?></td>
                    <td class="num"><?= e(format_number($r['initial_stock'])) ?></td>
                    <td class="num"><?= e(format_number($r['total_in'])) ?></td>
                    <td class="num"><?= e(format_number($r['total_out'])) ?></td>
                    <td class="num"><strong><?= e(format_number($r['stock'])) ?></strong></td>
                    <td><?= stock_badge((int) $r['stock'], (int) $r['minimum_stock']) ?></td>
                    <td class="num"><?= e(format_rupiah($r['value'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($rows): ?>
            <tfoot>
                <tr>
                    <th colspan="4">Total</th>
                    <th class="num"><?= e(format_number($sumIn)) ?></th>
                    <th class="num"><?= e(format_number($sumOut)) ?></th>
                    <th></th><th></th>
                    <th class="num"><?= e(format_rupiah($sumValue)) ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <h2>Ringkasan per Kategori</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Kategori</th><th class="num">Jumlah Produk</th><th class="num">Total Stok</th><th class="num">Rata-rata Harga</th><th class="num">Nilai Inventory</th></tr></thead>
            <tbody>
            <?php foreach ($summary as $s): ?>
                <tr>
                    <td><?= e($s['name']) ?></td>
                    <td class="num"><?= e(format_number($s['total_products'])) ?></td>
                    <td class="num"><?= e(format_number($s['total_stock'])) ?></td>
                    <td class="num"><?= e(format_rupiah($s['average_price'])) ?></td>
                    <td class="num"><?= e(format_rupiah($s['total_value'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
