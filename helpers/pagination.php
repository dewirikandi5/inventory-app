<?php
/**
 * helpers/pagination.php
 * Pagination dan link sorting yang mempertahankan parameter search/filter.
 */

/** Jumlah data per halaman, hanya dari allowlist PER_PAGE_OPTIONS. */
function resolve_per_page(): int
{
    $perPage = get_int('per_page', PER_PAGE_DEFAULT);
    return in_array($perPage, PER_PAGE_OPTIONS, true) ? $perPage : PER_PAGE_DEFAULT;
}

/** Hitung halaman aktif. Mengembalikan [page, totalPages, offset]. */
function resolve_page(int $totalRows, int $perPage): array
{
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $page = max(1, get_int('page', 1));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    return [$page, $totalPages, $offset];
}

/** Bind parameter ke statement (int => PARAM_INT, lainnya => PARAM_STR). */
function bind_params(PDOStatement $stmt, array $params): void
{
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function render_pagination(int $page, int $totalPages, array $params): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pagination" aria-label="Pagination">';

    $link = function (string $label, int $target, string $class = '') use (&$html, $params) {
        $href = '?' . http_build_query(array_merge($params, ['page' => $target]));
        $html .= '<a class="page-link ' . $class . '" href="' . e($href) . '">' . e($label) . '</a>';
    };
    $disabled = function (string $label) use (&$html) {
        $html .= '<span class="page-link disabled">' . e($label) . '</span>';
    };

    if ($page > 1) {
        $link('‹ Sebelumnya', $page - 1);
    } else {
        $disabled('‹ Sebelumnya');
    }

    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);

    if ($start > 1) {
        $link('1', 1);
        if ($start > 2) {
            $html .= '<span class="page-gap">…</span>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $link((string) $i, $i, $i === $page ? 'active' : '');
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="page-gap">…</span>';
        }
        $link((string) $totalPages, $totalPages);
    }

    if ($page < $totalPages) {
        $link('Berikutnya ›', $page + 1);
    } else {
        $disabled('Berikutnya ›');
    }

    return $html . '</nav>';
}

/** Link header kolom untuk sorting (toggle ASC/DESC, kembali ke halaman 1). */
function sort_link(string $label, string $column, string $currentSort, string $currentDir, array $params): string
{
    $newDir = ($currentSort === $column && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $href   = '?' . http_build_query(array_merge($params, ['sort' => $column, 'dir' => $newDir, 'page' => 1]));
    $arrow  = $currentSort === $column ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="' . e($href) . '">' . e($label . $arrow) . '</a>';
}
