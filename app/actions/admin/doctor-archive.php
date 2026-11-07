<?php
// Menonaktifkan dokter tanpa menghapus riwayat pendaftarannya.

execute('UPDATE doctors SET active=0 WHERE id=?', [(int) $_POST['id']]);
flash('Dokter dinonaktifkan. Riwayat pendaftaran tetap tersimpan.');
