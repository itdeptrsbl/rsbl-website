<?php
// Pemasangan awal database dan akun admin; terkunci setelah instalasi selesai.

// Setup hanya tersedia sebelum akun pertama dibuat, lalu otomatis terkunci.
if (installed()) {
    go('index.php?page=login');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190 ||
        strlen($password) < 12 ||
        strlen($password) > 128
    ) {
        $error = 'Isi email valid dan kata sandi 12–128 karakter.';
    } else {
        require APP_ROOT . '/app/database/schema.php';
        // Database kosong diwajibkan agar setup yang gagal tidak menggandakan konten.
        $db->beginTransaction();
        try {
            if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) {
                throw new RuntimeException('Sudah diinstal');
            }
            execute('INSERT INTO users (email,password) VALUES (?,?)', [
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            require APP_ROOT . '/app/database/seed.php';
            $db->commit();
            flash('Instalasi selesai. Silakan login.');
            go('index.php?page=login');
        } catch (Throwable $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Instalasi gagal. Gunakan database kosong dan periksa konfigurasi.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Instalasi • RS Bintang Laut</title>
        <link rel="stylesheet" href="assets/css/base.css">
    </head>
    <body class="auth">
        <main class="auth-card">
            <a class="brand" href="index.php">
                <span class="brand-symbol">✦</span>
                RS Bintang Laut
            </a>
            <p class="eyebrow">PERSIAPAN WEBSITE</p>
            <h1>Mulai dari sini.</h1>
            <p>Buat akun pengelola pertama. Konten contoh akan disiapkan untuk review.</p>
            <?php if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <?php
                echo csrf();
                field('email', 'Email admin', '', 'email');
                field('password', 'Kata sandi (minimal 12 karakter)', '', 'password');
                ?>
                <button class="button">Pasang website</button>
            </form>
            <small>Tidak membutuhkan Composer atau Node. SQLite aktif secara default.</small>
        </main>
    </body>
</html>
