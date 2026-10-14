<?php
/**
 * views/stock_transaction.php
 * Logika + tampilan form barang masuk / keluar. Dipakai oleh stock/in.php dan stock/out.php.
 * Wajib: variabel $type ('IN' atau 'OUT') diset oleh halaman pemanggil.
 */
if (!defined('BASE_PATH') || !isset($type) || !in_array($type, ['IN', 'OUT'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$isIn       = ($type === 'IN');
$pdo        = db();
$authUser   = current_user();
$selfPath   = $isIn ? 'stock/in.php' : 'stock/out.php';
$pageTitle  = $isIn ? 'Barang Masuk' : 'Barang Keluar';
$activeMenu = $isIn ? 'stock_in' : 'stock_out';
$errors     = [];
$old        = ['product_id' => get_int('product_id'), 'quantity' => '', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $note      = post_string('note');

    $old = ['product_id' => (int) $productId, 'quantity' => post_string('quantity'), 'note' => $note];

    if ($productId === false || $productId === null || $productId <= 0) {
        $errors[] = 'Produk wajib dipilih.';
    }
    if ($quantity === false || $quantity === null || $quantity <= 0) {
        $errors[] = 'Jumlah harus bilangan bulat lebih dari nol.';
    } elseif ($quantity > MAX_QUANTITY) {
        $errors[] = 'Jumlah maksimal per transaksi adalah ' . format_number(MAX_QUANTITY) . '.';
    }
    if (mb_strlen($note) > 255) {
        $errors[] = 'Catatan maksimal 255 karakter.';
    }

    if (!$errors) {
        try {
            $result = record_stock_movement($pdo, (int) $productId, $type, (int) $quantity, $authUser['id'], $note);
            flash_set('success', sprintf(
                '%s berhasil dicatat untuk "%s". Stok sekarang: %s.',
                $isIn ? 'Barang masuk' : 'Barang keluar',
                $result['product_name'],
                format_number($result['new_stock'])
            ));
            redirect($selfPath); // Post/Redirect/Get: cegah submit ganda saat refresh
        } catch (StockException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Transaksi stok gagal: ' . $e->getMessage());
            $errors[] = 'Transaksi gagal dan seluruh perubahan dibatalkan (rollback). Silakan coba lagi.';
        }
    }
}

$products = $pdo->query(
    'SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock FROM products p ORDER BY p.name ASC'
)->fetchAll();

require BASE_PATH . '/views/header.php';
?>
<div class="card form-card">
    <h2><?= $isIn ? 'Catat Barang Masuk' : 'Catat Barang Keluar' ?></h2>
    <p class="muted">
        <?= $isIn
            ? 'Barang masuk akan menambah stok produk dan tercatat di riwayat.'
            : 'Barang keluar akan mengurangi stok produk. Jumlah tidak boleh melebihi stok tersedia.' ?>
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url($selfPath)) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="product_id">Produk</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Pilih produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $old['product_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['sku'] . ' - ' . $p['name'] . ' (stok: ' . $p['stock'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Jumlah</label>
            <input type="number" id="quantity" name="quantity" min="1" step="1" required
                   value="<?= e($old['quantity']) ?>" placeholder="Contoh: 10">
        </div>

        <div class="form-group">
            <label for="note">Catatan (opsional)</label>
            <input type="text" id="note" name="note" maxlength="255" value="<?= e($old['note']) ?>"
                   placeholder="<?= $isIn ? 'Contoh: Pembelian dari supplier' : 'Contoh: Penjualan ke pelanggan' ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn <?= $isIn ? 'btn-success' : 'btn-warning' ?>">
                Simpan <?= $isIn ? 'Barang Masuk' : 'Barang Keluar' ?>
            </button>
            <a href="<?= e(url('stock/history.php')) ?>" class="btn btn-outline">Lihat Riwayat</a>
        </div>
    </form>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
