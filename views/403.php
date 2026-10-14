<?php
/**
 * views/403.php - ditampilkan oleh require_role() bila role tidak diizinkan.
 */
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Forbidden');
}
require BASE_PATH . '/views/header.php';
?>
<div class="card empty-state">
    <h2>403 &ndash; Akses Ditolak</h2>
    <p>Anda tidak memiliki izin untuk membuka halaman ini.</p>
    <p><a class="btn btn-primary" href="<?= e(url('dashboard.php')) ?>">Kembali ke Dashboard</a></p>
</div>
<?php require BASE_PATH . '/views/footer.php'; ?>
