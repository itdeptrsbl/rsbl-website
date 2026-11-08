<?php
// Form login pengelola, pemeriksaan kata sandi, dan pembatasan percobaan login.

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $key = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'local');
    $attempt = query('SELECT * FROM login_attempts WHERE attempt_key=?', [$key])[0] ?? null;
    if ($attempt && (int) $attempt['blocked_until'] > time()) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam 15 menit.';
    } else {
        $user = query('SELECT * FROM users WHERE email=?', [$email])[0] ?? null;
        if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
            execute('DELETE FROM login_attempts WHERE attempt_key=?', [$key]);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            go('index.php?page=admin');
        }
        $n =
            $attempt && (int) $attempt['blocked_until'] > 0
                ? 1
                : (int) ($attempt['attempts'] ?? 0) + 1;
        if ($attempt) {
            execute('UPDATE login_attempts SET attempts=?,blocked_until=? WHERE attempt_key=?', [
                $n,
                $n >= 5 ? time() + 900 : 0,
                $key,
            ]);
        } else {
            execute(
                'INSERT INTO login_attempts (attempt_key,attempts,blocked_until) VALUES (?,?,?)',
                [$key, 1, 0],
            );
        }
        $error = 'Email atau kata sandi tidak sesuai.';
    }
}
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex">
        <title>Login Pengelola</title>
        <link rel="icon" href="assets/branding/hospital.png" type="image/png">
        <link rel="stylesheet" href="assets/css/base.css">
    </head>
    <body class="auth">
        <main class="auth-card">
            <a class="brand" href="index.php">
                <img class="brand-logo" src="assets/branding/hospital.png" alt="Logo RS Bintang Laut">
                RS Bintang Laut
            </a>
            <p class="eyebrow">KHUSUS PENGELOLA</p>
            <h1>Selamat datang kembali.</h1>
            <p>Kelola informasi dan pelayanan dalam satu tempat.</p>
            <?php
            if (isset($_SESSION['flash'])): ?>
            <div class="notice"><?= e($_SESSION['flash']) ?></div>
            <?php unset($_SESSION['flash']);endif;
            if ($error): ?>
            <div class="notice error"><?= e($error) ?></div>
            <?php endif;
            ?>
            <form method="post">
                <?php
                echo csrf();
                field('email', 'Email', '', 'email');
                field('password', 'Kata sandi', '', 'password');
                ?>
                <button class="button">Masuk ke panel admin →</button>
            </form>
            <a href="index.php">← Kembali ke website</a>
        </main>
    </body>
</html>
