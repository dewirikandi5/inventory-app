<?php
/**
 * helpers/product.php
 * Fungsi bersama untuk master data produk (validasi, cek SKU, simpan + relasi supplier).
 */

function fetch_categories(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
}

function fetch_suppliers(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC')->fetchAll();
}

function sku_exists(PDO $pdo, string $sku, int $exceptProductId = 0): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = :sku AND id <> :id');
    $stmt->execute(['sku' => $sku, 'id' => $exceptProductId]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Validasi form produk (syntactic + semantic).
 * Mengembalikan [$data, $errors, $selectedSuppliers]
 *  - $data: nilai mentah untuk mengisi ulang form
 *  - $selectedSuppliers: [supplier_id => supplier_price (string, '' jika kosong)]
 */
function validate_product_form(PDO $pdo, bool $isCreate, int $productId = 0): array
{
    $errors = [];
    $data = [
        'category_id'   => post_string('category_id'),
        'sku'           => strtoupper(post_string('sku')),
        'name'          => post_string('name'),
        'price'         => post_string('price'),
        'stock'         => $isCreate ? post_string('stock') : '0',
        'minimum_stock' => post_string('minimum_stock'),
    ];

    // Kategori (semantic: harus ada di database)
    $categoryId = filter_var($data['category_id'], FILTER_VALIDATE_INT);
    if ($categoryId === false || $categoryId <= 0) {
        $errors[] = 'Kategori wajib dipilih.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
        $stmt->execute(['id' => $categoryId]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errors[] = 'Kategori tidak ditemukan.';
        }
    }

    // SKU
    if ($data['sku'] === '') {
        $errors[] = 'SKU wajib diisi.';
    } elseif (!valid_sku($data['sku'])) {
        $errors[] = 'SKU harus 3-50 karakter: huruf, angka, titik, strip, atau underscore.';
    } elseif (sku_exists($pdo, $data['sku'], $productId)) {
        $errors[] = 'SKU sudah digunakan oleh produk lain. SKU harus unik.';
    }

    // Nama
    check_length($errors, 'Nama produk', $data['name'], 1, 150);

    // Harga (tidak boleh negatif)
    if ($data['price'] === '' || !valid_price($data['price'])) {
        $errors[] = 'Harga harus berupa angka tidak negatif (maksimal 2 desimal, gunakan titik).';
    }

    // Stok awal (hanya saat membuat produk)
    if ($isCreate && parse_non_negative_int($data['stock']) === null) {
        $errors[] = 'Stok awal harus bilangan bulat tidak negatif.';
    }

    // Minimum stok
    if (parse_non_negative_int($data['minimum_stock']) === null) {
        $errors[] = 'Minimum stok harus bilangan bulat tidak negatif.';
    }

    // Supplier (many-to-many)
    $selected = [];
    $validIds = array_map('intval', array_column(fetch_suppliers($pdo), 'id'));
    $ids      = $_POST['supplier_ids'] ?? [];
    $prices   = $_POST['supplier_price'] ?? [];

    if (is_array($ids)) {
        foreach ($ids as $rawId) {
            $supplierId = filter_var($rawId, FILTER_VALIDATE_INT);
            if ($supplierId === false || !in_array($supplierId, $validIds, true)) {
                $errors[] = 'Supplier yang dipilih tidak valid.';
                continue;
            }
            $price = (is_array($prices) && isset($prices[$supplierId]) && is_string($prices[$supplierId]))
                ? trim($prices[$supplierId]) : '';
            if ($price !== '' && !valid_price($price)) {
                $errors[] = 'Harga supplier harus berupa angka tidak negatif (maksimal 2 desimal).';
            }
            $selected[$supplierId] = $price;
        }
    }

    return [$data, $errors, $selected];
}

/**
 * Simpan produk (insert jika $productId = 0, update jika > 0) beserta relasi supplier,
 * dalam satu database transaction. Mengembalikan id produk.
 */
function save_product(PDO $pdo, array $data, array $selectedSuppliers, int $productId = 0): int
{
    $pdo->beginTransaction();
    try {
        if ($productId === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                 VALUES (:category_id, :sku, :name, :price, :stock, :minimum_stock)'
            );
            $stmt->execute([
                'category_id'   => (int) $data['category_id'],
                'sku'           => $data['sku'],
                'name'          => $data['name'],
                'price'         => $data['price'],
                'stock'         => (int) $data['stock'],
                'minimum_stock' => (int) $data['minimum_stock'],
            ]);
            $productId = (int) $pdo->lastInsertId();
        } else {
            // Stok TIDAK diubah di sini; stok hanya berubah lewat transaksi barang masuk/keluar.
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET category_id = :category_id, sku = :sku, name = :name,
                     price = :price, minimum_stock = :minimum_stock
                 WHERE id = :id'
            );
            $stmt->execute([
                'category_id'   => (int) $data['category_id'],
                'sku'           => $data['sku'],
                'name'          => $data['name'],
                'price'         => $data['price'],
                'minimum_stock' => (int) $data['minimum_stock'],
                'id'            => $productId,
            ]);
        }

        // Sinkronkan junction table product_suppliers
        $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = :pid')
            ->execute(['pid' => $productId]);

        $insert = $pdo->prepare(
            'INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
             VALUES (:pid, :sid, :price)'
        );
        foreach ($selectedSuppliers as $supplierId => $price) {
            $insert->execute([
                'pid'   => $productId,
                'sid'   => (int) $supplierId,
                'price' => $price === '' ? null : $price,
            ]);
        }

        $pdo->commit();
        return $productId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
