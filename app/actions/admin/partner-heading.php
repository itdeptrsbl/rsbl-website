<?php
// Menyimpan judul dan subjudul bagian mitra pada beranda.

$heading = trim($_POST['partner_heading'] ?? '');
$subtitle = trim($_POST['partner_subtitle'] ?? '');
if ($heading === '' || strlen($heading) > 255) {
    throw new RuntimeException('Judul mitra wajib diisi, maksimal 255 karakter.');
}
if (strlen($subtitle) > 1000) {
    throw new RuntimeException('Subjudul mitra maksimal 1.000 karakter.');
}
if (query('SELECT name FROM settings WHERE name=?', ['partner_heading'])) {
    execute('UPDATE settings SET value=? WHERE name=?', [$heading, 'partner_heading']);
} else {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', ['partner_heading', $heading]);
}
if (query('SELECT name FROM settings WHERE name=?', ['partner_subtitle'])) {
    execute('UPDATE settings SET value=? WHERE name=?', [$subtitle, 'partner_subtitle']);
} else {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', ['partner_subtitle', $subtitle]);
}
flash('Judul dan subjudul mitra disimpan.');
