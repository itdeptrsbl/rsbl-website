<?php
// Menyimpan penjelasan singkat halaman Rawat Jalan.

$intro = trim($_POST['outpatient_intro'] ?? '');
if (strlen($intro) > 10000) {
    throw new RuntimeException('Penjelasan Rawat Jalan maksimal 10.000 karakter.');
}
if (query('SELECT name FROM settings WHERE name=?', ['outpatient_intro'])) {
    execute('UPDATE settings SET value=? WHERE name=?', [$intro, 'outpatient_intro']);
} else {
    execute('INSERT INTO settings (name,value) VALUES (?,?)', ['outpatient_intro', $intro]);
}
flash('Penjelasan Rawat Jalan disimpan.');
