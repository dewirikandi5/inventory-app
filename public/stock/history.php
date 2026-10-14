<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin', 'staff');

$pdo = db();

$q       = mb_substr(get_string('q'), 0, 100);
$type    = strtoupper(get_string('type'));
$type    = in_array($type, ['IN', 'OUT'], true) ? $type : '';   // allowlist
$perPage = resolve_per_page();

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q_name OR p.sku LIKE :q_sku)';
    $params[':q_name'] = '%' . escape_like($q) . '%';
    $params[':q_sku']  = '%' . escape_like($q) . '%';
}
if ($type !== '') {
    $where[] = 'sm.type = :type';
    $params[':type'] = $type;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM stock_movements sm JOIN products p ON p.id = sm.product_id $whereSql"
);
bind_params($countStmt, $params);
$countStmt->execute();
$totalRows = (int) $countStmt->fetchColumn();
[$page, $totalPages, $offset] = resolve_page($totalRows, $perPage);

$stmt = $pdo->prepare(
    "SELECT sm.id, sm.type, sm.quantity, sm.note, sm.created_at,
            p.sku, p.name AS product_name, u.username
     FROM stock_movements sm
     JOIN products p ON p.id = sm.product_id
     JOIN users u ON u.id = sm.user_id
     $whereSql
     ORDER BY sm.created_at DESC, sm.id DESC
     LIMIT :limit OFFSET :offset"
);
bind_params($stmt, $params + [':limit' => $perPage, ':offset' => $offset]);
$stmt->execute();
$rows = $stmt->fetchAll();

$keepParams = array_filter(['q' => $q, 'type' => $type, 'per_page' => $perPage], fn($v) => $v !== '');

$pageTitle  = 'Riwayat Stok';
$activeMenu = 'history';
require BASE_PATH . '/views/header.php';
?>
<div class="card">
    <form method="get" action="" class="filter-form">
        <div>
            <label for="q">Cari produk (nama / SKU)</label>
            <input type="text" id="q" name="q" maxlength="100" value="<?= e($q) ?>">
        </div>
        <div>
            <label for="type">Jenis</label>
            <select id="type" name="type">
                <option value="">Semua</option>
                <option value="IN"  <?= $type === 'IN'  ? 'selected' : '' ?>>Barang Masuk</option>
                <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>Barang Keluar</option>
            </select>
        </div>
        <div>
            <label for="per_page">Per halaman</label>
            <select id="per_page" name="per_page">
                <?php foreach (PER_PAGE_OPTIONS as $opt): ?>
                    <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a class="btn btn-outline" href="<?= e(url('stock/history.php')) ?>">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Waktu</th><th>SKU</th><th>Produk</th><th>Jenis</th><th class="num">Jumlah</th><th>Oleh</th><th>Catatan</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="muted">Belum ada riwayat transaksi.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e(format_datetime($r['created_at'])) ?></td>
                    <td><?= e($r['sku']) ?></td>
                    <td><?= e($r['product_name']) ?></td>
                    <td><span class="badge <?= $r['type'] === 'IN' ? 'badge-success' : 'badge-danger' ?>"><?= $r['type'] === 'IN' ? 'MASUK' : 'KELUAR' ?></span></td>
                    <td class="num"><?= e(format_number($r['quantity'])) ?></td>
                    <td><?= e($r['username']) ?></td>
                    <td class="wrap"><?= e($r['note']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Total <?= e(format_number($totalRows)) ?> transaksi &middot; Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?></span>
    </div>
    <?= render_pagination($page, $totalPages, $keepParams) ?>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
