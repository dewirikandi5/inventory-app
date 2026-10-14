<?php
/**
 * helpers/stock.php
 * Business logic transaksi stok (barang masuk / barang keluar).
 */

/** Exception untuk pelanggaran business rule yang pesannya aman ditampilkan ke pengguna. */
class StockException extends RuntimeException
{
}

/**
 * Catat satu transaksi stok secara atomik:
 *   BEGIN -> SELECT ... FOR UPDATE -> validasi -> UPDATE products -> INSERT stock_movements -> COMMIT
 * Jika ada langkah yang gagal, seluruh perubahan di-ROLLBACK.
 *
 * @param string $type 'IN' (barang masuk) atau 'OUT' (barang keluar)
 * @return array ['product_name' => string, 'new_stock' => int]
 * @throws StockException jika business rule dilanggar
 * @throws Throwable      untuk kegagalan teknis (setelah rollback)
 */
function record_stock_movement(PDO $pdo, int $productId, string $type, int $quantity, int $userId, ?string $note = null): array
{
    if (!in_array($type, ['IN', 'OUT'], true)) {
        throw new StockException('Jenis transaksi tidak valid.');
    }
    if ($quantity <= 0) {
        throw new StockException('Jumlah harus lebih dari nol.');
    }
    if ($quantity > MAX_QUANTITY) {
        throw new StockException('Jumlah maksimal per transaksi adalah ' . format_number(MAX_QUANTITY) . '.');
    }

    $pdo->beginTransaction();
    try {
        // 1. Kunci baris produk agar tidak ada transaksi lain yang mengubah stok bersamaan
        $stmt = $pdo->prepare('SELECT name, stock FROM products WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new StockException('Produk tidak ditemukan.');
        }

        $currentStock = (int) $product['stock'];

        // 2. Validasi business rule
        if ($type === 'OUT' && $quantity > $currentStock) {
            throw new StockException(
                'Stok tidak cukup. Stok tersedia: ' . format_number($currentStock) . ', diminta: ' . format_number($quantity) . '.'
            );
        }
        if ($type === 'IN' && $currentStock + $quantity > 2147483647) {
            throw new StockException('Stok melebihi batas maksimum yang dapat disimpan.');
        }

        // 3. Update stok
        if ($type === 'IN') {
            $update = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
        } else {
            $update = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');
        }
        $update->bindValue(':qty', $quantity, PDO::PARAM_INT);
        $update->bindValue(':id', $productId, PDO::PARAM_INT);
        $update->execute();

        // (Hanya untuk pengujian rollback) gagal sengaja setelah stok berubah
        if (SIMULATE_STOCK_FAILURE) {
            throw new RuntimeException('Simulasi kegagalan: histori tidak dapat disimpan.');
        }

        // 4. Catat histori
        $insert = $pdo->prepare(
            'INSERT INTO stock_movements (product_id, user_id, type, quantity, note)
             VALUES (:product_id, :user_id, :type, :quantity, :note)'
        );
        $insert->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $insert->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insert->bindValue(':type', $type, PDO::PARAM_STR);
        $insert->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $insert->bindValue(':note', ($note === null || $note === '') ? null : $note, PDO::PARAM_STR);
        $insert->execute();

        $pdo->commit();

        return [
            'product_name' => $product['name'],
            'new_stock'    => $type === 'IN' ? $currentStock + $quantity : $currentStock - $quantity,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
