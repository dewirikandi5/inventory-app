<?php
/**
 * helpers/validation.php
 * Validasi input di sisi server (syntactic) + helper pembaca input.
 */

/** Ambil string dari $_POST (trim). Jika bukan string, kembalikan ''. */
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Ambil string dari $_GET (trim). */
function get_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Ambil integer dari $_GET; jika tidak valid kembalikan $default. */
function get_int(string $key, int $default = 0): int
{
    $value = $_GET[$key] ?? null;
    if (!is_string($value)) {
        return $default;
    }
    $int = filter_var($value, FILTER_VALIDATE_INT);
    return $int === false ? $default : $int;
}

/** Harga: angka >= 0, maksimal 2 desimal (titik), maksimal 10 digit bulat. */
function valid_price(string $value): bool
{
    return preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value) === 1;
}

/** Bilangan bulat >= 0 (maks 9 digit). Mengembalikan null jika tidak valid. */
function parse_non_negative_int(string $value): ?int
{
    return preg_match('/^\d{1,9}$/', $value) === 1 ? (int) $value : null;
}

/** SKU: 3-50 karakter, huruf/angka/titik/strip/underscore, diawali huruf atau angka. */
function valid_sku(string $value): bool
{
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,49}$/', $value) === 1;
}

function valid_username(string $value): bool
{
    return preg_match('/^[A-Za-z0-9_]{3,50}$/', $value) === 1;
}

function valid_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($value) <= 100;
}

/** Validasi panjang teks. Menambahkan pesan ke $errors jika tidak sesuai. */
function check_length(array &$errors, string $label, string $value, int $min, int $max): void
{
    $length = mb_strlen($value);
    if ($length < $min) {
        $errors[] = $min === 1 ? "$label wajib diisi." : "$label minimal $min karakter.";
    } elseif ($length > $max) {
        $errors[] = "$label maksimal $max karakter.";
    }
}

/** Escape karakter khusus LIKE (% dan _) agar input pengguna tidak menjadi wildcard liar. */
function escape_like(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}
