<?php
// Memeriksa kata sandi lama dan mengganti kata sandi akun admin.

$user = query('SELECT * FROM users WHERE id=?', [$_SESSION['admin_id']])[0];
$new = $_POST['new_password'] ?? '';

if (
    !password_verify($_POST['current_password'] ?? '', $user['password']) ||
    strlen($new) < 12 ||
    strlen($new) > 128
) {
    throw new RuntimeException(
        'Kata sandi lama salah atau kata sandi baru kurang dari 12 karakter.',
    );
}

execute('UPDATE users SET password=? WHERE id=?', [
    password_hash($new, PASSWORD_DEFAULT),
    $_SESSION['admin_id'],
]);
session_regenerate_id(true);
flash('Kata sandi diperbarui.');
