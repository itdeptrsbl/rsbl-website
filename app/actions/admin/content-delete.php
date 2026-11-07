<?php
// Menghapus konten sesuai ID dan kategori yang sedang dikelola.

execute('DELETE FROM contents WHERE id=? AND kind=?', [(int) $_POST['id'], $kind]);
flash('Konten dihapus.');
