<?php
require_once __DIR__ . '/../../bootstrap.php';
require_role('admin');                 // hanya admin yang boleh menambah/mengubah produk

$pdo    = db();
$id     = get_int('id');
$isEdit = $id > 0;
$errors = [];

$data     = ['category_id' => '', 'sku' => '', 'name' => '', 'price' => '', 'stock' => '0', 'minimum_stock' => '5'];
$selected = [];   // [supplier_id => supplier_price]

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT category_id, sku, name, price, stock, minimum_stock FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('danger', 'Produk tidak ditemukan.');
        redirect('products/index.php');
    }
    $data = [
        'category_id'   => (string) $row['category_id'],
        'sku'           => $row['sku'],
        'name'          => $row['name'],
        'price'         => (string) $row['price'],
        'stock'         => (string) $row['stock'],
        'minimum_stock' => (string) $row['minimum_stock'],
    ];
    $ps = $pdo->prepare('SELECT supplier_id, supplier_price FROM product_suppliers WHERE product_id = :id');
    $ps->execute(['id' => $id]);
    foreach ($ps->fetchAll() as $r) {
        $selected[(int) $r['supplier_id']] = $r['supplier_price'] === null ? '' : (string) $r['supplier_price'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$posted, $errors, $postedSuppliers] = validate_product_form($pdo, !$isEdit, $id);
    $data     = $isEdit ? array_merge($posted, ['stock' => $data['stock']]) : $posted;
    $selected = $postedSuppliers;

    if (!$errors) {
        try {
            save_product($pdo, $data, $selected, $isEdit ? $id : 0);
            flash_set('success', $isEdit ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.');
            redirect('products/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // pelanggaran unique/foreign key (mis. SKU ganda saat race)
                $errors[] = 'Data tidak dapat disimpan: SKU sudah digunakan atau relasi tidak valid.';
            } else {
                error_log('Simpan produk gagal: ' . $e->getMessage());
                $errors[] = 'Gagal menyimpan produk.';
            }
        }
    }
}

$categories = fetch_categories($pdo);
$suppliers  = fetch_suppliers($pdo);

$pageTitle  = $isEdit ? 'Edit Produk' : 'Tambah Produk';
$activeMenu = 'products';
require BASE_PATH . '/views/header.php';
?>
<div class="card form-card">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-group">
                <label for="sku">SKU</label>
                <input type="text" id="sku" name="sku" maxlength="50" value="<?= e($data['sku']) ?>" required placeholder="Contoh: HP-NVX5-256">
                <div class="hint">Harus unik. Huruf, angka, titik, strip, underscore.</div>
            </div>
            <div class="form-group">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === (string) $data['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="name">Nama Produk</label>
            <input type="text" id="name" name="name" maxlength="150" value="<?= e($data['name']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Harga (Rp)</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?= e($data['price']) ?>" required>
            </div>
            <div class="form-group">
                <label for="stock"><?= $isEdit ? 'Stok Saat Ini' : 'Stok Awal' ?></label>
                <input type="number" id="stock" name="stock" min="0" step="1" value="<?= e($data['stock']) ?>"
                       <?= $isEdit ? 'readonly' : 'required' ?>>
                <?php if ($isEdit): ?>
                    <div class="hint">Stok hanya berubah lewat transaksi barang masuk/keluar.</div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="minimum_stock">Minimum Stok</label>
                <input type="number" id="minimum_stock" name="minimum_stock" min="0" step="1" value="<?= e($data['minimum_stock']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Supplier (opsional, dapat lebih dari satu)</label>
            <?php if (!$suppliers): ?>
                <p class="muted">Belum ada supplier. Tambahkan di menu Supplier.</p>
            <?php else: ?>
                <div class="supplier-list">
                    <?php foreach ($suppliers as $s): ?>
                        <?php $sid = (int) $s['id']; $checked = array_key_exists($sid, $selected); ?>
                        <div class="supplier-item">
                            <label>
                                <input type="checkbox" name="supplier_ids[]" value="<?= $sid ?>" <?= $checked ? 'checked' : '' ?>>
                                <?= e($s['name']) ?>
                            </label>
                            <input type="number" name="supplier_price[<?= $sid ?>]" min="0" step="0.01"
                                   placeholder="Harga supplier" value="<?= e($checked ? $selected[$sid] : '') ?>"
                                   aria-label="Harga dari <?= e($s['name']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a class="btn btn-outline" href="<?= e(url('products/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
