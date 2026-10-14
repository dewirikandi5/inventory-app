<?php
/**
 * helpers/escape.php
 * Output encoding (anti XSS) dan format tampilan.
 */

/** Escape untuk output HTML. Gunakan pada SEMUA data yang berasal dari pengguna/database. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_rupiah($number): string
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

function format_number($number): string
{
    return number_format((float) $number, 0, ',', '.');
}

function format_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '-';
    }
    return date('d M Y H:i', strtotime($datetime));
}

/** Badge indikator stok (HTML statis, tidak memuat data pengguna). */
function stock_badge(int $stock, int $minimum): string
{
    if ($stock <= 0) {
        return '<span class="badge badge-danger">Habis</span>';
    }
    if ($stock <= $minimum) {
        return '<span class="badge badge-warning">Stok rendah</span>';
    }
    return '<span class="badge badge-success">Aman</span>';
}
