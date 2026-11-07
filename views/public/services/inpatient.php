<?php
// Rawat Inap: jenis kamar, galeri, kapasitas opsional, dan fasilitas kamar.

$rooms = query('SELECT * FROM contents WHERE kind=? AND active=1 ORDER BY sort_order,id', [
    'inpatient_room',
]);
$intro = setting(
    'inpatient_intro',
    'Kenali pilihan ruang perawatan dan fasilitas untuk kenyamanan pasien serta keluarga. Hubungi petugas untuk informasi kamar dan persiapan rawat inap.',
);
$wards = inpatientLines(setting('inpatient_wards'));
?>
<div class="container inpatient-page">
    <div class="inpatient-breadcrumb">
        <a href="index.php?page=services">Fasilitas dan Layanan</a>
        <span>/</span>
        Rawat Inap
    </div>
    <header class="inpatient-intro">
        <p class="eyebrow">RUANG PERAWATAN</p>
        <h1>Rawat Inap</h1>
        <?php if ($intro !== ''): ?>
        <p class="inpatient-intro-text"><?= nl2br(e($intro)) ?></p>
        <?php endif; ?>
        <?php if ($rooms): ?>
        <nav class="inpatient-room-links" aria-label="Pilih jenis kamar">
            <?php foreach ($rooms as $room): ?>
            <a href="#kamar-<?= (int) $room['id'] ?>"><?= e($room['title']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </header>
    <div class="inpatient-layout">
        <div class="inpatient-room-list">
            <?php foreach ($rooms as $room): ?>
                <?php
                $data = inpatientRoomData($room);
                $roomPhotos = inpatientRoomPhotos($room);
                $coverDisplay = json_decode(
                    setting('slide_display_' . $room['id'], '{}'),
                    true,
                ) ?: ['fit' => 'contain'];
                if ($roomPhotos && !$roomPhotos[0]['cover']) {
                    $coverDisplay = $roomPhotos[0]['display'];
                }
                ?>
            <article class="inpatient-room" id="kamar-<?= (int) $room['id'] ?>">
                <div class="inpatient-room-gallery" data-room-gallery>
                    <div class="inpatient-room-image">
                        <a data-room-main-link href="<?= e(
                            $roomPhotos
                                ? photo($roomPhotos[0]['src'])
                                : 'assets/room-placeholder.svg',
                        ) ?>" target="_blank" rel="noopener" aria-label="Lihat foto <?= e(
    $room['title'],
) ?> ukuran penuh">
                            <img data-room-main data-slide-image data-display="<?= e(
                                json_encode($coverDisplay),
                            ) ?>" src="<?= e(
    $roomPhotos ? photo($roomPhotos[0]['src']) : 'assets/room-placeholder.svg',
) ?>" alt="<?= e($room['title']) ?>" loading="lazy">
                        </a>
                        <?php if (count($roomPhotos) > 1): ?>
                        <button type="button" class="room-gallery-arrow room-prev" data-room-prev aria-label="Foto kamar sebelumnya">&lsaquo;</button>
                        <button type="button" class="room-gallery-arrow room-next" data-room-next aria-label="Foto kamar berikutnya">&rsaquo;</button>
                        <?php endif; ?>
                    </div>
                    <?php $firstCaption = $roomPhotos[0]['caption'] ?? ''; ?>
                    <p class="inpatient-photo-caption" data-room-caption <?= $firstCaption === ''
                        ? 'hidden'
                        : '' ?>><?= e($firstCaption) ?></p>
                    <?php if (count($roomPhotos) > 1): ?>
                    <div class="inpatient-room-thumbs" role="group" aria-label="Pilih foto <?= e(
                        $room['title'],
                    ) ?>">
                        <?php foreach ($roomPhotos as $i => $roomPhoto): ?>
                        <button type="button" data-room-photo data-caption="<?= e(
                            $roomPhoto['caption'],
                        ) ?>" data-src="<?= e(photo($roomPhoto['src'])) ?>" data-display="<?= e(
    json_encode($roomPhoto['cover'] ? $coverDisplay : $roomPhoto['display']),
) ?>" aria-label="Foto <?= $i + 1 ?>: <?= e($room['title']) ?>" aria-pressed="<?= $i === 0
    ? 'true'
    : 'false' ?>">
                            <img src="<?= e(photo($roomPhoto['src'])) ?>" alt="" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="room-gallery-status" data-room-status aria-live="polite">Foto 1 dari <?= count(
                        $roomPhotos,
                    ) ?></p>
                    <?php endif; ?>
                </div>
                <div class="inpatient-room-details">
                    <?php if (!empty($data['beds'])): ?>
                    <p class="inpatient-capacity"><?= (int) $data[
                        'beds'
                    ] ?> tempat tidur per kamar</p>
                    <?php endif; ?>
                    <h2><?= e($room['title']) ?></h2>
                    <?php if (!empty($data['description'])): ?>
                    <p><?= nl2br(e($data['description'])) ?></p>
                    <?php endif; ?>
                        <?php if (!empty($data['facilities'])): ?>
                    <h3>Fasilitas kamar</h3>
                    <ul class="inpatient-facilities">
                        <?php foreach ($data['facilities'] as $facility): ?>
                        <li><?= e($facility) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a class="text-link" href="index.php?page=contact">Tanyakan informasi kamar &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if (!$rooms): ?>
            <div class="inpatient-empty">
                <h2>Informasi ruang perawatan</h2>
                <p>Informasi jenis kamar dan fasilitas sedang diperbarui. Hubungi petugas untuk mengetahui pilihan kamar yang tersedia.</p>
                <a class="button" href="index.php?page=contact">Hubungi petugas</a>
            </div>
            <?php endif; ?>
            <?php if ($wards): ?>
            <section class="inpatient-wards">
                <p class="eyebrow">AREA PERAWATAN</p>
                <h2>Ruang perawatan kami</h2>
                <ul>
                    <?php foreach ($wards as $ward): ?>
                    <li><?= e($ward) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
            <p class="inpatient-note">Ketersediaan kamar, fasilitas, dan ketentuan pembiayaan perlu dikonfirmasi kepada petugas rumah sakit.</p>
        </div>
        <aside class="inpatient-sidebar">
            <nav aria-label="Fasilitas dan layanan lainnya">
                <h2>Fasilitas dan Layanan</h2>
                <?php foreach (facilityCategories() as $slug => $category): ?>
                <a href="<?= e(facilityUrl($slug)) ?>" <?= $slug === 'rawat-inap'
    ? 'aria-current="page"'
    : '' ?>><?= e($category['title']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="inpatient-assistance">
                <h3>Perlu bantuan?</h3>
                <p>Petugas kami siap membantu informasi rawat inap dan pilihan ruang perawatan.</p>
                <a class="button small" href="index.php?page=contact">Hubungi kami</a>
            </div>
        </aside>
    </div>
</div>
