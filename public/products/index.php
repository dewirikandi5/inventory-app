<?php
require_once __DIR__ . '/../../bootstrap.php';
require_login();                       // admin & staff boleh melihat produk

$pdo     = db();
$isAdmin = is_admin();

/* ---------- 1. Baca & validasi parameter ---------- */
// ALLOWLIST kolom sorting: nama kolom TIDAK boleh berasal langsung dari input pengguna.
$sortColumns = ['name' => 'p.name', 'price' => 'p.price', 'stock' => 'p.stock'];

$q          = mb_substr(get_string('q'), 0, 100);
$categoryId = max(0, get_int('category_id'));
$sort       = get_string('sort');
$sort       = array_key_exists($sort, $sortColumns) ? $sort : 'name';
$dir        = strtoupper(get_string('dir'));
$dir        = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';
$perPage    = resolve_per_page();

/* ---------- 2. Bangun WHERE (nilai selalu lewat parameter) ---------- */
$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q_name OR p.sku LIKE :q_sku)';
    $params[':q_name'] = '%' . escape_like($q) . '%';
    $params[':q_sku']  = '%' . escape_like($q) . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- 3. Hitung total & pagination ---------- */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
bind_params($countStmt, $params);
$countStmt->execute();
$totalRows = (int) $countStmt->fetchColumn();
[$page, $totalPages, $offset] = resolve_page($totalRows, $perPage);

/* ---------- 4. Ambil data ---------- */
$orderSql = 'ORDER BY ' . $sortColumns[$sort] . ' ' . $dir . ', p.id ASC';  // aman: dari allowlist
$sql = "SELECT p.id, p.sku, p.name, p.price, p.stock, p.minimum_stock, c.name AS category_name,
               (SELECT GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ')
                FROM product_suppliers ps
                JOIN suppliers s ON s.id = ps.supplier_id
                WHERE ps.product_id = p.id) AS supplier_names
        FROM products p
        JOIN categories c ON c.id = p.category_id
        $whereSql
        $orderSql
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
bind_params($stmt, $params + [':limit' => $perPage, ':offset' => $offset]);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = fetch_categories($pdo);

// Parameter yang dipertahankan saat pindah halaman / ganti sorting
$keepParams = array_filter([
    'q'           => $q,
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'sort'        => $sort,
    'dir'         => $dir,
    'per_page'    => $perPage,
], fn($v) => $v !== null && $v !== '');

$pageTitle  = 'Produk';
$activeMenu = 'products';
require BASE_PATH . '/views/header.php';
?>
<div class="page-header">
    <p class="muted">Daftar produk beserta stok dan supplier.</p>
    <?php if ($isAdmin): ?>
        <a class="btn btn-primary" href="<?= e(url('products/form.php')) ?>">+ Tambah Produk</a>
    <?php endif; ?>
</div>

<div class="card">
    <form method="get" action="" class="filter-form">
        <div>
            <label for="q">Cari (nama / SKU)</label>
            <input type="text" id="q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Contoh: nova">
        </div>
        <div>
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id">
                <option value="0">Semua kategori</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort">Urutkan</label>
            <select id="sort" name="sort">
                <option value="name"  <?= $sort === 'name'  ? 'selected' : '' ?>>Nama</option>
                <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Harga</option>
                <option value="stock" <?= $sort === 'stock' ? 'selected' : '' ?>>Stok</option>
            </select>
        </div>
        <div>
            <label for="dir">Arah</label>
            <select id="dir" name="dir">
                <option value="ASC"  <?= $dir === 'ASC'  ? 'selected' : '' ?>>A-Z / Terkecil (ASC)</option>
                <option value="DESC" <?= $dir === 'DESC' ? 'selected' : '' ?>>Z-A / Terbesar (DESC)</option>
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
            <a class="btn btn-outline" href="<?= e(url('products/index.php')) ?>">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th><?= sort_link('Produk', 'name', $sort, $dir, $keepParams) ?></th>
                    <th>Kategori</th>
                    <th class="num"><?= sort_link('Harga', 'price', $sort, $dir, $keepParams) ?></th>
                    <th class="num"><?= sort_link('Stok', 'stock', $sort, $dir, $keepParams) ?></th>
                    <th>Status</th>
                    <th>Supplier</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="8" class="muted">Tidak ada produk yang cocok dengan pencarian/filter.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
                <?php $rowClass = (int) $p['stock'] === 0 ? 'row-empty' : ((int) $p['stock'] <= (int) $p['minimum_stock'] ? 'row-low' : ''); ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= e($p['sku']) ?></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['category_name']) ?></td>
                    <td class="num"><?= e(format_rupiah($p['price'])) ?></td>
                    <td class="num"><?= e(format_number($p['stock'])) ?> <span class="muted">/ min <?= e(format_number($p['minimum_stock'])) ?></span></td>
                    <td><?= stock_badge((int) $p['stock'], (int) $p['minimum_stock']) ?></td>
                    <td class="wrap"><?= e($p['supplier_names'] ?? '-') ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-success btn-sm" href="<?= e(url('stock/in.php?product_id=' . (int) $p['id'])) ?>">Masuk</a>
                            <a class="btn btn-warning btn-sm" href="<?= e(url('stock/out.php?product_id=' . (int) $p['id'])) ?>">Keluar</a>
                            <?php if ($isAdmin): ?>
                                <a class="btn btn-outline btn-sm" href="<?= e(url('products/form.php?id=' . (int) $p['id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('products/delete.php')) ?>"
                                      data-confirm="Hapus produk ini? Produk yang memiliki histori tidak dapat dihapus.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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

    <div class="table-footer">
        <span>Menampilkan <?= e(format_number(count($products))) ?> dari <?= e(format_number($totalRows)) ?> produk &middot; Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?></span>
    </div>
    <?= render_pagination($page, $totalPages, $keepParams) ?>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
