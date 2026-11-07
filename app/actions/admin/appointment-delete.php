<?php
// Menghapus satu permohonan pendaftaran yang dipilih admin.

execute('DELETE FROM appointments WHERE id=?', [(int) $_POST['id']]);
flash('Data permohonan dihapus.');
