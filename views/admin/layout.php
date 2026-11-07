<?php
// Kerangka panel admin: sidebar, judul halaman, pesan, dan pemilihan tab.
?><!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="robots" content="noindex">
        <title><?= e($modules[$tab]) ?> • Admin RSBL</title>
        <link rel="stylesheet" href="assets/css/base.css">
        <link rel="stylesheet" href="assets/css/admin/admin-partners.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/admin/admin-partners.css',
        ) ?>">
        <link rel="stylesheet" href="assets/css/pages/leader-photo.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/leader-photo.css',
        ) ?>">
        <script src="assets/js/core/app.js" defer></script>
        <script src="assets/js/components/slide-display.js" defer></script>
        <link rel="stylesheet" href="assets/css/components/photo-editor.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/components/photo-editor.css',
        ) ?>">
        <link rel="stylesheet" href="assets/css/admin/history-editor.css">
        <script src="assets/js/admin/history-editor.js" defer>
        </script>
        <script src="assets/js/admin/doctor-schedule-editor.js" defer>
        </script>
        <link rel="stylesheet" href="assets/css/admin/doctor-schedule-editor.css">
    </head>
    <body class="admin-body">
        <aside class="sidebar">
            <a class="brand" href="index.php?page=admin">
                <span class="brand-symbol">✦</span>
                <span>
                    RS Bintang Laut
                    <small>PANEL PENGELOLA</small>
                </span>
            </a>
            <p class="eyebrow">WORKSPACE</p>
            <nav>
                <?php foreach ($modules as $slug => $label): ?>
                <a class="<?= $slug === $tab
                    ? 'selected'
                    : '' ?>" href="index.php?page=admin&tab=<?= $slug ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-bottom">
                <a href="index.php" target="_blank" rel="noopener">Lihat website ↗</a>
                <form method="post" enctype="multipart/form-data" action="index.php?page=logout">
                    <?= csrf() ?>
                    <button class="text-button">Keluar</button>
                </form>
            </div>
        </aside>
        <main class="admin-main">
            <header class="admin-header">
                <div>
                    <p class="eyebrow">MANAJEMEN WEBSITE</p>
                    <h1><?= e($modules[$tab]) ?></h1>
                </div>
                <span class="badge">Pengelola RSBL</span>
            </header>
            <?php if ($error): ?>
            <div class="notice error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
<?php if (isset($_SESSION['flash'])): ?>
            <div class="notice"><?= e($_SESSION['flash']) ?></div>
            <?php unset($_SESSION['flash']);endif; ?>
<?php if ($tab === 'dashboard'): ?>
<?php require APP_ROOT . '/views/admin/pages/dashboard.php'; ?>
<?php elseif ($tab === 'settings'): ?>
<?php require APP_ROOT . '/views/admin/pages/settings.php'; ?>
<?php elseif (in_array($tab, ['contents', 'information'], true)): ?>
<?php require APP_ROOT . '/views/admin/pages/contents.php'; ?>
<?php elseif ($tab === 'doctors'): ?>
<?php require APP_ROOT . '/views/admin/pages/doctors.php'; ?>
<?php elseif ($tab === 'appointments'): ?>
<?php require APP_ROOT . '/views/admin/pages/appointments.php'; ?>
<?php elseif ($tab === 'account'): ?>
<?php require APP_ROOT . '/views/admin/pages/account.php'; ?>
<?php endif; ?>
        </main>
    </body>
</html>
