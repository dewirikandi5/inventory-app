<?php
/**
 * helpers/flash.php
 * Notifikasi berhasil / gagal yang tampil satu kali.
 * Tipe: success, danger, warning, info
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
