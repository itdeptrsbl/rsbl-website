<?php
// Template bersama Farmasi, Radiologi, Fisioterapi, dan Laboratorium.

$facilityConfig = facilityPhotoPages()[$page];
$facilityTitle = $facilityConfig['title'];
$facilityIntro = setting('facility_intro_' . $page);
$facilityPhotos = query(
    'SELECT * FROM contents WHERE kind=? AND active=1 ORDER BY CASE WHEN sort_order < 1 THEN 1 ELSE 0 END, sort_order, id DESC',
    [$facilityConfig['kind']],
);
$firstPhoto = $facilityPhotos[0] ?? null;
$firstDisplay = $firstPhoto
    ? (json_decode(setting('slide_display_' . $firstPhoto['id'], '{}'), true) ?: [
        'fit' => 'contain',
    ])
    : [];
?>
<div class="container facility-detail-page">
    <div class="facility-detail-breadcrumb">
        <a href="index.php?page=services">Fasilitas dan Layanan</a>
        <span>/</span>
        <?= e($facilityTitle) ?>
    </div>
    <div class="facility-detail-layout">
        <div>
            <header class="facility-detail-heading">
                <p class="eyebrow">FASILITAS DAN LAYANAN</p>
                <h1><?= e($facilityTitle) ?></h1>
                <?php if ($facilityIntro !== ''): ?>
                <p class="facility-detail-description"><?= nl2br(e($facilityIntro)) ?></p>
                <?php else: ?>
                <p><?= e(facilityCategories()[$page]['caption']) ?></p>
                <?php endif; ?>
            </header>
            <?php if ($firstPhoto): ?>
            <section class="facility-detail-gallery" data-room-gallery aria-label="Galeri <?= e(
                $facilityTitle,
            ) ?>">
                <div class="facility-detail-main">
                    <a data-room-main-link href="<?= e(
                        photo($firstPhoto['image']),
                    ) ?>" target="_blank" rel="noopener" aria-label="Lihat foto <?= e(
    $facilityTitle,
) ?> ukuran penuh">
                        <img data-room-main data-slide-image data-display="<?= e(
                            json_encode($firstDisplay),
                        ) ?>" src="<?= e(photo($firstPhoto['image'])) ?>" alt="<?= e(
    $firstPhoto['title'] ?: $facilityTitle,
) ?>" fetchpriority="high">
                    </a>
                    <?php if (count($facilityPhotos) > 1): ?>
                    <button type="button" class="facility-detail-arrow gallery-prev" data-room-prev aria-label="Foto sebelumnya">&lsaquo;</button>
                    <button type="button" class="facility-detail-arrow gallery-next" data-room-next aria-label="Foto berikutnya">&rsaquo;</button>
                    <?php endif; ?>
                </div>
                <p class="facility-detail-caption" data-room-caption <?= $firstPhoto['title'] === ''
                    ? 'hidden'
                    : '' ?>><?= e($firstPhoto['title']) ?></p>
                <?php if (count($facilityPhotos) > 1): ?>
                <div class="facility-detail-thumbs" role="group" aria-label="Pilih foto <?= e(
                    $facilityTitle,
                ) ?>">
                    <?php foreach ($facilityPhotos as $i => $photo): ?>
                                <?php $display = json_decode(
                                    setting('slide_display_' . $photo['id'], '{}'),
                                    true,
                                ) ?: ['fit' => 'contain']; ?>
                    <button type="button" data-room-photo data-src="<?= e(
                        photo($photo['image']),
                    ) ?>" data-caption="<?= e($photo['title']) ?>" data-display="<?= e(
    json_encode($display),
) ?>" aria-label="<?= e($photo['title'] ?: 'Foto ' . ($i + 1)) ?>" aria-pressed="<?= $i === 0
    ? 'true'
    : 'false' ?>">
                        <img src="<?= e(photo($photo['image'])) ?>" alt="" loading="lazy">
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="facility-detail-count" data-room-status aria-live="polite">Foto 1 dari <?= count(
                    $facilityPhotos,
                ) ?></p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
            <div class="facility-detail-contact">
                <div>
                    <h2>Informasi Layanan</h2>
                    <p>Hubungi petugas untuk informasi layanan rumah sakit dan persiapan kunjungan Anda.</p>
                </div>
                <a class="button small" href="index.php?page=contact">Hubungi kami</a>
            </div>
        </div>
        <aside class="facility-detail-sidebar">
            <nav aria-label="Fasilitas dan layanan lainnya">
                <h2>Fasilitas dan Layanan</h2>
                <?php foreach (facilityCategories() as $slug => $category): ?>
                <a href="<?= e(facilityUrl($slug)) ?>" <?= $slug === $page
    ? 'aria-current="page"'
    : '' ?>><?= e($category['title']) ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>
    </div>
</div>
