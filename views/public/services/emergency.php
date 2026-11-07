<?php
// IGD: nomor kontak, jam layanan, galeri, fasilitas, dan petunjuk lokasi.

$emergency = emergencyContent();
$emergencyPhone = $emergency['phone'] !== '' ? $emergency['phone'] : setting('phone');
$emergencyPhotos = query(
    'SELECT * FROM contents WHERE kind=? AND active=1 ORDER BY CASE WHEN sort_order < 1 THEN 1 ELSE 0 END, sort_order, id DESC',
    ['emergency_photo'],
);
$emergencyFacilities = inpatientLines($emergency['facilities']);
?>
<div class="container emergency-page">
    <div class="emergency-breadcrumb">
        <a href="index.php?page=services">Fasilitas dan Layanan</a>
        <span>/</span>
        Instalasi Gawat Darurat
    </div>
    <section class="emergency-intro" aria-labelledby="emergency-title">
        <div>
            <p class="eyebrow">PENANGANAN GAWAT DARURAT</p>
            <h1 id="emergency-title">
                Instalasi
                <br>
                Gawat Darurat
            </h1>
            <?php if ($emergency['hours'] !== ''): ?>
            <p class="emergency-hours">
                <span aria-hidden="true">
                </span>
                Layanan <?= e($emergency['hours']) ?>
            </p>
            <?php endif; ?>
            <?php if ($emergency['intro'] !== ''): ?>
            <p class="emergency-intro-text"><?= nl2br(e($emergency['intro'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="emergency-contact">
            <p class="eyebrow">HUBUNGI KAMI</p>
            <h2>Kontak IGD</h2>
            <?php if ($emergencyPhone !== ''): ?>
            <a class="emergency-phone" href="tel:<?= e(
                preg_replace('/[^+0-9]/', '', $emergencyPhone),
            ) ?>">
                <?= socialIcon('phone') ?>
                <span>
                    <small><?= $emergency['phone'] !== ''
                        ? 'Telepon IGD'
                        : 'Telepon rumah sakit' ?></small>
                    <strong><?= e($emergencyPhone) ?></strong>
                </span>
            </a>
            <?php endif; ?>
            <?php if ($emergency['ambulance_phone'] !== ''): ?>
            <a class="emergency-phone" href="tel:<?= e(
                preg_replace('/[^+0-9]/', '', $emergency['ambulance_phone']),
            ) ?>">
                <?= socialIcon('phone') ?>
                <span>
                    <small>Informasi ambulans</small>
                    <strong><?= e($emergency['ambulance_phone']) ?></strong>
                </span>
            </a>
            <?php endif; ?>
            <?php if ($emergency['ambulance_note'] !== ''): ?>
            <p class="emergency-ambulance-note"><?= nl2br(e($emergency['ambulance_note'])) ?></p>
            <?php endif; ?>
            <?php if ($emergency['arrival_note'] !== ''): ?>
            <p class="emergency-arrival-note"><?= nl2br(e($emergency['arrival_note'])) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <div class="emergency-layout">
        <div>
            <?php if ($emergencyPhotos): ?>
            <section class="emergency-photo-section" aria-label="Foto fasilitas IGD">
                <div class="hospital-slideshow emergency-gallery" data-slideshow role="region" aria-roledescription="carousel" aria-label="Galeri IGD">
                    <div class="slide-stage">
                        <?php foreach ($emergencyPhotos as $i => $photo): ?>
                                <?php $display = json_decode(
                                    setting('slide_display_' . $photo['id'], '{}'),
                                    true,
                                ) ?: ['fit' => 'contain']; ?>
                        <figure class="hospital-slide <?= $i === 0
                            ? 'is-current'
                            : '' ?>" data-slide <?= $i > 0 ? 'hidden' : '' ?> aria-label="<?= $i +
     1 .
     ' dari ' .
     count($emergencyPhotos) ?>">
                            <div class="slide-photo-frame">
                                <img data-slide-image data-display="<?= e(
                                    json_encode($display),
                                ) ?>" src="<?= e(photo($photo['image'])) ?>" alt="<?= e(
    $photo['title'] ?: 'Fasilitas IGD ' . ($i + 1),
) ?>" loading="lazy">
                            </div>
                            <?php if ($photo['title'] !== ''): ?>
                            <figcaption><?= e($photo['title']) ?></figcaption>
                            <?php endif; ?>
                        </figure>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($emergencyPhotos) > 1): ?>
                    <button type="button" class="slide-arrow slide-prev" data-prev aria-label="Foto sebelumnya">&lsaquo;</button>
                    <button type="button" class="slide-arrow slide-next" data-next aria-label="Foto berikutnya">&rsaquo;</button>
                    <div class="slide-toolbar">
                        <div class="slide-dots" aria-label="Pilih foto IGD">
                            <?php foreach ($emergencyPhotos as $i => $photo): ?>
                            <button type="button" data-slide-to="<?= $i ?>" aria-label="Foto <?= $i +
    1 ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <span class="sr-only" data-slide-status aria-live="polite">
                    </span>
                </div>
            </section>
            <?php endif; ?>
            <?php if ($emergencyFacilities): ?>
            <section class="emergency-facilities">
                <p class="eyebrow">FASILITAS PELAYANAN</p>
                <h2>Fasilitas IGD</h2>
                <ul>
                    <?php foreach ($emergencyFacilities as $facility): ?>
                    <li><?= e($facility) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
            <section class="emergency-location">
                <p class="eyebrow">AKSES RUMAH SAKIT</p>
                <h2>Lokasi &amp; petunjuk kedatangan</h2>
                <p><?= nl2br(e(setting('address'))) ?></p>
                <?php if ($emergency['location'] !== ''): ?>
                <p><?= nl2br(e($emergency['location'])) ?></p>
                <?php endif; ?>
                <a class="text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode(
                    setting('name') . ' ' . setting('address'),
                ) ?>" target="_blank" rel="noopener">Buka petunjuk arah &rarr;</a>
            </section>
        </div>
        <aside class="emergency-sidebar">
            <nav aria-label="Fasilitas dan layanan lainnya">
                <h2>Fasilitas dan Layanan</h2>
                <?php foreach (facilityCategories() as $slug => $category): ?>
                <a href="<?= e(facilityUrl($slug)) ?>" <?= $slug === 'rawat-darurat'
    ? 'aria-current="page"'
    : '' ?>><?= e($category['title']) ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>
    </div>
</div>
