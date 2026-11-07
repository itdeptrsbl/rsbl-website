<?php
// Menyimpan status verifikasi dan catatan internal permohonan pasien.

$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (
    !in_array($status, ['Menunggu verifikasi', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'], true) ||
    strlen($notes) > 5000
) {
    throw new RuntimeException('Status atau catatan tidak valid.');
}

execute('UPDATE appointments SET status=?,notes=? WHERE id=?', [
    $status,
    $notes,
    (int) $_POST['id'],
]);
flash('Status pendaftaran disimpan.');
