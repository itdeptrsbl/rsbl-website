<?php
// Header publik: identitas rumah sakit, kontak, menu, dan aset halaman.

require_once APP_ROOT . '/app/support/branding.php';

$pageTitles = [
    'home' => 'Beranda',
    'doctors' => 'Daftar Dokter',
    'doctor_schedule' => 'Jadwal Dokter',
    'doctor_profile' => 'Profil Dokter',
    'services' => 'Fasilitas dan Layanan',
    'outpatient' => 'Rawat Jalan',
    'inpatient' => 'Rawat Inap',
    'emergency' => 'Instalasi Gawat Darurat',
    'news' => 'Informasi',
    'about' => 'Tentang Kami',
    'history' => 'Sejarah',
    'vision' => 'Visi & Misi',
    'quality' => 'Laporan Mutu',
    'register' => 'Pendaftaran Online',
    'contact' => 'Hubungi Kami',
    'detail' => 'Informasi',
];

foreach (facilityPhotoPages() as $facilitySlug => $facilityPage) {
    $pageTitles[$facilitySlug] = $facilityPage['title'];
}

$navigationItems = [
    'home' => 'Beranda',
    'about' => 'Tentang Kami',
    'doctors' => 'Dokter',
    'services' => 'Fasilitas dan Layanan',
    'news' => 'Informasi',
];

$socialPlatforms = [
    'instagram' => 'Instagram',
    'facebook' => 'Facebook',
    'youtube' => 'YouTube',
    'tiktok' => 'TikTok',
];
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= e(
            setting('name'),
        ) ?> &mdash; informasi dokter, layanan, dan pendaftaran online di Palopo.">
        <title><?= e($pageTitles[$page] ?? 'Beranda') ?> | <?= e(setting('name')) ?></title>
        <?php if ($page === 'register'): ?>
        <link rel="stylesheet" href="assets/css/pages/registration.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/registration.css',
        ) ?>">
        <script src="assets/js/pages/registration.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/pages/registration.js',
        ) ?>" defer></script>
        <?php endif; ?>
        <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
        <link rel="stylesheet" href="assets/css/base.css">
        <link rel="stylesheet" href="assets/css/layout/header.css">
        <link rel="stylesheet" href="assets/css/layout/social-icons.css">
        <link rel="stylesheet" href="assets/css/pages/doctors.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/doctors.css',
        ) ?>">
        <?php if ($page === 'history'): ?>
        <link rel="stylesheet" href="assets/css/pages/leader-photo.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/leader-photo.css',
        ) ?>">
        <?php endif; ?>
    <?php if ($page === 'history'): ?>
        <link rel="stylesheet" href="assets/css/pages/history-page.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/history-page.css',
        ) ?>">
        <?php endif; ?>
    <?php if ($page === 'history'): ?>
        <script src="assets/js/pages/history-page.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/pages/history-page.js',
        ) ?>" defer>
        </script>
        <?php endif; ?>
    <?php if ($page === 'vision'): ?>
        <link rel="stylesheet" href="assets/css/pages/vision-page.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/vision-page.css',
        ) ?>">
        <?php endif; ?>
    <?php if ($page === 'vision'): ?>
        <script src="assets/js/pages/vision-page.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/pages/vision-page.js',
        ) ?>" defer>
        </script>
        <?php endif; ?>
    <?php if ($page === 'quality'): ?>
        <link rel="stylesheet" href="assets/css/pages/quality-page.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/quality-page.css',
        ) ?>">
        <script src="assets/js/pages/quality-page.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/pages/quality-page.js',
        ) ?>" defer>
        </script>
        <?php endif; ?>
    <?php if (
        in_array(
            $page,
            array_merge(
                ['services', 'outpatient', 'inpatient', 'emergency'],
                array_keys(facilityPhotoPages()),
            ),
            true,
        )
    ): ?>
        <link rel="stylesheet" href="assets/css/pages/services.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/services.css',
        ) ?>">
        <script src="assets/js/pages/facility-spotlight.js" defer>
        </script>
        <?php endif; ?>
    <?php if ($page === 'outpatient'): ?>
        <link rel="stylesheet" href="assets/css/pages/outpatient.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/outpatient.css',
        ) ?>">
        <script src="assets/js/pages/outpatient-gallery.js" defer>
        </script>
        <?php endif; ?>
    <?php if ($page === 'inpatient'): ?>
        <link rel="stylesheet" href="assets/css/pages/inpatient.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/inpatient.css',
        ) ?>">
        <script src="assets/js/components/inpatient-gallery.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/components/inpatient-gallery.js',
        ) ?>" defer>
        </script>
        <?php endif; ?>
    <?php if ($page === 'emergency'): ?>
        <link rel="stylesheet" href="assets/css/pages/emergency.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/emergency.css',
        ) ?>">
        <?php endif; ?>
    <?php if (isset(facilityPhotoPages()[$page]) || $page === 'detail'): ?>
        <link rel="stylesheet" href="assets/css/pages/facility-detail.css?v=<?= filemtime(
            APP_ROOT . '/assets/css/pages/facility-detail.css',
        ) ?>">
        <script src="assets/js/components/inpatient-gallery.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/components/inpatient-gallery.js',
        ) ?>" defer>
        </script>
        <?php endif; ?>
        <script src="assets/js/core/app.js" defer>
        </script>
        <script src="assets/js/layout/navigation.js" defer>
        </script>
        <link rel="stylesheet" href="assets/css/components/photo-editor.css">
        <script src="assets/js/components/slide-display.js" defer>
        </script>
        <script src="assets/js/components/slideshow.js" defer>
        </script>
        <script src="assets/js/components/doctor-carousel.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/components/doctor-carousel.js',
        ) ?>" defer>
        </script>
        <script src="assets/js/components/partner-carousel.js?v=<?= filemtime(
            APP_ROOT . '/assets/js/components/partner-carousel.js',
        ) ?>" defer>
        </script>
    </head>
    <body>
        <a href="#main" class="skip">Lewati ke konten</a>
        <div class="topbar topbar-v2">
            <div class="container">
                <div class="topbar-contacts">
                    <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', setting('phone'))) ?>">
                        <?= socialIcon('phone') ?>
                        <span><?= e(setting('phone')) ?></span>
                    </a>
                    <a href="https://wa.me/<?= e(
                        preg_replace('/\D/', '', setting('whatsapp')),
                    ) ?>" target="_blank" rel="noopener">
                        <?= socialIcon('whatsapp') ?>
                        <span><?= e(setting('whatsapp')) ?></span>
                    </a>
                </div>
                <div class="social-links" aria-label="Media sosial rumah sakit">
                    <?php foreach ($socialPlatforms as $key => $label): ?>
                    <?php if (setting($key) !== ''): ?>
                    <a href="<?= e(
                        setting($key),
                    ) ?>" target="_blank" rel="noopener" aria-label="<?= e(
    $label,
) ?>"><?= socialIcon($key) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <header class="site-header header-v2">
            <div class="container nav-wrap">
                <div class="brand-group">
                    <a class="brand hospital-brand" href="index.php">
                        <img class="brand-logo" src="<?= e(
                            brandingImage('logo_image', 'assets/branding/hospital.png'),
                        ) ?>" alt="Logo rumah sakit">
                        <span>
                            <?= e(setting('name')) ?>
                            <small>PALOPO - SULAWESI SELATAN</small>
                        </span>
                    </a>
                    <div class="company-brand">
                        <img src="<?= e(
                            brandingImage('company_image', 'assets/branding/hospital.png'),
                        ) ?>" alt="Logo perusahaan">
                        <span><?= preg_replace(
                            '/\s+(\S+)$/',
                            '<br>$1',
                            e(setting('company_name')),
                        ) ?></span>
                    </div>
                    <img class="accreditation-mark" src="<?= e(
                        brandingImage('accreditation_image', 'assets/branding/accreditation.jpg'),
                    ) ?>" alt="Logo akreditasi rumah sakit">
                </div>
                <button class="menu-toggle" aria-expanded="false" aria-controls="navigation" aria-label="Buka menu">&#9776;</button>
                <nav id="navigation" aria-label="Navigasi utama">
                    <?php foreach ($navigationItems as $url => $label): ?>
                    <?php if ($url === 'about'): ?>
                    <details class="doctor-menu" <?= in_array(
                        $page,
                        ['about', 'history', 'vision', 'quality'],
                        true,
                    )
                        ? 'data-active'
                        : '' ?>>
                        <summary>Tentang Kami</summary>
                        <div class="doctor-submenu">
                            <?php foreach (
                                [
                                    'history' => 'Sejarah',
                                    'vision' => 'Visi & Misi',
                                    'quality' => 'Laporan Mutu',
                                ]
                                as $aboutUrl => $aboutLabel
                            ): ?>
                            <a href="index.php?page=<?= $aboutUrl ?>" <?= $page === $aboutUrl
    ? 'aria-current="page"'
    : '' ?>><?= e($aboutLabel) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php continue; ?>
                    <?php endif; ?>
                    <?php if ($url === 'services'): ?>
                    <details class="doctor-menu facilities-menu" <?= in_array(
                        $page,
                        array_merge(
                            ['services', 'outpatient', 'inpatient', 'emergency'],
                            array_keys(facilityPhotoPages()),
                        ),
                        true,
                    )
                        ? 'data-active'
                        : '' ?>>
                        <summary>Fasilitas dan Layanan</summary>
                        <div class="doctor-submenu">
                            <a href="index.php?page=services" <?= $page === 'services'
                                ? 'aria-current="page"'
                                : '' ?>>Semua Fasilitas dan Layanan</a>
                            <?php foreach (facilityCategories() as $slug => $category): ?>
                            <a href="<?= e(facilityUrl($slug)) ?>" <?= ($page === 'outpatient' &&
    $slug === 'rawat-jalan') ||
($page === 'inpatient' && $slug === 'rawat-inap') ||
($page === 'emergency' && $slug === 'rawat-darurat') ||
($page === $slug && isset(facilityPhotoPages()[$page]))
    ? 'aria-current="page"'
    : '' ?>><?= e($category['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php continue; ?>
                    <?php endif; ?>
                    <?php if ($url === 'doctors'): ?>
                    <details class="doctor-menu" <?= in_array(
                        $page,
                        ['doctors', 'doctor_schedule', 'doctor_profile'],
                        true,
                    )
                        ? 'data-active'
                        : '' ?>>
                        <summary>Dokter</summary>
                        <div class="doctor-submenu">
                            <a href="index.php?page=doctors" <?= $page === 'doctors'
                                ? 'aria-current="page"'
                                : '' ?>>Daftar Dokter</a>
                            <a href="index.php?page=doctor_schedule" <?= $page === 'doctor_schedule'
                                ? 'aria-current="page"'
                                : '' ?>>Jadwal Dokter</a>
                        </div>
                    </details>
                    <?php continue; ?>
                    <?php endif; ?>
                    <a class="<?= $page === $url ? 'active' : '' ?>" <?= $page === $url
    ? 'aria-current="page"'
    : '' ?> href="index.php?page=<?= $url ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </header>
        <main id="main">
