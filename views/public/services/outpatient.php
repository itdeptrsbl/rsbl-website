<?php
// Rawat Jalan: galeri, penjelasan, kartu poli, dan navigasi fasilitas.

// Urutan 1 menjadi foto utama; foto tanpa urutan ditempatkan terakhir.
$photos = query(
    'SELECT * FROM contents WHERE kind=? AND active=1 ORDER BY CASE WHEN sort_order < 1 THEN 1 ELSE 0 END, sort_order, id DESC',
    ['outpatient_photo'],
);
$clinics = records('clinic');
?>
<div class="container outpatient-page">
    <div class="outpatient-breadcrumb">
        <a href="index.php?page=services">Fasilitas dan Layanan</a>
        <span>/</span>
        Rawat Jalan
    </div>
    <div class="outpatient-layout">
        <div>
            <header class="outpatient-heading">
                <p class="eyebrow">FASILITAS DAN LAYANAN</p>
                <h1>Rawat Jalan</h1>
                <p>Konsultasi dan perawatan sesuai kebutuhan Anda.</p>
            </header>
            <?php if ($photos): ?>
            <section class="outpatient-gallery" aria-label="Foto fasilitas Rawat Jalan" data-outpatient-gallery>
                <?php
                $photo = $photos[0];
                $i = 0;
                ?>
                <div class="outpatient-main-photo"><?php require APP_ROOT .
                    '/views/components/media/outpatient-photo.php'; ?></div>
                <?php if (count($photos) > 1): ?>
                <div class="outpatient-photo-slider">
                    <button type="button" class="outpatient-photo-arrow" data-gallery-prev aria-label="Geser foto ke kiri" aria-controls="outpatient-photo-track">&lsaquo;</button>
                    <div class="outpatient-photo-track" id="outpatient-photo-track" data-gallery-track tabindex="0" role="region" aria-label="Foto Rawat Jalan lainnya"><?php foreach (
                        array_slice($photos, 1)
                        as $i => $photo
                    ): ?>
                                    <?php $i++; ?>
                                    <?php require APP_ROOT .
                                        '/views/components/media/outpatient-photo.php'; ?>
                                <?php endforeach; ?></div>
                    <button type="button" class="outpatient-photo-arrow" data-gallery-next aria-label="Geser foto ke kanan" aria-controls="outpatient-photo-track">&rsaquo;</button>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>
            <?php $intro = setting(
                'outpatient_intro',
                'Pelayanan rawat jalan menyediakan konsultasi, pemeriksaan, dan penanganan sesuai kebutuhan pasien tanpa menjalani rawat inap.',
            ); ?>
            <?php if ($intro !== ''): ?>
            <section class="outpatient-description">
                <h2>Pelayanan Rawat Jalan</h2>
                <p><?= nl2br(e($intro)) ?></p>
            </section>
            <?php endif; ?>
            <section class="outpatient-clinics" aria-labelledby="clinic-heading">
                <p class="eyebrow">POLIKLINIK KAMI</p>
                <h2 id="clinic-heading">Temukan layanan poli Anda.</h2>
                <p>Pilih poliklinik untuk melihat dokter yang tersedia.</p>
                <div class="clinic-grid">
                    <?php foreach ($clinics as $clinic): ?>
                        <?php $clinicDisplay = json_decode(
                            setting('slide_display_' . $clinic['id'], '{}'),
                            true,
                        ) ?: ['fit' => 'contain']; ?>
                    <a class="clinic-card" href="index.php?page=doctors&amp;clinic=<?= (int) $clinic[
                        'id'
                    ] ?>">
                        <div class="clinic-image">
                            <?php if ($clinic['image'] !== ''): ?>
                            <img data-slide-image data-display="<?= e(
                                json_encode($clinicDisplay),
                            ) ?>" src="<?= e(photo($clinic['image'])) ?>" alt="" loading="lazy">
                            <?php else: ?>
                            <span aria-hidden="true">+</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($clinic['title']) ?></h3>
                        <span class="clinic-link">Lihat dokter</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!$clinics): ?>
                <p class="notice">
                    Informasi poliklinik sedang diperbarui.
                    <a href="index.php?page=doctors">Lihat daftar dokter</a>
                    .
                </p>
                <?php endif; ?>
            </section>
        </div>
        <aside class="outpatient-sidebar">
            <nav aria-label="Fasilitas dan layanan lainnya">
                <h2>Fasilitas dan Layanan</h2>
                <?php foreach (facilityCategories() as $slug => $category): ?>
                <a href="<?= e(facilityUrl($slug)) ?>" <?= $slug === 'rawat-jalan'
    ? 'aria-current="page"'
    : '' ?>><?= e($category['title']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="outpatient-help">
                <h3>Perlu bantuan?</h3>
                <p>Hubungi petugas untuk informasi layanan dan persiapan kunjungan.</p>
                <a class="button small" href="index.php?page=contact">Hubungi kami</a>
            </div>
        </aside>
    </div>
</div>
